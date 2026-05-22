@extends('layouts.portal')

@section('title', 'Change log entry · '.config('app.name'))

@section('content')
@php
    $changes = $presenter->changes();
@endphp
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.change-logs.index', $log->induction_policy_id ? ['policy' => $log->induction_policy_id] : []) }}" class="text-sm font-medium text-primary hover:underline">← Back to change log</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">{{ $presenter->actionLabel() }}</h1>
        <p class="text-sm text-muted-foreground">Read-only audit record. This entry cannot be modified.</p>
    </div>

    <div class="portal-card space-y-4 p-5">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date &amp; time</dt>
                <dd class="mt-1 text-sm font-medium text-foreground">{{ $log->created_at?->timezone(config('app.timezone'))->format('d M Y, g:i A T') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Changed by</dt>
                <dd class="mt-1 text-sm font-medium text-foreground">{{ $presenter->actorName() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Policy</dt>
                <dd class="mt-1 text-sm font-medium text-foreground">{{ $presenter->policyLabel() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Subject</dt>
                <dd class="mt-1 text-sm font-medium text-foreground">{{ $presenter->subjectLabel() }}</dd>
            </div>
            @if ($log->staff_repeat_requested || $log->staff_repeat_applied)
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Staff repeat induction</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        Requested: {{ $log->staff_repeat_requested ? 'Yes' : 'No' }} · Applied: {{ $log->staff_repeat_applied ? 'Yes' : 'No' }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="portal-card overflow-hidden">
        <div class="border-b border-border bg-muted/30 px-5 py-3">
            <h2 class="text-sm font-semibold text-foreground">Changes (from → to)</h2>
        </div>
        @if ($changes === [])
            <p class="p-5 text-sm text-muted-foreground">No field-level changes were recorded for this entry.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Field</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">From</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">To</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($changes as $change)
                            <tr>
                                <td class="px-4 py-3 font-medium text-foreground">{{ $change['label'] }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $change['from'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-foreground">{{ $change['to'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
