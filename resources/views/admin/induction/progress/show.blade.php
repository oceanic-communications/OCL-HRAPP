@extends('layouts.portal')

@section('title', $user->name.' · Employee progress · '.config('app.name'))

@section('content')
@php
    $version = $detail['version'] ?? null;
    $summary = $detail['summary'];
    $enrollment = $detail['enrollment'] ?? null;
    $completions = $detail['completions'] ?? collect();
    $status = $summary['status'];
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
    $tz = config('app.timezone');
@endphp

<div class="space-y-8">
    <div>
        <a href="{{ route('admin.induction.progress.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to employee progress</a>
        <h1 class="mt-3 font-heading text-2xl font-bold text-foreground">{{ $user->name }}</h1>
        <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
    </div>

    <div class="portal-card space-y-4 p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">Summary</h2>
        @if ($version)
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Policy</dt>
                    <dd class="mt-1 text-sm font-medium text-foreground">{{ $version->policy->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Policy version</dt>
                    <dd class="mt-1 text-sm font-medium text-foreground">{{ $version->version_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</dt>
                    <dd class="mt-1">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Clauses completed</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        @if ($summary['sections_total'] > 0)
                            {{ $summary['sections_completed'] }} / {{ $summary['sections_total'] }} ({{ $summary['progress_percent'] }}%)
                        @else
                            No clauses in policy
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Started</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        @if ($summary['started_at'])
                            {{ $summary['started_at']->timezone($tz)->format('Y-m-d H:i T') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Completed</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        @if ($summary['completed_at'])
                            {{ $summary['completed_at']->timezone($tz)->format('Y-m-d H:i T') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        @else
            <p class="text-sm text-muted-foreground">No published induction policy is active.</p>
        @endif
    </div>

    <div class="portal-card space-y-4 p-5">
        <div>
            <h2 class="font-heading text-lg font-semibold text-foreground">Section acknowledgements</h2>
            <!-- <p class="mt-1 text-sm text-muted-foreground">
                Each row records the employee name, date and time, IP address, device information, and policy version captured at acknowledgement.
            </p> -->
        </div>

        @if ($completions->isEmpty())
            <p class="text-sm text-muted-foreground">
                @if ($enrollment)
                    No clauses have been acknowledged yet.
                @else
                    This employee has not started induction for the current policy.
                @endif
            </p>
        @else
            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Section</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Employee name</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Date &amp; time</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Policy version</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">IP address</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Device</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($completions as $completion)
                            <tr>
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
    </div>
</div>
@endsection
