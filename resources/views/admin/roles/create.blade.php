@extends('layouts.portal')

@section('title', 'Create role · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to roles</a>
        <h1 class="mt-2 text-3xl font-bold text-foreground">Add new role</h1>
        <p class="mt-2 text-muted-foreground">Create an assignable role linked to a permission template.</p>
    </div>

    <div class="portal-card relative p-6 md:p-8" data-wff-form-loading-root>
        <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-5" data-wff-form-loading>
            @csrf
            <x-form.text-input name="name" label="Role name *" :value="old('name')" required maxlength="128" />
            <x-form.select name="role_template_id" label="Role template *" id="role_template_id" required>
                <option value="" disabled @selected(! old('role_template_id'))>Select template</option>
                @foreach ($roleTemplates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('role_template_id') === (string) $template->id)>
                        {{ $template->name }} ({{ $template->slug }})
                    </option>
                @endforeach
            </x-form.select>
            <p class="text-sm text-muted-foreground">Users assigned this role receive permissions from the selected template.</p>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary-hover hover:text-primary">Save</button>
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium hover:bg-muted">Cancel</a>
            </div>
        </form>
        <x-form.loading-overlay message="Creating role…" />
    </div>
</div>
@endsection
