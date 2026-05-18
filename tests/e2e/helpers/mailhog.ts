type MailhogAddress = { Mailbox?: string; Domain?: string };

type MailhogMessage = {
    ID?: string;
    Created?: string;
    To?: MailhogAddress[];
    Content?: {
        Body?: string;
        Headers?: Record<string, string[]>;
        MIME?: { Parts?: unknown[] };
    };
};

type MailhogListResponse = {
    items?: MailhogMessage[];
};

function addressToEmail(addr: MailhogAddress): string {
    const m = (addr.Mailbox ?? '').trim();
    const d = (addr.Domain ?? '').trim();
    return `${m}@${d}`.toLowerCase();
}

/**
 * Parsed `To` addresses from RFC5322 header lines (Mailhog may only populate these).
 */
function headerRecipientEmails(msg: MailhogMessage): string[] {
    const raw = msg.Content?.Headers?.To;
    if (!Array.isArray(raw)) {
        return [];
    }
    const out: string[] = [];
    for (const line of raw) {
        const angle = line.match(/<([^>]+@[^>]+)>/);
        if (angle?.[1]) {
            out.push(angle[1].trim().toLowerCase());
            continue;
        }
        const plain = line.trim().toLowerCase();
        if (plain.includes('@')) {
            out.push(plain.replace(/^"|"$/g, ''));
        }
    }
    return out;
}

function messageMatchesRecipient(msg: MailhogMessage, email: string): boolean {
    const want = email.toLowerCase();
    const to = msg.To ?? [];
    if (to.some((addr) => addressToEmail(addr) === want)) {
        return true;
    }
    return headerRecipientEmails(msg).includes(want);
}

function collectTextBodies(content: unknown): string[] {
    if (!content || typeof content !== 'object') {
        return [];
    }
    const c = content as MailhogMessage['Content'] & {
        MIME?: { Parts?: unknown[] };
    };
    const out: string[] = [];
    if (typeof c.Body === 'string' && c.Body.length > 0) {
        out.push(c.Body);
    }
    const parts = c.MIME?.Parts;
    if (Array.isArray(parts)) {
        for (const p of parts) {
            out.push(...collectTextBodies(p));
        }
    }
    return out;
}

/**
 * Laravel sends HTML as quoted-printable; OTP digits may be split as `2719=\r\n33`.
 */
function unfoldQuotedPrintable(input: string): string {
    let s = input.replace(/=\r?\n/g, '');
    s = s.replace(/=([0-9A-F]{2})/gi, (_, hex: string) =>
        String.fromCharCode(parseInt(hex, 16)),
    );
    return s;
}

function extractOtpFromBodies(bodies: string[]): string | null {
    const combined = bodies.map(unfoldQuotedPrintable).join('\n');
    const matches = [...combined.matchAll(/\b(\d{6})\b/g)].map((m) => m[1]!);
    if (matches.length === 0) {
        return null;
    }
    return matches[matches.length - 1]!;
}

function parseCreatedMs(created: string | undefined): number {
    if (!created) {
        return 0;
    }
    const t = Date.parse(created);
    return Number.isNaN(t) ? 0 : t;
}

export async function fetchMailhogMessages(
    mailhogBaseUrl: string,
): Promise<MailhogMessage[]> {
    const base = mailhogBaseUrl.replace(/\/$/, '');
    const res = await fetch(`${base}/api/v2/messages?limit=500`);
    if (!res.ok) {
        throw new Error(`Mailhog ${res.status}: ${res.statusText}`);
    }
    const data = (await res.json()) as MailhogListResponse;
    return data.items ?? [];
}

/**
 * Poll Mailhog for the newest login OTP email to `toEmail` created at or after `notBeforeMs` (epoch ms).
 */
export async function waitForLoginOtp(options: {
    mailhogBaseUrl: string;
    toEmail: string;
    notBeforeMs: number;
    timeoutMs?: number;
    pollMs?: number;
}): Promise<string> {
    const {
        mailhogBaseUrl,
        toEmail,
        notBeforeMs,
        timeoutMs = 20_000,
        pollMs = 250,
    } = options;
    const deadline = Date.now() + timeoutMs;
    let lastError: Error | null = null;

    while (Date.now() < deadline) {
        try {
            const items = await fetchMailhogMessages(mailhogBaseUrl);
            const candidates = items
                .filter((m) => messageMatchesRecipient(m, toEmail))
                .filter((m) => {
                    if (notBeforeMs <= 0) {
                        return true;
                    }
                    const skewMs = 120_000;
                    return parseCreatedMs(m.Created) >= notBeforeMs - skewMs;
                })
                .sort(
                    (a, b) =>
                        parseCreatedMs(b.Created) - parseCreatedMs(a.Created),
                );

            for (const msg of candidates) {
                const bodies = collectTextBodies(msg.Content);
                const otp = extractOtpFromBodies(bodies);
                if (otp) {
                    return otp;
                }
            }
        } catch (e) {
            lastError =
                e instanceof Error ? e : new Error(String(e));
        }
        await new Promise((r) => setTimeout(r, pollMs));
    }

    throw lastError ?? new Error(`No OTP email to ${toEmail} within ${timeoutMs}ms`);
}
