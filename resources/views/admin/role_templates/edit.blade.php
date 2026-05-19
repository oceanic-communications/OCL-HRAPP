@extends('layouts.portal')

@section('title', 'Permissions · '.$roleTemplate->name.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.role-templates.index') }}" class="text-sm font-medium text-primary hover:underline">← Role templates</a>
        <h1 class="mt-3 text-3xl font-bold text-foreground">{{ $roleTemplate->name }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Configure access levels for this template. Users inherit these capabilities through their assigned role.
        </p>
    </div>

    <form action="{{ route('admin.role-templates.permissions.update', $roleTemplate) }}" method="post" class="relative space-y-6" data-wff-form-loading-root data-wff-form-loading>
        @csrf
        @method('PUT')

        @foreach ($accessLevels as $level)
            <div class="portal-card overflow-hidden">
                <div class="border-b border-border bg-muted/30 px-4 py-3">
                    <h2 class="text-sm font-semibold text-foreground">
                        {{ $level['label'] }}
                        @if ($level['subtitle'])
                            <span class="font-normal text-muted-foreground">({{ $level['subtitle'] }})</span>
                        @endif
                    </h2>
                </div>
                <ul class="divide-y divide-border">
                    @foreach ($level['capabilities'] as $capability)
                        @php
                            $permissionId = $permissionIdsBySlug[$capability['slug']] ?? null;
                        @endphp
                        @if ($permissionId)
                            <li class="px-4 py-3">
                                <label class="flex cursor-pointer items-center gap-3 text-sm">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permissionId }}"
                                        class="h-4 w-4 rounded border-border text-primary"
                                        @checked(\App\Support\PortalPermissions::isGranted($capability['slug'], $assignedSlugs))
                                    />
                                    <span class="font-medium text-foreground">{{ $capability['label'] }}</span>
                                </label>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover hover:text-primary">
                Save permissions
            </button>
            <a href="{{ route('admin.role-templates.index') }}" class="inline-flex items-center justify-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted">
                Cancel
            </a>
        </div>
        <x-form.loading-overlay message="Saving permissions…" />
    </form>
</div>
@endsection
