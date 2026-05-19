@extends('layouts.portal')

@php
    $templateMode = old('template_mode', $isEdit && $canManageTemplates ? 'existing' : ($canManageTemplates ? 'existing' : 'existing'));
    $selectedTemplateId = old('role_template_id', $role?->role_template_id);
@endphp

@section('title', ($isEdit ? 'Edit' : 'Create').' role · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to roles</a>
        <h1 class="mt-2 text-3xl font-bold text-foreground">{{ $isEdit ? 'Edit role' : 'Create role' }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Set the role name, choose or define its permission template, and configure access levels in one place.
        </p>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif


    <form
        action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
        method="POST"
        class="relative space-y-8"
        data-wff-form-loading-root
        data-wff-form-loading
        data-role-setup-form
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="portal-card space-y-4 p-6">
            <h2 class="text-sm font-semibold text-foreground">1. Role</h2>
            <x-form.text-input name="name" label="Role name *" :value="old('name', $role?->name)" required maxlength="128" />
            @if ($isEdit && ($role?->users_count ?? 0) > 0)
                <p class="text-sm text-muted-foreground">{{ $role->users_count }} user(s) currently assigned this role.</p>
            @endif
        </section>

        <section class="portal-card space-y-4 p-6">
            <h2 class="text-sm font-semibold text-foreground">2. Permission template</h2>

            @if ($canManageTemplates)
                <fieldset class="space-y-3">
                    <legend class="sr-only">Template source</legend>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-4 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input
                            type="radio"
                            name="template_mode"
                            value="existing"
                            class="mt-0.5 h-4 w-4 border-border text-primary"
                            @checked($templateMode === 'existing')
                        />
                        <span>
                            <span class="block text-sm font-medium text-foreground">Use an existing template</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">Link this role to a template already used in the system.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-4 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input
                            type="radio"
                            name="template_mode"
                            value="new"
                            class="mt-0.5 h-4 w-4 border-border text-primary"
                            @checked($templateMode === 'new')
                        />
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ $isEdit ? 'Update this role\'s template' : 'Create a new template' }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ $isEdit ? 'Rename the template linked to this role and set its slug.' : 'Define a new permission blueprint for this role.' }}</span>
                        </span>
                    </label>
                </fieldset>

                <div data-template-panel="existing" class="{{ $templateMode === 'new' ? 'hidden' : '' }}">
                    <x-form.select name="role_template_id" label="Template *" id="role_template_id">
                        <option value="" disabled @selected(! $selectedTemplateId)>Select template</option>
                        @foreach ($roleTemplates as $template)
                            <option value="{{ $template->id }}" @selected((int) $selectedTemplateId === (int) $template->id)>
                                {{ $template->name }} ({{ $template->slug }})
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div data-template-panel="new" class="{{ $templateMode === 'existing' ? 'hidden' : '' }} space-y-4">
                    <x-form.text-input
                        name="template_name"
                        label="Template name *"
                        :value="old('template_name', $isEdit ? $role?->roleTemplate?->name : '')"
                        maxlength="128"
                    />
                    <x-form.text-input
                        name="template_slug"
                        label="Template slug *"
                        :value="old('template_slug', $isEdit ? $role?->roleTemplate?->slug : '')"
                        maxlength="64"
                        pattern="[a-z0-9_]+"
                        autocomplete="off"
                        hint="Lowercase letters, numbers, and underscores only."
                    />
                </div>

                @if ($isEdit && ($templateSharedRoleCount ?? 0) > 1)
                    <p class="rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-foreground">
                        This template is shared by {{ $templateSharedRoleCount }} roles. Permission changes apply to every role using it.
                    </p>
                @endif
            @else
                <x-form.select name="role_template_id" label="Permission template *" id="role_template_id" required>
                    <option value="" disabled @selected(! $selectedTemplateId)>Select template</option>
                    @foreach ($roleTemplates as $template)
                        <option value="{{ $template->id }}" @selected((int) $selectedTemplateId === (int) $template->id)>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </x-form.select>
                <p class="text-sm text-muted-foreground">Contact a super admin to create templates or change access levels.</p>
            @endif
        </section>

        @if ($canManageTemplates)
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">3. Access levels</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Choose what users with this role can do in the portal.</p>
                </div>
                @include('admin.roles._access-levels')
            </section>
        @endif

        <div class="flex flex-wrap items-center gap-3 border-t border-border pt-6">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary-hover hover:text-primary">
                {{ $isEdit ? 'Save role' : 'Create role' }}
            </button>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted">
                Cancel
            </a>
        </div>

        <x-form.loading-overlay :message="$isEdit ? 'Saving role…' : 'Creating role…'" />
    </form>

    @if ($isEdit && $role && ! $role->isArchived() && ($role->users_count ?? 0) === 0)
        <form
            action="{{ route('admin.roles.archive', $role) }}"
            method="POST"
            class="flex justify-end"
            onsubmit="return confirm('Archive this role? It will no longer be available when assigning users.');"
        >
            @csrf
            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-destructive/40 px-5 py-2.5 text-sm font-medium text-destructive hover:bg-destructive/10">
                Archive role
            </button>
        </form>
    @endif
</div>
@endsection
