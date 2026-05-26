@extends('layouts.portal')

@section('title', 'Employee progress · '.config('app.name'))

@section('content')
@php
    $programmes = $inductionProgress['programmes'] ?? [];
@endphp

<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Employee progress</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Track staff induction completion per policy. Select an employee to view acknowledgement details including date, device, and policy version.
        </p>
    </div>

    @if ($programmes === [])
        <div class="portal-card p-8 text-center text-sm text-muted-foreground">
            No published active induction policies. Publish and activate policies to track staff progress.
        </div>
    @else
        @foreach ($programmes as $programme)
            @php
                $version = $programme['version'];
                $rows = $programme['rows'];
                $completedCount = $rows->where('status', 'completed')->count();
                $inProgressCount = $rows->where('status', 'in_progress')->count();
                $notStartedCount = $rows->where('status', 'not_started')->count();
            @endphp

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="portal-card p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Policy</p>
                        <p class="mt-1 font-medium text-foreground">{{ $version->policy->name }} ({{ $version->policy->abbreviation }})</p>
                        <p class="text-xs text-muted-foreground">Version {{ $version->version_label }}</p>
                    </div>
                    <div class="portal-card p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Completed</p>
                        <p class="mt-1 text-2xl font-bold text-success">{{ $completedCount }}</p>
                    </div>
                    <div class="portal-card p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">In progress</p>
                        <p class="mt-1 text-2xl font-bold text-warning-foreground">{{ $inProgressCount }}</p>
                    </div>
                </div>

                @include('admin.induction.partials.user-progress', [
                    'inductionProgress' => [
                        'version' => $version,
                        'total_sections' => $programme['total_sections'],
                        'rows' => $rows,
                    ],
                    'showDetailLinks' => true,
                    'heading' => $version->policy->abbreviation.' — all employees',
                ])
            </div>
        @endforeach
    @endif
</div>
@endsection
