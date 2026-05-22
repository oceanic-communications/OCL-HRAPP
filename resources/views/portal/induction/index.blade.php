@extends('layouts.portal')

@section('title', 'Induction · '.config('app.name'))

@section('content')
@php
    $sections = $version->activeSections;
    $completed = collect($completedSectionIds);
    $total = $sections->count();
    $doneCount = $completed->count();
    $pct = $total > 0 ? (int) round(($doneCount / $total) * 100) : 0;
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h1 class="font-heading text-pretty text-xl font-bold text-foreground sm:text-2xl lg:text-3xl">Induction training</h1>
                <p class="mt-1 text-sm text-muted-foreground sm:text-base">{{ $version->policy->name }} — {{ $version->version_label }}. Complete each section in order.</p>
                @if ($version->policy_pdf_path && $version->policy_pdf_disk)
                    <p class="mt-3">
                        <a href="{{ route('portal.induction.master-pdf', $version) }}" class="text-sm font-medium text-primary underline">Download master policy PDF</a>
                    </p>
                @endif
            </div>
            <div class="flex w-full max-w-sm shrink-0 items-center gap-4 rounded-xl border border-border bg-muted/30 p-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-secondary/20 text-lg font-bold text-secondary">{{ $pct }}%</div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-foreground">Overall progress</p>
                    <p class="text-xs text-muted-foreground">{{ $doneCount }} of {{ $total }} completed</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($enrollment->isCompleted())
        <div class="portal-card border-2 border-accent/40 bg-accent/5 p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-heading text-lg font-semibold text-foreground">Induction complete</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Your acknowledgement PDF was generated and emailed to you{{ config('induction.hr_notification_email') ? ' and HR' : '' }}.</p>
                </div>
                <a href="{{ route('portal.induction.certificate') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Download PDF</a>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @foreach ($sections as $idx => $section)
            @php
                $isDone = $completed->contains($section->id);
                $prev = $idx > 0 ? $sections->get($idx - 1) : null;
                $locked = $prev && ! $completed->contains($prev->id);
            @endphp
            <div class="portal-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5 {{ $locked ? 'opacity-60' : '' }}">
                <div class="min-w-0 flex items-start gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $isDone ? 'bg-accent text-accent-foreground' : ($locked ? 'bg-muted text-muted-foreground' : 'bg-secondary/20 text-secondary') }}">
                        @if ($isDone)
                            ✓
                        @elseif ($locked)
                            —
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="font-medium text-foreground">{{ $section->title }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ \App\Support\InductionAcknowledgementMode::label(
                                $section->requiresSignatureForCompletion()
                                    ? \App\Support\InductionAcknowledgementMode::READ_AND_SIGN
                                    : \App\Support\InductionAcknowledgementMode::READ_ONLY
                            ) }}
                        </p>
                    </div>
                </div>
                <div class="shrink-0">
                    @if ($isDone)
                        <span class="text-sm font-medium text-accent">Completed</span>
                    @elseif ($locked)
                        <span class="text-sm text-muted-foreground">Locked</span>
                    @else
                        <a href="{{ route('portal.induction.section', $section) }}" class="inline-flex rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Open</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
