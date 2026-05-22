@extends('layouts.portal')

@section('title', 'New clause · '.$policy->name.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="text-sm font-medium text-primary hover:underline">← Back to {{ $policy->name }}</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Add clause</h1>
        <p class="text-sm text-muted-foreground">{{ $policy->name }}</p>
    </div>

    <form
        action="{{ route('admin.induction.policies.clauses.store', $policy) }}"
        method="POST"
        class="portal-card space-y-4 p-5"
        onsubmit="if (window.tinymce) { window.tinymce.triggerSave(); }"
    >
        @csrf
        <div>
            <label class="portal-label" for="title">Title</label>
            <input id="title" name="title" type="text" class="portal-input" required value="{{ old('title') }}" maxlength="255">
        </div>
        <x-rich-editor
            name="body"
            :value="old('body', '')"
            :max-words="\App\Models\InductionSection::BODY_MAX_WORDS"
            label="Content"
            :rows="12"
            required
            placeholder="Policy text employees will read for this clause."
        />
        @include('admin.induction.partials.acknowledgement-options', [
            'acknowledgementMode' => old('acknowledgement_mode', $policy->acknowledgement_mode ?? \App\Support\InductionAcknowledgementMode::READ_ONLY),
        ])
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Create clause</button>
            <a href="{{ route('admin.induction.policies.show', $policy) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Cancel</a>
        </div>
    </form>
</div>
@endsection
