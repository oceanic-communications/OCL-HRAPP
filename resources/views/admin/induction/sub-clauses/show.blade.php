@extends('layouts.portal')

@section('title', $subClause->title.' · '.$section->title.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.policies.clauses.show', [$policy, $section]) }}" class="text-sm font-medium text-primary hover:underline">← Back to {{ $section->title }}</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-bold text-foreground">{{ $subClause->title }}</h1>
                <p class="text-sm text-muted-foreground">{{ $policy->name }} · {{ $section->title }}</p>
            </div>
            @if ($subClause->isArchived())
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
        <h2 class="text-sm font-semibold text-foreground">Content</h2>
        <div class="rich-html-content mt-3 text-sm leading-relaxed text-foreground">{!! \App\Support\RichHtmlPurifier::purify($subClause->body) !!}</div>
    </div>

    <div class="flex flex-wrap gap-3">
        @if (! $subClause->isArchived() && ($portalCap?->inductionPolicyUpdate ?? false))
            <a href="{{ route('admin.induction.policies.clauses.sub-clauses.edit', [$policy, $section, $subClause]) }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Edit sub-clause</a>
        @endif
        @if (! $subClause->isArchived() && ($portalCap?->inductionPolicyArchive ?? false))
            <form action="{{ route('admin.induction.policies.clauses.sub-clauses.archive', [$policy, $section, $subClause]) }}" method="POST" onsubmit="return confirm('Archive this sub-clause? Staff will no longer see it during induction.');">
                @csrf
                <button type="submit" class="rounded-lg border border-destructive/40 px-4 py-2 text-sm font-semibold text-destructive hover:bg-destructive/10">Archive</button>
            </form>
        @endif
        <a href="{{ route('admin.induction.policies.clauses.show', [$policy, $section]) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Back to clause</a>
    </div>
</div>
@endsection
