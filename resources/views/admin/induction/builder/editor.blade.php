@extends('layouts.policy-builder', ['builderPolicy' => $policy])

@section('title', $policy->abbreviation.' Policy · Document builder')

@section('builder-header')
<header class="flex shrink-0 items-center justify-between gap-4 border-b border-[#E2E8F0] bg-white px-4 py-3 lg:px-6">
    <div class="min-w-0">
        <h1 class="truncate text-lg font-semibold text-slate-900">{{ $policy->abbreviation }} Policy</h1>
        <p class="text-xs text-slate-500">{{ $policy->name }}</p>
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="policy-builder-btn policy-builder-btn--secondary">Save</a>
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="policy-builder-btn policy-builder-btn--primary">Publish</a>
    </div>
</header>
@endsection

@section('content')
@php
    $activeType = $activeNode['type'];
    $activeId = $activeNode['id'];
    $activeSub = $activeType === 'sub_clause' ? $activeNode['model'] : null;
    $activeClause = $activeType === 'clause' ? $activeNode['model'] : ($activeNode['clause'] ?? null);
    $clausePart = $activeSub && $activeClause
        ? ($clauseLabels[$activeClause->id] ?? '')
        : '';
@endphp

<div
    class="policy-builder-workspace"
    data-policy-builder
    data-active-node="{{ $activeType }}-{{ $activeId }}"
    data-clause-part="{{ $activeSub ? e($clausePart) : '' }}"
    data-sub-title="{{ $activeSub ? e($activeSub->title) : '' }}"
>
    <div class="policy-builder-split">
        {{-- Document tree --}}
        <div class="policy-builder-tree">
            <div class="policy-builder-card policy-builder-card--level-1">
                <div class="flex items-start gap-3">
                    <span class="policy-builder-icon policy-builder-icon--doc" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">{{ $policy->name }}</p>
                        <p class="text-xs text-slate-500">Policy Section · {{ $sectionLabel }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Options">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 14a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/></svg>
                    </button>
                </div>
            </div>

            @forelse ($clauses as $ci => $clause)
                @php $clauseLabel = $clauseLabels[$clause->id] ?? ('Clause '.($ci + 1)); @endphp
                <div class="policy-builder-tree-branch">
                    <a
                        href="{{ route('admin.induction.policies.builder', ['policy' => $policy, 'node' => 'clause-'.$clause->id]) }}"
                        class="policy-builder-card policy-builder-card--level-2 {{ $activeType === 'clause' && $activeId === $clause->id ? 'is-active' : '' }}"
                    >
                        <div class="flex items-start gap-3">
                            <span class="policy-builder-icon policy-builder-icon--text" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 6H5.25m7.5 6H15M9 3.75H5.625A2.25 2.25 0 0 0 3.375 6v11.25A2.25 2.25 0 0 0 5.625 19.5h12.75a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H15a2.25 2.25 0 0 0-2.25 2.25v.75"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ $clause->title }}</p>
                                <p class="text-xs text-slate-500">Clause · {{ $clauseLabel }}</p>
                            </div>
                            <span class="text-slate-400" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                            </span>
                        </div>
                    </a>

                    @if ($clause->activeSubClauses->isNotEmpty())
                        <div class="policy-builder-tree-children">
                            @foreach ($clause->activeSubClauses as $si => $sub)
                                @php
                                    $subLabel = ($ci + 1).'.'.($si + 1);
                                    $isActive = $activeType === 'sub_clause' && $activeId === $sub->id;
                                @endphp
                                <a
                                    href="{{ route('admin.induction.policies.builder', ['policy' => $policy, 'node' => 'sub-clause-'.$sub->id]) }}"
                                    class="policy-builder-node-sub {{ $isActive ? 'is-active' : '' }}"
                                    data-sub-clause-node
                                    data-sub-id="{{ $sub->id }}"
                                    data-sub-title="{{ $sub->title }}"
                                    data-clause-part="{{ $clauseLabels[$clause->id] ?? '' }}"
                                    data-prefix="{{ $sub->number_prefix ?? ($scheme['sub_clause']['prefix'] ?? '') }}"
                                    data-style="{{ $sub->numbering_style ?? ($scheme['sub_clause']['style'] ?? 'decimal') }}"
                                    data-separator="{{ $sub->number_separator ?? ($scheme['sub_clause']['separator'] ?? '.') }}"
                                >
                                    <span class="policy-builder-pill">{{ $subLabel }}</span>
                                    <span class="min-w-0 flex-1 truncate font-medium">{{ $sub->title }}</span>
                                    @if ($isActive)
                                        <span class="text-xs text-[#4F46E5]">Sub-clauses</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No clauses yet. Add a clause to build your document.</p>
            @endforelse

            @if ($portalCap?->inductionPolicyCreate ?? false)
                <a href="{{ route('admin.induction.policies.clauses.create', $policy) }}" class="policy-builder-add-clause">
                    + Add Clause
                </a>
            @endif
        </div>

        {{-- Editor panel --}}
        <div class="policy-builder-editor">
            @if ($activeType === 'sub_clause' && $activeSub && $activeClause)
                <form
                    action="{{ route('admin.induction.policies.clauses.sub-clauses.update', [$policy, $activeClause, $activeSub]) }}"
                    method="POST"
                    class="space-y-4"
                    onsubmit="if (window.tinymce) { window.tinymce.triggerSave(); }"
                >
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="policy-builder-label" for="editor_title">Policy Title</label>
                        <input id="editor_title" name="title" type="text" class="policy-builder-input" value="{{ old('title', $activeSub->title) }}" required maxlength="255">
                    </div>
                    <x-rich-editor name="body" :value="old('body', $activeSub->body)" :max-words="\App\Models\InductionSubClause::BODY_MAX_WORDS" label="Content" :rows="14" required />
                    <button type="submit" class="policy-builder-btn policy-builder-btn--primary w-full sm:w-auto">Save sub-clause</button>
                </form>

                @if ($portalCap?->inductionPolicyUpdate ?? false)
                    <form
                        action="{{ route('admin.induction.policies.clauses.sub-clauses.numbering', [$policy, $activeClause, $activeSub]) }}"
                        method="POST"
                        class="policy-builder-popover mt-6"
                        data-numbering-popover
                        id="numbering-popover"
                    >
                        @csrf
                        @method('PUT')
                        <p class="mb-3 text-sm font-semibold text-slate-900">Custom numbering</p>
                        <div class="space-y-3">
                            <div>
                                <label class="policy-builder-label" for="number_prefix">1. Prefix</label>
                                <input id="number_prefix" name="number_prefix" type="text" class="policy-builder-input" value="{{ old('number_prefix', $activeSub->number_prefix ?? ($scheme['sub_clause']['prefix'] ?? '')) }}" placeholder="REG-" maxlength="32" data-numbering-prefix>
                            </div>
                            <div>
                                <label class="policy-builder-label" for="numbering_style">2. Numbering style</label>
                                <select id="numbering_style" name="numbering_style" class="policy-builder-input" data-numbering-style>
                                    <option value="decimal" @selected(($activeSub->numbering_style ?? 'decimal') === 'decimal')>1, 2, 3</option>
                                    <option value="alpha_upper" @selected(($activeSub->numbering_style ?? '') === 'alpha_upper')>A, B, C</option>
                                    <option value="alpha_lower" @selected(($activeSub->numbering_style ?? '') === 'alpha_lower')>i, ii, iii</option>
                                    <option value="roman" @selected(($activeSub->numbering_style ?? '') === 'roman')>I, II, III</option>
                                </select>
                            </div>
                            <div>
                                <label class="policy-builder-label" for="number_separator">3. Separator</label>
                                <select id="number_separator" name="number_separator" class="policy-builder-input" data-numbering-separator>
                                    <option value="." @selected(($activeSub->number_separator ?? '.') === '.')>period '.'</option>
                                    <option value=")" @selected(($activeSub->number_separator ?? '') === ')')>parenthesis ')'</option>
                                    <option value="" @selected(($activeSub->number_separator ?? '') === '')>none</option>
                                </select>
                            </div>
                            <p class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                Result: <strong data-numbering-preview class="text-slate-900">{{ $activeNode['label'] }} {{ $activeSub->title }}</strong>
                            </p>
                        </div>
                        <button type="submit" class="policy-builder-btn policy-builder-btn--primary mt-4 w-full">Apply numbering</button>
                    </form>
                @endif
            @elseif ($activeType === 'clause' && $activeClause)
                <form
                    action="{{ route('admin.induction.policies.clauses.update', [$policy, $activeClause]) }}"
                    method="POST"
                    class="space-y-4"
                    onsubmit="if (window.tinymce) { window.tinymce.triggerSave(); }"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="staff_must_repeat_induction" value="0">
                    <div>
                        <label class="policy-builder-label" for="editor_title">Policy Title</label>
                        <input id="editor_title" name="title" type="text" class="policy-builder-input" value="{{ old('title', $activeClause->title) }}" required maxlength="255">
                    </div>
                    <x-rich-editor name="body" :value="old('body', $activeClause->body)" :max-words="\App\Models\InductionSection::BODY_MAX_WORDS" label="Content" :rows="14" required />
                    <button type="submit" class="policy-builder-btn policy-builder-btn--primary w-full sm:w-auto">Save clause</button>
                </form>
                @if ($portalCap?->inductionPolicyCreate ?? false)
                    <a href="{{ route('admin.induction.policies.clauses.sub-clauses.create', [$policy, $activeClause]) }}" class="policy-builder-btn policy-builder-btn--secondary mt-4 inline-flex">+ Add sub-clause</a>
                @endif
            @else
                <p class="text-sm text-slate-600">Select a clause or sub-clause in the document tree to edit its content.</p>
            @endif
        </div>
    </div>
</div>

@endsection
