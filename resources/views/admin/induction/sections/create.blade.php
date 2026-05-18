@extends('layouts.portal')

@section('title', 'New section · '.$policy->name.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.induction.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to induction policies</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">Add policy section</h1>
        <p class="text-sm text-muted-foreground">{{ $policy->name }}</p>
    </div>

    <form action="{{ route('admin.induction.policies.sections.store', $policy) }}" method="POST" class="portal-card space-y-4 p-5">
        @csrf
        <div>
            <label class="portal-label" for="title">Title</label>
            <input id="title" name="title" type="text" class="portal-input" required value="{{ old('title') }}" maxlength="255">
        </div>
        <div>
            <label class="portal-label" for="sort_order">Order (optional)</label>
            <input id="sort_order" name="sort_order" type="number" class="portal-input w-28" min="0" max="9999" value="{{ old('sort_order') }}" placeholder="Auto">
        </div>
        <div>
            <label class="portal-label" for="body">Content</label>
            <textarea id="body" name="body" rows="12" class="portal-input text-sm" required placeholder="Policy text employees will read for this section.">{{ old('body') }}</textarea>
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Create section</button>
            <a href="{{ route('admin.induction.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">Cancel</a>
        </div>
    </form>
</div>
@endsection
