@extends('layouts.portal')

@section('title', 'Employee progress · '.config('app.name'))

@section('content')
@php
    $version = $inductionProgress['version'] ?? null;
    $rows = $inductionProgress['rows'] ?? collect();
    $completedCount = $rows->where('status', 'completed')->count();
    $inProgressCount = $rows->where('status', 'in_progress')->count();
    $notStartedCount = $rows->where('status', 'not_started')->count();
@endphp

<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Employee progress</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Track staff induction completion. Select an employee to view acknowledgement details including date, device, and policy version.
        </p>
    </div>

    @if ($version)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="portal-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Active policy</p>
                <p class="mt-1 font-medium text-foreground">{{ $version->policy->name }}</p>
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
            <div class="portal-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Not started</p>
                <p class="mt-1 text-2xl font-bold text-muted-foreground">{{ $notStartedCount }}</p>
            </div>
        </div>
    @endif

    @include('admin.induction.partials.user-progress', [
        'inductionProgress' => $inductionProgress,
        'showDetailLinks' => true,
    ])
</div>
@endsection
