@props([
    'completions',
])

@php
    $tz = config('app.timezone');
@endphp

@if ($completions->isEmpty())
    <p class="text-sm text-muted-foreground">No acknowledgements or signatures on record yet.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-border">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted/40">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Policy</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Section</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Employee name</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Date &amp; time</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Policy version</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Signature</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">IP address</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Device</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border bg-card">
                @foreach ($completions as $completion)
                    @php
                        $version = $completion->enrollment?->version;
                        $policyName = $version?->policy?->name;
                        $hasStoredSignature = $completion->signature_path
                            && $completion->signature_disk
                            && \Illuminate\Support\Facades\Storage::disk($completion->signature_disk)->exists($completion->signature_path);
                        $requiredSignature = $completion->section?->requires_signature ?? false;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-foreground">{{ $policyName ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-foreground">{{ $completion->section?->title ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-foreground">{{ $completion->employee_name_snapshot ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                            @if ($completion->completed_at)
                                {{ $completion->completed_at->timezone($tz)->format('Y-m-d H:i:s T') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $completion->policy_version_label_snapshot ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                            @if ($hasStoredSignature)
                                <span class="text-foreground">Digital signature on file</span>
                            @elseif ($requiredSignature)
                                <span class="text-destructive">Signature required (not stored)</span>
                            @else
                                <span>Acknowledgement only</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-muted-foreground">{{ $completion->ip_address ?? '—' }}</td>
                        <td class="max-w-xs px-4 py-3 text-xs text-muted-foreground" title="{{ $completion->user_agent }}">
                            {{ $completion->user_agent ? \Illuminate\Support\Str::limit($completion->user_agent, 80) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
