@extends('layouts.portal')

@section('title', 'Induction · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h1 class="font-heading text-pretty text-xl font-bold text-foreground sm:text-2xl lg:text-3xl">Induction training</h1>
                <p class="mt-1 text-sm text-muted-foreground sm:text-base">
                    Complete each policy in order, then work through its sections from top to bottom.
                </p>
            </div>
            <div class="flex w-full max-w-sm shrink-0 items-center gap-4 rounded-xl border border-border bg-muted/30 p-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-secondary/20 text-lg font-bold text-secondary">{{ $overallPct }}%</div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-foreground">Overall progress</p>
                    <p class="text-xs text-muted-foreground">{{ $overallDone }} of {{ $overallTotal }} sections completed</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $overallPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($programmes as $programme)
        @php
            $version = $programme['version'];
            $enrollment = $programme['enrollment'];
            $policyLocked = $programme['policyLocked'];
            $sections = $version->activeSections;
            $completed = collect($programme['completedSectionIds']);
            $total = $sections->count();
            $doneCount = $completed->count();
            $pct = $total > 0 ? (int) round(($doneCount / $total) * 100) : 0;
        @endphp

        <div class="space-y-3 {{ $policyLocked ? 'opacity-60' : '' }}">
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-muted px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-foreground">{{ $version->policy->abbreviation }}</span>
                            @if ($policyLocked)
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Locked</span>
                            @elseif ($enrollment->isCompleted())
                                <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Completed</span>
                            @else
                                <span class="rounded-full bg-secondary/15 px-2 py-0.5 text-xs font-medium text-secondary">In progress</span>
                            @endif
                        </div>
                        <h2 class="mt-2 font-heading text-lg font-semibold text-foreground sm:text-xl">{{ $version->policy->name }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $version->version_label }} · {{ $doneCount }} of {{ $total }} sections</p>
                        @if ($version->policy_pdf_path && $version->policy_pdf_disk)
                            <p class="mt-2">
                                <a href="{{ route('portal.induction.master-pdf', $version) }}" class="text-sm font-medium text-primary underline">Download master policy PDF</a>
                            </p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <div class="text-right">
                            <p class="text-lg font-bold text-secondary">{{ $pct }}%</p>
                            <p class="text-xs text-muted-foreground">Policy progress</p>
                        </div>
                        @if ($enrollment->isCompleted())
                            <a href="{{ route('portal.induction.certificate', $version) }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Download PDF</a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($policyLocked)
                <p class="px-1 text-sm text-muted-foreground">Complete the previous policy before starting this one.</p>
            @else
                <div class="space-y-2">
                    @foreach ($sections as $idx => $section)
                        @php
                            $isDone = $completed->contains($section->id);
                            $prev = $idx > 0 ? $sections->get($idx - 1) : null;
                            $locked = $prev && ! $completed->contains($prev->id);
                        @endphp
                        <div class="portal-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5 {{ $locked ? 'opacity-60' : '' }}">
                            <div class="flex min-w-0 items-start gap-3">
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
            @endif
        </div>
    @endforeach
</div>
@endsection
