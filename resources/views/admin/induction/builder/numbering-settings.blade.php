@extends('layouts.policy-builder', ['builderPolicy' => $policy])

@section('title', 'Numbering Scheme Editor · Settings')

@section('builder-header')
<header class="flex shrink-0 items-center justify-between gap-4 border-b border-[#E2E8F0] bg-white px-4 py-3 lg:px-6">
    <p class="text-sm font-medium text-slate-500">Settings</p>
</header>
@endsection

@section('content')
@php
    $section = $scheme['section'] ?? [];
    $clause = $scheme['clause'] ?? [];
    $sub = $scheme['sub_clause'] ?? [];
@endphp

<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Numbering Scheme Editor</h1>
    </div>

    @if ($policies->count() > 1)
        <form method="GET" action="{{ route('admin.induction.settings.numbering') }}" class="mb-6">
            <label class="policy-builder-label" for="policy_select">Policy</label>
            <select id="policy_select" name="policy" class="policy-builder-input max-w-md" onchange="this.form.submit()">
                @foreach ($policies as $p)
                    <option value="{{ $p->id }}" @selected($policy?->id === $p->id)>{{ $p->abbreviation }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <form action="{{ $policy ? route('admin.induction.settings.numbering.update', $policy) : route('admin.induction.settings.numbering.update.global') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-4 rounded-xl border border-[#E2E8F0] bg-white p-5 sm:grid-cols-3">
            <div>
                <label class="policy-builder-label">Section style</label>
                <select name="scheme[section][style]" class="policy-builder-input">
                    <option value="roman" @selected(($section['style'] ?? '') === 'roman')>I, II, III</option>
                    <option value="decimal" @selected(($section['style'] ?? '') === 'decimal')>1, 2, 3</option>
                </select>
            </div>
            <div>
                <label class="policy-builder-label">Section separator</label>
                <input name="scheme[section][separator]" type="text" class="policy-builder-input" value="{{ $section['separator'] ?? '.' }}" maxlength="16">
            </div>
            <div>
                <label class="policy-builder-label">Section start</label>
                <input name="scheme[section][start]" type="text" class="policy-builder-input" value="{{ $section['start'] ?? 'I' }}" maxlength="16">
            </div>
        </div>

        <div class="policy-builder-level-card is-expanded">
            <p class="font-semibold text-[#4F46E5]">Clause Level (Nested)</p>
            <p class="text-xs text-slate-500">Custom numbering</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="policy-builder-label">Numbering style</label>
                    <select name="scheme[clause][style]" class="policy-builder-input">
                        <option value="alpha_upper" @selected(($clause['style'] ?? '') === 'alpha_upper')>A, B, C</option>
                        <option value="decimal" @selected(($clause['style'] ?? '') === 'decimal')>1, 2, 3</option>
                        <option value="roman" @selected(($clause['style'] ?? '') === 'roman')>I, II, III</option>
                    </select>
                </div>
                <div>
                    <label class="policy-builder-label">Separator</label>
                    <input name="scheme[clause][separator]" type="text" class="policy-builder-input" value="{{ $clause['separator'] ?? '.' }}" maxlength="16">
                </div>
                <div>
                    <label class="policy-builder-label">Starting number</label>
                    <input name="scheme[clause][start]" type="text" class="policy-builder-input" value="{{ $clause['start'] ?? 'A' }}" maxlength="16">
                </div>
                <div>
                    <label class="policy-builder-label">Inherit preview</label>
                    <input name="scheme[clause][inherit_preview]" type="text" class="policy-builder-input" value="{{ $clause['inherit_preview'] ?? 'II.A' }}" maxlength="32">
                </div>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border border-[#E2E8F0] bg-slate-50 p-5 sm:grid-cols-3">
            <div>
                <label class="policy-builder-label text-slate-500">Sub-clause style</label>
                <select name="scheme[sub_clause][style]" class="policy-builder-input">
                    <option value="decimal" @selected(($sub['style'] ?? '') === 'decimal')>1, 2, 3</option>
                    <option value="alpha_lower" @selected(($sub['style'] ?? '') === 'alpha_lower')>i, ii, iii</option>
                </select>
            </div>
            <div>
                <label class="policy-builder-label text-slate-500">Sub-clause separator</label>
                <input name="scheme[sub_clause][separator]" type="text" class="policy-builder-input" value="{{ $sub['separator'] ?? '.' }}" maxlength="16">
            </div>
            <div>
                <label class="policy-builder-label text-slate-500">Default prefix</label>
                <input name="scheme[sub_clause][prefix]" type="text" class="policy-builder-input" value="{{ $sub['prefix'] ?? '' }}" placeholder="REG-" maxlength="32">
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            @if ($policy)
                <button type="submit" class="policy-builder-btn policy-builder-btn--primary">Save for {{ $policy->abbreviation }}</button>
                <button type="submit" name="apply_all" value="1" class="policy-builder-btn policy-builder-btn--secondary">Apply to All Policies</button>
            @else
                <button type="submit" name="apply_all" value="1" class="policy-builder-btn policy-builder-btn--primary">Apply to All Policies</button>
            @endif
        </div>
    </form>

    <div class="mt-10 space-y-4" aria-hidden="true">
        <div class="policy-builder-level-row opacity-60">
            <span class="text-slate-500">☰</span>
            <div class="grid flex-1 gap-3 sm:grid-cols-2">
                <input type="text" class="policy-builder-input bg-white" value="I, II, III" readonly>
                <input type="text" class="policy-builder-input bg-white" value="." readonly>
            </div>
        </div>
        <div class="policy-builder-level-card is-expanded">
            <p class="font-semibold text-[#4F46E5]">Clause Level (Nested)</p>
            <p class="text-xs text-slate-500">Custom numbering</p>
        </div>
        <div class="policy-builder-level-card is-muted">
            <p class="font-semibold text-slate-500">Sub-clause Level</p>
            <p class="text-xs text-slate-400">Sub-clause numbering</p>
        </div>
    </div>
</div>
@endsection
