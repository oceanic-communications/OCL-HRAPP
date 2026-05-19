@extends('layouts.portal')

@section('title', 'Create role template · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <a href="{{ route('admin.role-templates.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to role templates</a>
        <h1 class="mt-2 text-3xl font-bold text-foreground">Add new role template</h1>
        <p class="mt-2 text-muted-foreground">Define a permission blueprint. You can assign access levels after creating the template.</p>
    </div>

    <div class="portal-card relative p-6 md:p-8" data-wff-form-loading-root>
        <form action="{{ route('admin.role-templates.store') }}" method="POST" class="space-y-5" data-wff-form-loading>
            @csrf
            <x-form.text-input name="name" label="Template name *" :value="old('name')" required maxlength="128" />
            <x-form.text-input
                name="slug"
                label="Slug *"
                :value="old('slug')"
                required
                maxlength="64"
                pattern="[a-z0-9_]+"
                autocomplete="off"
                hint="Lowercase letters, numbers, and underscores only (e.g. team_lead)."
            />
            <x-form.select name="audience" label="Audience *" id="audience" required>
                <option value="{{ \App\Models\RoleTemplate::AUDIENCE_STAFF }}" @selected(old('audience', \App\Models\RoleTemplate::AUDIENCE_STAFF) === \App\Models\RoleTemplate::AUDIENCE_STAFF)>
                    Staff
                </option>
            </x-form.select>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary-hover hover:text-primary">Save</button>
                <a href="{{ route('admin.role-templates.index') }}" class="inline-flex items-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium hover:bg-muted">Cancel</a>
            </div>
        </form>
        <x-form.loading-overlay message="Creating role template…" />
    </div>
</div>
@endsection
