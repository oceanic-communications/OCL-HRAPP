@extends('layouts.portal')

@section('title', 'Role template permissions · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-3xl font-bold text-foreground">Role template permissions</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Choose a role template to configure access levels (user management, induction management, and user induction progress). Only the platform super admin can access this area.
            </p>
        </div>
        <a href="{{ route('admin.role-templates.create') }}" class="inline-flex w-fit shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover hover:text-primary">
            Add new role template
        </a>
    </div>

    <div class="portal-card overflow-hidden">
        <ul class="divide-y divide-border">
            @foreach ($roleTemplates as $template)
                <li class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-foreground">{{ $template->name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            <span class="capitalize">{{ $template->audience }}</span>
                            · {{ $template->slug }}
                            · {{ $template->permissions_count }} permission{{ $template->permissions_count === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <a
                        href="{{ route('admin.role-templates.permissions.edit', $template) }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-border bg-transparent px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                    >
                        Edit permissions
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
