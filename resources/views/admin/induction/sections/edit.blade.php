@extends('layouts.portal')

@section('title', 'Edit '.$section->title.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.policies.sections.show', [$policy, $section]) }}" class="text-sm font-medium text-primary hover:underline">← Back to section</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Edit section</h1>
        <p class="text-sm text-muted-foreground">{{ $policy->name }}</p>
    </div>

    @if ($errors->has('staff_must_repeat_induction'))
        <div class="portal-card border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive" data-induction-staff-validation-error>
            Please choose how this change affects staff, then save again.
        </div>
    @endif

    <form id="induction-section-edit-form" action="{{ route('admin.induction.policies.sections.update', [$policy, $section]) }}" method="POST" class="portal-card space-y-4 p-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="staff_must_repeat_induction" value="{{ old('staff_must_repeat_induction', '') }}">

        <div class="w-28">
            <label class="portal-label" for="sort_order">Order</label>
            <input id="sort_order" name="sort_order" type="number" class="portal-input" required value="{{ old('sort_order', $section->sort_order) }}" min="0" max="9999">
        </div>
        <div>
            <label class="portal-label" for="title">Title</label>
            <input id="title" name="title" type="text" class="portal-input" required value="{{ old('title', $section->title) }}" maxlength="255">
        </div>
        <div>
            <label class="portal-label" for="body">Content</label>
            <textarea id="body" name="body" rows="14" class="portal-input text-sm" required>{{ old('body', $section->body) }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-border pt-4">
            <button type="button" data-induction-save-open class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                Save changes
            </button>
            <a href="{{ route('admin.induction.policies.sections.show', [$policy, $section]) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Cancel</a>
        </div>
    </form>

    @include('admin.induction.partials.staff-repeat-dialog')
</div>
@endsection
