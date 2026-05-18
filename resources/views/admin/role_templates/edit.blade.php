@extends('layouts.portal')

@section('title', 'Permissions · '.$roleTemplate->name.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.role-templates.index') }}" class="text-sm font-medium text-primary hover:underline">← Role templates</a>
        <h1 class="mt-3 text-3xl font-bold text-foreground">{{ $roleTemplate->name }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Toggle permissions for this template. Users inherit these capabilities through their assigned role.
        </p>
    </div>

    <form action="{{ route('admin.role-templates.permissions.update', $roleTemplate) }}" method="post" class="relative space-y-6" data-wff-form-loading-root data-wff-form-loading>
        @csrf
        @method('PUT')

        @foreach ($permissionsByModule as $module => $rows)
            <div class="portal-card overflow-hidden">
                <h2 class="border-b border-border bg-muted/30 px-4 py-3 text-sm font-semibold text-foreground">
                    {{ str_replace('_', ' ', (string) $module) }}
                </h2>
                <ul class="max-h-80 divide-y divide-border overflow-y-auto sm:max-h-none sm:columns-2 sm:gap-x-6">
                    @foreach ($rows as $permission)
                        <li class="break-inside-avoid px-4 py-2">
                            <label class="flex cursor-pointer items-start gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    class="mt-1 h-4 w-4 rounded border-border text-primary"
                                    @checked(in_array($permission->id, $assignedIds, true))
                                />
                                <span>
                                    <span class="font-medium text-foreground">{{ $permission->resource_code }}.{{ $permission->action }}</span>
                                    <span class="mt-0.5 block text-xs text-muted-foreground">{{ $permission->slug }}</span>
                                </span>
                            </label>
                        </li>
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
