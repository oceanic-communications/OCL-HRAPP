@extends('layouts.portal')

@section('title', 'Edit '.$subClause->title.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.policies.clauses.sub-clauses.show', [$policy, $section, $subClause]) }}" class="text-sm font-medium text-primary hover:underline">← Back to sub-clause</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Edit sub-clause</h1>
        <p class="text-sm text-muted-foreground">{{ $policy->name }} · {{ $section->title }}</p>
    </div>

    @if ($errors->has('staff_must_repeat_induction'))
        <div class="portal-card border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive" data-induction-staff-validation-error>
            Please choose how this change affects staff, then save again.
        </div>
    @endif

    <form
        id="induction-section-edit-form"
        action="{{ route('admin.induction.policies.clauses.sub-clauses.update', [$policy, $section, $subClause]) }}"
        method="POST"
        class="portal-card space-y-4 p-5"
        onsubmit="if (window.tinymce) { window.tinymce.triggerSave(); }"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="staff_must_repeat_induction" value="{{ old('staff_must_repeat_induction', '') }}">

        <div>
            <label class="portal-label" for="title">Title</label>
            <input id="title" name="title" type="text" class="portal-input" required value="{{ old('title', $subClause->title) }}" maxlength="255">
        </div>
        <x-rich-editor
            name="body"
            :value="old('body', $subClause->body)"
            :max-words="\App\Models\InductionSubClause::BODY_MAX_WORDS"
            label="Content"
            :rows="14"
            required
        />
        @include('admin.induction.partials.acknowledgement-options', [
            'acknowledgementMode' => old('acknowledgement_mode', $subClause->acknowledgement_mode ?? \App\Support\InductionAcknowledgementMode::READ_ONLY),
        ])
        <div class="flex flex-wrap gap-3 border-t border-border pt-4">
            <button type="button" data-induction-save-open class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                Save changes
            </button>
            <a href="{{ route('admin.induction.policies.clauses.sub-clauses.show', [$policy, $section, $subClause]) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Cancel</a>
        </div>
    </form>

    @include('admin.induction.partials.staff-repeat-dialog')
</div>
@endsection
