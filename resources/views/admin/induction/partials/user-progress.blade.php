@php
    $version = $inductionProgress['version'] ?? null;
    $totalSections = (int) ($inductionProgress['total_sections'] ?? 0);
    $rows = $inductionProgress['rows'] ?? collect();
    $showDetailLinks = $showDetailLinks ?? false;
@endphp

<div class="portal-card space-y-4 p-5">
    <div>
        <h2 class="font-heading text-lg font-semibold text-foreground">{{ $showDetailLinks ? 'All employees' : 'User induction progress' }}</h2>
        @unless ($showDetailLinks)
            <p class="mt-1 text-sm text-muted-foreground">
                @if ($version)
                    Progress against the current published policy
                    <span class="font-medium text-foreground">{{ $version->policy->name }}</span>
                    ({{ $version->version_label }}).
                @else
                    No published induction policy is active. Publish a policy to track staff progress.
                @endif
            </p>
        @endunless
    </div>

    @if ($rows->isEmpty())
        <p class="text-sm text-muted-foreground">No active portal users to display.</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">User</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Email</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Clauses</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Progress</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Last activity</th>
                        @if ($showDetailLinks)
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-foreground"><span class="sr-only">Details</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-border bg-card">
                    @foreach ($rows as $row)
                        @php
                            $status = $row['status'];
                            $statusLabel = match ($status) {
                                'completed' => 'Completed',
                                'in_progress' => 'In progress',
                                default => 'Not started',
                            };
                            $statusClass = match ($status) {
                                'completed' => 'bg-success/15 text-success',
                                'in_progress' => 'bg-warning/15 text-warning-foreground',
                                default => 'bg-muted text-muted-foreground',
                            };
                            $lastActivity = $row['completed_at'] ?? $row['started_at'];
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-foreground">{{ $row['user']->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['user']->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                @if ($totalSections > 0)
                                    {{ $row['sections_completed'] }} / {{ $row['sections_total'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($totalSections > 0)
                                    <div class="flex min-w-[10rem] items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full rounded-full bg-primary transition-all"
                                                style="width: {{ $row['progress_percent'] }}%"
                                                role="progressbar"
                                                aria-valuenow="{{ $row['progress_percent'] }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            ></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-xs font-medium text-muted-foreground">{{ $row['progress_percent'] }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-muted-foreground">No clauses</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                @if ($lastActivity)
                                    {{ $lastActivity->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            @if ($showDetailLinks)
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.induction.progress.show', $row['user']) }}" class="font-medium text-primary hover:underline">View details</a>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
