<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Induction acknowledgement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.45; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 0 0 8px; }
        .muted { color: #444; font-size: 10px; margin-bottom: 16px; }
        .section { margin-top: 20px; padding-top: 12px; border-top: 1px solid #ccc; page-break-inside: avoid; }
        .section:first-of-type { border-top: 0; margin-top: 12px; padding-top: 0; }
        .section-body { margin: 10px 0 14px; padding: 10px 12px; background: #fafafa; border: 1px solid #e5e5e5; }
        .section-body p { margin: 0 0 8px; }
        .section-body ul, .section-body ol { margin: 0 0 8px 18px; padding: 0; }
        .signoff { margin-top: 10px; padding: 10px 12px; border: 1px solid #bbb; background: #f5f5f5; }
        .signoff-table { width: 100%; border-collapse: collapse; }
        .signoff-table td { padding: 4px 8px 4px 0; vertical-align: top; }
        .signoff-label { font-weight: bold; width: 120px; color: #333; }
        .sig { max-height: 72px; max-width: 200px; }
        .meta { margin-top: 24px; font-size: 10px; color: #333; border-top: 1px solid #ddd; padding-top: 12px; }
    </style>
</head>
<body>
    <h1>Induction acknowledgement record</h1>
    <p class="muted">{{ $enrollment->version->policy->name }} — version {{ $enrollment->version->version_label }}</p>
    <p><strong>Employee:</strong> {{ $enrollment->user->name }} ({{ $enrollment->user->email }})</p>
    <p><strong>Completed at:</strong> {{ $enrollment->completed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s T') }}</p>

    @foreach ($completions as $c)
        @php
            $rawBody = $c->section->body ?? '';
            $sectionBodyHtml = str_contains($rawBody, '<')
                ? \App\Support\RichHtmlPurifier::purify($rawBody)
                : nl2br(e($rawBody));
        @endphp
        <div class="section">
            <h2>Section {{ $c->section->sort_order }}: {{ $c->section->title }}</h2>

            <div class="section-body">
                {!! $sectionBodyHtml !!}

                @foreach ($c->section->activeSubClauses ?? [] as $subClause)
                    @php
                        $subBody = $subClause->body ?? '';
                        $subBodyHtml = str_contains($subBody, '<')
                            ? \App\Support\RichHtmlPurifier::purify($subBody)
                            : nl2br(e($subBody));
                    @endphp
                    <div style="margin-top:14px;padding-top:10px;border-top:1px solid #e0e0e0;">
                        <p style="margin:0 0 6px;font-weight:bold;">{{ $subClause->title }}</p>
                        {!! $subBodyHtml !!}
                    </div>
                @endforeach
            </div>

            <div class="signoff">
                <p style="margin:0 0 8px;font-weight:bold;">Employee sign-off</p>
                <table class="signoff-table">
                    <tr>
                        <td class="signoff-label">Acknowledged as</td>
                        <td>{{ $c->employee_name_snapshot }}</td>
                    </tr>
                    <tr>
                        <td class="signoff-label">Date &amp; time</td>
                        <td>{{ $c->completed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s T') }}</td>
                    </tr>
                    <tr>
                        <td class="signoff-label">Policy version</td>
                        <td>{{ $c->policy_version_label_snapshot }}</td>
                    </tr>
                    <tr>
                        <td class="signoff-label">IP address</td>
                        <td>{{ $c->ip_address ?? '—' }}</td>
                    </tr>
                    @if ($c->user_agent)
                        <tr>
                            <td class="signoff-label">Device</td>
                            <td style="font-size:9px;word-break:break-all;">{{ \Illuminate\Support\Str::limit($c->user_agent, 200) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="signoff-label">Signature</td>
                        <td>
                            @if ($c->signature_path && $c->signature_disk && \Illuminate\Support\Facades\Storage::disk($c->signature_disk)->exists($c->signature_path))
                                @php
                                    $data = base64_encode(\Illuminate\Support\Facades\Storage::disk($c->signature_disk)->get($c->signature_path));
                                @endphp
                                <img class="sig" src="data:image/png;base64,{{ $data }}" alt="" width="180">
                            @elseif ($c->section->requires_signature)
                                <span style="color:#888;">Not captured</span>
                            @else
                                <span>Acknowledged without digital signature (section does not require one)</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    <div class="meta">
        <p style="margin:0;">This document was generated by {{ config('app.name') }} and includes the induction content and employee sign-off for each section completed.</p>
    </div>
</body>
</html>
