@extends('layouts.portal')

@section('title', $section->title.' · Induction · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
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

    <div>
        <a href="{{ route('portal.induction') }}" class="text-sm font-medium text-primary hover:underline">← Back to induction</a>
        <h1 class="font-heading mt-2 text-pretty text-xl font-bold text-foreground sm:text-2xl">{{ $section->title }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">Policy version: {{ $version->version_label }}</p>
    </div>

    <div class="portal-card p-4 sm:p-6">
        <div class="max-w-none whitespace-pre-wrap text-sm leading-relaxed text-foreground">
            {{ $section->body }}
        </div>
    </div>

    <form action="{{ route('portal.induction.section.complete', $section) }}" method="POST" class="portal-card space-y-5 p-4 sm:p-6" data-induction-form novalidate>
        @csrf
        <div class="flex items-start gap-3">
            <input type="checkbox" name="acknowledge" id="acknowledge" value="1" class="mt-1 h-4 w-4 rounded border-border text-primary" {{ old('acknowledge') ? 'checked' : '' }} required>
            <label for="acknowledge" class="text-sm text-foreground">
                I confirm that I have read and understood this section, and that the information I provide below is accurate.
            </label>
        </div>

        @if ($section->requires_signature)
            <div data-induction-signature class="space-y-2">
                @if ($section->acknowledgement_hint)
                    <p class="text-sm text-muted-foreground">{{ $section->acknowledgement_hint }}</p>
                @endif
                <p class="text-sm font-medium text-foreground">Digital signature (draw below)</p>
                <div class="rounded-lg border border-border bg-white p-2">
                    <canvas data-induction-signature-canvas class="block w-full max-w-full touch-none" style="height:160px" aria-label="Signature pad"></canvas>
                </div>
                <input type="hidden" name="signature_data" value="" data-induction-signature-output>
                <button type="button" class="text-sm font-medium text-primary hover:underline" data-induction-signature-clear>Clear signature</button>
            </div>
        @endif

        <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 sm:w-auto">
            Submit acknowledgement
        </button>
    </form>
</div>
@endsection
