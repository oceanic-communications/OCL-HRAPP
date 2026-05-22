@extends('layouts.portal')

@section('title', $section->title.' · '.$policy->name.' · '.config('app.name'))

@section('content')
@php
    $subClauses = $section->subClauses;
@endphp
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="text-sm font-medium text-primary hover:underline">← Back to {{ $policy->name }}</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-bold text-foreground">{{ $section->title }}</h1>
                <p class="text-sm text-muted-foreground">{{ $policy->name }}</p>
            </div>
            @if ($section->isArchived())
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">Archived</span>
            @else
                <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    <div class="portal-card space-y-3 p-5">
        <p class="text-sm text-muted-foreground">
            Digital signature:
            @if ($section->requires_signature)
                <span class="font-medium text-foreground">Required</span>
            @else
                <span class="font-medium text-foreground">Not required</span>
            @endif
        </p>
        <h2 class="text-sm font-semibold text-foreground">Clause content</h2>
        <div class="rich-html-content mt-3 text-sm leading-relaxed text-foreground">{!! \App\Support\RichHtmlPurifier::purify($section->body) !!}</div>
    </div>

    <div class="portal-card overflow-hidden">
        <div class="space-y-4 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">Sub-clauses</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Nested content shown within this clause during staff induction.</p>
                </div>
                @if (! $section->isArchived() && ($portalCap?->inductionPolicyCreate ?? false))
                    <a href="{{ route('admin.induction.policies.clauses.sub-clauses.create', [$policy, $section]) }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                        Add sub-clause
                    </a>
                @endif
            </div>

            @if ($subClauses->isEmpty())
                <p class="text-sm text-muted-foreground">No sub-clauses yet.@if (! $section->isArchived() && ($portalCap?->inductionPolicyCreate ?? false)) Add sub-clauses to break this clause into smaller parts.@endif</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Order</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Title</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($subClauses as $subClause)
                                <tr class="{{ $subClause->isArchived() ? 'bg-muted/20 text-muted-foreground' : '' }}">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $subClause->title }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($subClause->isArchived())
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">Archived</span>
                                        @else
                                            <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('admin.induction.policies.clauses.sub-clauses.show', [$policy, $section, $subClause]) }}" class="font-medium text-primary hover:underline">View</a>
                                            @if (! $subClause->isArchived() && ($portalCap?->inductionPolicyUpdate ?? false))
                                                <a href="{{ route('admin.induction.policies.clauses.sub-clauses.edit', [$policy, $section, $subClause]) }}" class="font-medium text-primary hover:underline">Edit</a>
                                            @endif
                                            @if (! $subClause->isArchived() && ($portalCap?->inductionPolicyArchive ?? false))
                                                <form action="{{ route('admin.induction.policies.clauses.sub-clauses.archive', [$policy, $section, $subClause]) }}" method="POST" class="inline" onsubmit="return confirm('Archive this sub-clause? Staff will no longer see it during induction.');">
                                                    @csrf
                                                    <button type="submit" class="font-medium text-destructive hover:underline">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        @if (! $section->isArchived() && ($portalCap?->inductionPolicyUpdate ?? false))
            <a href="{{ route('admin.induction.policies.clauses.edit', [$policy, $section]) }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Edit clause</a>
        @endif
        @if (! $section->isArchived() && ($portalCap?->inductionPolicyArchive ?? false))
            <form action="{{ route('admin.induction.policies.clauses.archive', [$policy, $section]) }}" method="POST" onsubmit="return confirm('Archive this clause and all its sub-clauses? Staff will no longer see it during induction.');">
                @csrf
                <button type="submit" class="rounded-lg border border-destructive/40 px-4 py-2 text-sm font-semibold text-destructive hover:bg-destructive/10">Archive clause</button>
            </form>
        @endif
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Back to policy</a>
    </div>
</div>
@endsection
