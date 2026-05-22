@extends('layouts.portal')

@section('title', $policy->abbreviation.' · Document builder · '.config('app.name'))

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

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.induction.policies.show', $policy) }}" class="text-sm font-medium text-primary hover:underline">← Back to {{ $policy->name }}</a>
            <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">
                <span class="text-primary">{{ $policy->abbreviation }}</span>
                <span class="text-muted-foreground">·</span>
                Document builder
            </h1>
            <p class="text-sm text-muted-foreground">Edit clauses and sub-clauses in one place. Changes save when you submit each form.</p>
        </div>
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="shrink-0 rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Done</a>
    </div>

    <div
        class="induction-builder"
        data-policy-builder
        data-active-node="{{ $activeType }}-{{ $activeId }}"
        data-clause-part="{{ $activeSub ? e($clausePart) : '' }}"
        data-sub-title="{{ $activeSub ? e($activeSub->title) : '' }}"
    >
        <div class="induction-builder-split">
            <div class="induction-builder-tree space-y-3">
                <div class="portal-card p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-foreground">{{ $policy->name }}</p>
                            <p class="text-xs text-muted-foreground">Policy · {{ $sectionLabel }}</p>
                        </div>
                    </div>
                </div>

                @forelse ($clauses as $ci => $clause)
                    @php $clauseLabel = $clauseLabels[$clause->id] ?? ('Clause '.($ci + 1)); @endphp
                    <div class="induction-builder-branch">
                        <a
                            href="{{ route('admin.induction.policies.builder', ['policy' => $policy, 'node' => 'clause-'.$clause->id]) }}"
                            class="induction-builder-node portal-card block p-4 {{ $activeType === 'clause' && $activeId === $clause->id ? 'is-active' : '' }}"
                        >
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 6H5.25m7.5 6H15M9 3.75H5.625A2.25 2.25 0 0 0 3.375 6v11.25A2.25 2.25 0 0 0 5.625 19.5h12.75a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H15a2.25 2.25 0 0 0-2.25 2.25v.75"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-foreground">{{ $clause->title }}</p>
                                    <p class="text-xs text-muted-foreground">Clause · {{ $clauseLabel }}</p>
                                </div>
                            </div>
                        </a>

                        @if ($clause->activeSubClauses->isNotEmpty())
                            <div class="induction-builder-children">
                                @foreach ($clause->activeSubClauses as $si => $sub)
                                    @php
                                        $subLabel = ($ci + 1).'.'.($si + 1);
                                        $isActive = $activeType === 'sub_clause' && $activeId === $sub->id;
                                    @endphp
                                    <a
                                        href="{{ route('admin.induction.policies.builder', ['policy' => $policy, 'node' => 'sub-clause-'.$sub->id]) }}"
                                        class="induction-builder-sub {{ $isActive ? 'is-active' : '' }}"
                                        data-sub-clause-node
                                        data-sub-id="{{ $sub->id }}"
                                        data-sub-title="{{ $sub->title }}"
                                        data-clause-part="{{ $clauseLabels[$clause->id] ?? '' }}"
                                        data-prefix="{{ $sub->number_prefix ?? ($scheme['sub_clause']['prefix'] ?? '') }}"
                                        data-style="{{ $sub->numbering_style ?? ($scheme['sub_clause']['style'] ?? 'decimal') }}"
                                        data-separator="{{ $sub->number_separator ?? ($scheme['sub_clause']['separator'] ?? '.') }}"
                                    >
                                        <span class="induction-builder-pill">{{ $subLabel }}</span>
                                        <span class="min-w-0 flex-1 truncate font-medium text-foreground">{{ $sub->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">No clauses yet. Add a clause to build your document.</p>
                @endforelse

                @if ($portalCap?->inductionPolicyCreate ?? false)
                    <a href="{{ route('admin.induction.policies.clauses.create', $policy) }}" class="induction-builder-add-clause">
                        + Add clause
                    </a>
                @endif
            </div>

            <div class="induction-builder-panel portal-card p-5">
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
                            <label class="portal-label" for="editor_title">Title</label>
                            <input id="editor_title" name="title" type="text" class="portal-input mt-1" value="{{ old('title', $activeSub->title) }}" required maxlength="255">
                        </div>
                        <x-rich-editor name="body" :value="old('body', $activeSub->body)" :max-words="\App\Models\InductionSubClause::BODY_MAX_WORDS" label="Content" :rows="14" required />
                        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Save sub-clause</button>
                    </form>

                    @if ($portalCap?->inductionPolicyUpdate ?? false)
                        <form
                            action="{{ route('admin.induction.policies.clauses.sub-clauses.numbering', [$policy, $activeClause, $activeSub]) }}"
                            method="POST"
                            class="mt-6 space-y-4 rounded-lg border border-border bg-muted/20 p-4"
                            data-numbering-popover
                            id="numbering-popover"
                        >
                            @csrf
                            @method('PUT')
                            <h2 class="text-sm font-semibold text-foreground">Custom numbering</h2>
                            <div>
                                <label class="portal-label" for="number_prefix">Prefix</label>
                                <input id="number_prefix" name="number_prefix" type="text" class="portal-input mt-1" value="{{ old('number_prefix', $activeSub->number_prefix ?? ($scheme['sub_clause']['prefix'] ?? '')) }}" placeholder="REG-" maxlength="32" data-numbering-prefix>
                            </div>
                            <div>
                                <label class="portal-label" for="numbering_style">Numbering style</label>
                                <select id="numbering_style" name="numbering_style" class="portal-input mt-1" data-numbering-style>
                                    <option value="decimal" @selected(($activeSub->numbering_style ?? 'decimal') === 'decimal')>1, 2, 3</option>
                                    <option value="alpha_upper" @selected(($activeSub->numbering_style ?? '') === 'alpha_upper')>A, B, C</option>
                                    <option value="alpha_lower" @selected(($activeSub->numbering_style ?? '') === 'alpha_lower')>i, ii, iii</option>
                                    <option value="roman" @selected(($activeSub->numbering_style ?? '') === 'roman')>I, II, III</option>
                                </select>
                            </div>
                            <div>
                                <label class="portal-label" for="number_separator">Separator</label>
                                <select id="number_separator" name="number_separator" class="portal-input mt-1" data-numbering-separator>
                                    <option value="." @selected(($activeSub->number_separator ?? '.') === '.')>period '.'</option>
                                    <option value=")" @selected(($activeSub->number_separator ?? '') === ')')>parenthesis ')'</option>
                                    <option value="" @selected(($activeSub->number_separator ?? '') === '')>none</option>
                                </select>
                            </div>
                            <p class="rounded-lg border border-border bg-card px-3 py-2 text-xs text-muted-foreground">
                                Preview: <strong data-numbering-preview class="text-foreground">{{ $activeNode['label'] }} {{ $activeSub->title }}</strong>
                            </p>
                            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Apply numbering</button>
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
                            <label class="portal-label" for="editor_title">Title</label>
                            <input id="editor_title" name="title" type="text" class="portal-input mt-1" value="{{ old('title', $activeClause->title) }}" required maxlength="255">
                        </div>
                        <x-rich-editor name="body" :value="old('body', $activeClause->body)" :max-words="\App\Models\InductionSection::BODY_MAX_WORDS" label="Content" :rows="14" required />
                        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Save clause</button>
                    </form>
                    @if ($portalCap?->inductionPolicyCreate ?? false)
                        <a href="{{ route('admin.induction.policies.clauses.sub-clauses.create', [$policy, $activeClause]) }}" class="mt-4 inline-flex rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">+ Add sub-clause</a>
                    @endif
                @else
                    <p class="text-sm text-muted-foreground">Select a clause or sub-clause in the document tree to edit its content.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
