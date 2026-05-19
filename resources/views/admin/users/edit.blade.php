@extends('layouts.portal')

@section('title', 'Edit user · '.config('app.name'))

@section('content')
@php
    $mayEdit = ($portalCap?->staffUserUpdate ?? false) || auth()->user()?->isStaffSuperUser();
@endphp
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-foreground">Edit user</h1>
        <p class="mt-2 text-muted-foreground">{{ $user->name }} · {{ $user->email }}</p>
    </div>

    @if (! $mayEdit)
        <div class="portal-card border-border p-6 text-sm text-foreground">
            <p class="font-medium">You do not have permission to edit this account.</p>
            <a href="{{ route('admin.users.index') }}" class="mt-4 inline-flex rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Back</a>
        </div>
    @else
    <div class="portal-card relative p-6 md:p-8" data-wff-form-loading-root>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5" data-wff-form-loading>
            @csrf
            @method('PUT')
            <x-form.text-input name="title" label="Title" :value="old('title', $user->title)" maxlength="20" autocomplete="honorific-prefix" />
            <x-form.text-input name="first_name" label="First name *" :value="old('first_name', $user->first_name)" required autocomplete="given-name" />
            <x-form.text-input name="last_name" label="Last name *" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
            <x-form.text-input name="email" label="Email *" type="email" :value="old('email', $user->email)" required autocomplete="email" />

            @if (! $user->is_staff_super_user)
                <x-form.select name="role_id" label="Role *" id="role_id" required>
                    <option value="">Select role</option>
                    @foreach ($roleOptions as $role)
                        <option value="{{ $role->id }}" @selected($selectedRoleId !== null && (int) $selectedRoleId === (int) $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-form.select>
            @else
                <p class="text-sm text-muted-foreground">Super admin accounts do not use a role template.</p>
            @endif

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary-hover hover:text-primary">Save</button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium hover:bg-muted">Cancel</a>
            </div>
        </form>
        <x-form.loading-overlay message="Saving user…" />
    </div>

    @php
        $canArchive = ($portalCap?->staffUserArchive ?? false) || auth()->user()?->isStaffSuperUser();
        $canArchiveThis = $canArchive && ! $user->is_staff_super_user && ! $user->isArchived();
    @endphp
    @if ($canArchiveThis)
        <form
            action="{{ route('admin.users.archive', $user) }}"
            method="POST"
            class="flex justify-end"
            onsubmit="return confirm('Archive this user? They will no longer be able to sign in.');"
        >
            @csrf
            <button type="submit" class="inline-flex rounded-lg border border-destructive/40 px-5 py-2.5 text-sm font-medium text-destructive hover:bg-destructive/10">
                Archive user
            </button>
        </form>
    @endif
    @endif
</div>
@endsection
