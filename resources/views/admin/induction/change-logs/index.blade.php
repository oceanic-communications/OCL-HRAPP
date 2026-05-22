@extends('layouts.portal')

@section('title', 'Policy change log · '.config('app.name'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <a href="{{ route('admin.induction.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to policies</a>
            <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Policy change log</h1>
            <p class="text-sm text-muted-foreground">Read-only audit trail of policy, clause, and sub-clause changes. Entries cannot be edited or deleted.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.induction.change-logs.index') }}" class="portal-card flex flex-wrap items-end gap-4 p-4">
        <div class="min-w-[12rem] flex-1">
            <label class="portal-label" for="policy_filter">Filter by policy</label>
            <select id="policy_filter" name="policy" class="portal-input mt-1 w-full">
                <option value="">All policies</option>
                @foreach ($policies as $p)
                    <option value="{{ $p->id }}" @selected($filterPolicy?->id === $p->id)>{{ $p->abbreviation }} · {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:bg-secondary/90">Apply filter</button>
        @if ($filterPolicy)
            <a href="{{ route('admin.induction.change-logs.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Clear</a>
        @endif
    </form>

    <div class="portal-card overflow-hidden">
        @if ($logs->isEmpty())
            <p class="p-6 text-sm text-muted-foreground">No change log entries found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Date &amp; time</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Changed by</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Policy</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Action</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Summary</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-foreground">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($logs as $log)
                            @php $presenter = new \App\Support\InductionChangeLogPresenter($log); @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                    {{ $log->created_at?->timezone(config('app.timezone'))->format('d M Y, g:i A') }}
                                </td>
                                <td class="px-4 py-3 font-medium text-foreground">{{ $presenter->actorName() }}</td>
                                <td class="px-4 py-3 text-foreground">{{ $presenter->policyLabel() }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-foreground">{{ $presenter->actionLabel() }}</span>
                                    <span class="mt-0.5 block text-xs text-muted-foreground">{{ $presenter->subjectLabel() }}</span>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-muted-foreground">
                                    @php $changes = $presenter->changes(); @endphp
                                    @if ($changes === [])
                                        <span class="text-xs">—</span>
                                    @else
                                        <ul class="space-y-1 text-xs">
                                            @foreach (array_slice($changes, 0, 2) as $change)
                                                <li><span class="font-medium text-foreground">{{ $change['label'] }}:</span> {{ $change['from'] ?? '—' }} → {{ $change['to'] ?? '—' }}</li>
                                            @endforeach
                                            @if (count($changes) > 2)
                                                <li class="text-muted-foreground">+ {{ count($changes) - 2 }} more</li>
                                            @endif
                                        </ul>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.induction.change-logs.show', $log) }}" class="font-medium text-primary hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-border px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
