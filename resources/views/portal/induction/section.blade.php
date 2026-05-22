@extends('layouts.portal')

@section('title', $section->title.' · Induction · '.config('app.name'))

@section('content')
@php
    $acknowledgementAt = now()->timezone(config('app.timezone'));
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5" aria-label="Induction progress">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-secondary/20 text-base font-bold text-secondary" aria-hidden="true">{{ $progressPct }}%</div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground">Your progress</p>
                    <p class="text-xs text-muted-foreground">{{ $progressDone }} of {{ $progressTotal }} sections completed{{ $progressStep > 0 ? ' · Step '.$progressStep.' of '.$progressTotal : '' }}</p>
                </div>
            </div>
            <div class="w-full min-w-0 sm:max-w-xs sm:flex-1">
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Induction completion {{ $progressPct }} percent">
                    <div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $progressPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('portal.induction') }}" class="text-sm font-medium text-primary hover:underline">← Back to induction overview</a>
            <p class="mt-1 text-sm text-muted-foreground">{{ $version->policy->name }} · {{ $version->version_label }}</p>
        </div>
        @if ($version->policy_pdf_path && $version->policy_pdf_disk)
            <a href="{{ route('portal.induction.master-pdf', $version) }}" class="text-sm font-medium text-primary underline">Download master policy PDF</a>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[min(100%,360px)_1fr] lg:items-start">
        @include('portal.induction.partials.sections-sidebar', [
            'sections' => $sections,
            'section' => $section,
            'completedSectionIds' => $completedSectionIds,
        ])

        <div class="min-w-0 space-y-6">
            <div class="portal-card overflow-hidden">
                <div class="border-b border-border bg-muted/30 px-4 py-4 sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h1 class="font-heading text-pretty text-xl font-bold text-foreground sm:text-2xl">{{ $section->title }}</h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                @if ($sectionCompleted)
                                    <span class="font-medium text-accent">Completed</span>
                                @else
                                    <span class="font-medium text-secondary">In progress</span>
                                    @if ($section->requiresSignatureForCompletion())
                                        · {{ \App\Support\InductionAcknowledgementMode::label(\App\Support\InductionAcknowledgementMode::READ_AND_SIGN) }}
                                    @else
                                        · {{ \App\Support\InductionAcknowledgementMode::label(\App\Support\InductionAcknowledgementMode::READ_ONLY) }}
                                    @endif
                                @endif
                            </p>
                        </div>
                        @if ($sectionCompleted)
                            <span class="inline-flex shrink-0 rounded-full bg-accent/15 px-2.5 py-0.5 text-xs font-semibold text-accent">Done</span>
                        @endif
                    </div>
                </div>
                <div class="max-h-[min(60vh,32rem)] overflow-y-auto p-4 sm:p-6 lg:max-h-[calc(100dvh-22rem)]">
                    <div class="rich-html-content max-w-none text-sm leading-relaxed text-foreground">
                        {!! \App\Support\RichHtmlPurifier::purify($section->body) !!}
                    </div>

                    @if ($section->activeSubClauses->isNotEmpty())
                        <div class="mt-8 space-y-6 border-t border-border pt-6">
                            @foreach ($section->activeSubClauses as $subClause)
                                <article class="space-y-2">
                                    <h2 class="font-heading text-base font-semibold text-foreground">{{ $loop->iteration }}. {{ $subClause->title }}</h2>
                                    <div class="rich-html-content text-sm leading-relaxed text-foreground">
                                        {!! \App\Support\RichHtmlPurifier::purify($subClause->body) !!}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($sectionCompleted)
                <div class="portal-card border-accent/30 bg-accent/5 p-4 sm:p-5">
                    <p class="text-sm text-foreground">You have already completed this section. Open the next available section from the list, or return to the <a href="{{ route('portal.induction') }}" class="font-medium text-primary underline">induction overview</a>.</p>
                </div>
            @else
                <form action="{{ route('portal.induction.section.complete', $section) }}" method="POST" class="portal-card space-y-5 p-4 sm:p-6" data-induction-form novalidate>
                    @csrf

                    <div class="flex items-start gap-3 rounded-xl border border-border bg-card p-4">
                        <input
                            type="checkbox"
                            name="acknowledge"
                            id="acknowledge"
                            value="1"
                            class="mt-1 h-4 w-4 rounded border-border text-primary"
                            data-induction-acknowledge
                            {{ old('acknowledge') ? 'checked' : '' }}
                            required
                        >
                        <label for="acknowledge" class="text-sm leading-relaxed text-foreground">
                            I confirm that I have read and understood this section. I agree to comply with the policies and procedures outlined above.
                        </label>
                    </div>
                    @error('acknowledge')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    @if ($section->requiresSignatureForCompletion())
                        <div data-induction-signature class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2 sm:col-span-2">
                                    <p class="text-sm font-medium text-foreground">Digital signature</p>
                                    <p class="text-xs text-muted-foreground">Draw your signature in the box below.</p>
                                    <div class="rounded-lg border border-border bg-white p-2">
                                        <canvas data-induction-signature-canvas class="block w-full max-w-full touch-none" style="height:160px" aria-label="Signature pad"></canvas>
                                    </div>
                                    <input type="hidden" name="signature_data" value="" data-induction-signature-output>
                                    <button type="button" class="text-sm font-medium text-primary hover:underline" data-induction-signature-clear>Clear signature</button>
                                    @error('signature_data')
                                        <p class="text-sm text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="portal-label" for="acknowledgement_timestamp">Date &amp; time</label>
                                    <input
                                        type="text"
                                        id="acknowledgement_timestamp"
                                        class="portal-input bg-muted/50"
                                        value="{{ $acknowledgementAt->format('d M Y, g:i A') }}"
                                        readonly
                                        tabindex="-1"
                                        aria-readonly="true"
                                    >
                                </div>
                            </div>
                        </div>
                    @endif

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        data-induction-submit
                        disabled
                    >
                        Submit Acknowledgement
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
