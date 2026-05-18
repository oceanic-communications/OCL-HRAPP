@extends('layouts.portal')

@section('title', 'Create user · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-foreground">Create user</h1>
        <p class="mt-2 text-muted-foreground">The user signs in with a one-time code sent to their email.</p>
    </div>

    @if (! ($portalCap?->staffUserCreate ?? false))
        <div class="portal-card border-border p-6 text-sm text-foreground">
            <p class="font-medium">You do not have permission to create users.</p>
            <a href="{{ route('admin.users.index') }}" class="mt-4 inline-flex rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Back</a>
        </div>
    @else
    <div class="portal-card relative p-6 md:p-8" data-wff-form-loading-root>
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5" data-wff-form-loading>
            @csrf
            <x-form.text-input name="title" label="Title" :value="old('title')" maxlength="20" autocomplete="honorific-prefix" />
            <x-form.text-input name="first_name" label="First name *" :value="old('first_name')" required autocomplete="given-name" />
            <x-form.text-input name="last_name" label="Last name *" :value="old('last_name')" required autocomplete="family-name" />
            <x-form.text-input name="email" label="Email *" type="email" :value="old('email')" required autocomplete="email" />
            <x-form.select name="role_id" label="Role *" id="role_id" required>
                <option value="" disabled @selected(! old('role_id'))>Select role</option>
                @foreach ($rolesForCreate as $role)
                    <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </x-form.select>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary-hover hover:text-primary">Save</button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium hover:bg-muted">Cancel</a>
            </div>
        </form>
        <x-form.loading-overlay message="Creating user…" />
    </div>
    @endif
</div>
@endsection
