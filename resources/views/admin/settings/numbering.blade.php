@extends('layouts.portal')

@section('title', 'Policy numbering · Settings · '.config('app.name'))

@section('content')
@php
    $section = $scheme['section'] ?? [];
    $clause = $scheme['clause'] ?? [];
    $sub = $scheme['sub_clause'] ?? [];
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.settings.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to settings</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Policy numbering</h1>
        <p class="text-sm text-muted-foreground">Configure how sections, clauses, and sub-clauses are numbered across induction policies.</p>
    </div>

    @if ($policies->count() > 1)
        <form method="GET" action="{{ route('admin.settings.numbering') }}" class="portal-card p-5">
            <label class="portal-label" for="policy_select">Policy</label>
            <select id="policy_select" name="policy" class="portal-input mt-1 max-w-md" onchange="this.form.submit()">
                @foreach ($policies as $p)
                    <option value="{{ $p->id }}" @selected($policy?->id === $p->id)>{{ $p->abbreviation }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <form
        action="{{ $policy ? route('admin.settings.numbering.update', $policy) : route('admin.settings.numbering.update.global') }}"
        method="POST"
        class="portal-card space-y-6 p-5"
    >
        @csrf
        @method('PUT')

        <div>
            <h2 class="text-sm font-semibold text-foreground">Section level</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="portal-label">Style</label>
                    <select name="scheme[section][style]" class="portal-input mt-1">
                        <option value="roman" @selected(($section['style'] ?? '') === 'roman')>I, II, III</option>
                        <option value="decimal" @selected(($section['style'] ?? '') === 'decimal')>1, 2, 3</option>
                    </select>
                </div>
                <div>
                    <label class="portal-label">Separator</label>
                    <input name="scheme[section][separator]" type="text" class="portal-input mt-1" value="{{ $section['separator'] ?? '.' }}" maxlength="16">
                </div>
                <div>
                    <label class="portal-label">Start</label>
                    <input name="scheme[section][start]" type="text" class="portal-input mt-1" value="{{ $section['start'] ?? 'I' }}" maxlength="16">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-border bg-muted/20 p-4">
            <h2 class="text-sm font-semibold text-foreground">Clause level</h2>
            <p class="mt-1 text-xs text-muted-foreground">Nested under each policy section.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="portal-label">Numbering style</label>
                    <select name="scheme[clause][style]" class="portal-input mt-1">
                        <option value="alpha_upper" @selected(($clause['style'] ?? '') === 'alpha_upper')>A, B, C</option>
                        <option value="decimal" @selected(($clause['style'] ?? '') === 'decimal')>1, 2, 3</option>
                        <option value="roman" @selected(($clause['style'] ?? '') === 'roman')>I, II, III</option>
                    </select>
                </div>
                <div>
                    <label class="portal-label">Separator</label>
                    <input name="scheme[clause][separator]" type="text" class="portal-input mt-1" value="{{ $clause['separator'] ?? '.' }}" maxlength="16">
                </div>
                <div>
                    <label class="portal-label">Starting number</label>
                    <input name="scheme[clause][start]" type="text" class="portal-input mt-1" value="{{ $clause['start'] ?? 'A' }}" maxlength="16">
                </div>
                <div>
                    <label class="portal-label">Inherit preview</label>
                    <input name="scheme[clause][inherit_preview]" type="text" class="portal-input mt-1" value="{{ $clause['inherit_preview'] ?? 'II.A' }}" maxlength="32">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-foreground">Sub-clause level</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="portal-label">Style</label>
                    <select name="scheme[sub_clause][style]" class="portal-input mt-1">
                        <option value="decimal" @selected(($sub['style'] ?? '') === 'decimal')>1, 2, 3</option>
                        <option value="alpha_lower" @selected(($sub['style'] ?? '') === 'alpha_lower')>i, ii, iii</option>
                    </select>
                </div>
                <div>
                    <label class="portal-label">Separator</label>
                    <input name="scheme[sub_clause][separator]" type="text" class="portal-input mt-1" value="{{ $sub['separator'] ?? '.' }}" maxlength="16">
                </div>
                <div>
                    <label class="portal-label">Default prefix</label>
                    <input name="scheme[sub_clause][prefix]" type="text" class="portal-input mt-1" value="{{ $sub['prefix'] ?? '' }}" placeholder="REG-" maxlength="32">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-border pt-4">
            @if ($policy && ($portalCap?->inductionPolicyUpdate ?? false))
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Save for {{ $policy->abbreviation }}</button>
                <button type="submit" name="apply_all" value="1" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Apply to all policies</button>
            @elseif ($portalCap?->inductionPolicyUpdate ?? false)
                <button type="submit" name="apply_all" value="1" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Apply to all policies</button>
            @endif
        </div>
    </form>
</div>
@endsection
