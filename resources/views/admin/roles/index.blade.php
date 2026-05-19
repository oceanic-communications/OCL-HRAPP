@extends('layouts.portal')

@section('title', 'Roles · '.config('app.name'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-bold text-foreground">Roles</h1>
            <p class="mt-2 text-muted-foreground">View and manage assignable portal roles linked to permission templates.</p>
            @if (($stats['archived'] ?? 0) > 0)
                <p class="mt-2 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{{ $stats['archived'] }}</span> archived role{{ $stats['archived'] === 1 ? '' : 's' }}.
                </p>
            @endif
        </div>
        @if ($portalCap?->staffRoleUpdate ?? false)
            <a href="{{ route('admin.roles.create') }}" class="inline-flex w-fit shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover hover:text-primary">
                Add new role
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="portal-card p-6">
            <p class="text-sm font-medium text-muted-foreground">Active roles</p>
            <p class="mt-2 text-2xl font-bold text-foreground">{{ $stats['active'] }}</p>
            <p class="mt-1 text-xs text-muted-foreground">of {{ $stats['total'] }} total</p>
        </div>
        <div class="portal-card p-6">
            <p class="text-sm font-medium text-muted-foreground">Archived</p>
            <p class="mt-2 text-2xl font-bold text-foreground">{{ $stats['archived'] }}</p>
        </div>
    </div>

    @if ($roles->isEmpty())
        <div class="portal-card p-12 text-center text-muted-foreground">No roles yet.</div>
    @else
        <div class="portal-card overflow-hidden">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Template</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Users</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($roles as $role)
                        <tr class="{{ $role->isArchived() ? 'bg-muted/20 text-muted-foreground' : '' }}">
                            <td class="px-4 py-3 font-medium text-foreground">{{ $role->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $role->roleTemplate?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $role->users_count }}</td>
                            <td class="px-4 py-3">
                                @if ($role->isArchived())
                                    <span class="text-destructive">Archived</span>
                                @else
                                    <span class="text-muted-foreground">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.roles.show', $role) }}" class="font-medium text-primary hover:underline">View</a>
                                    @if (! $role->isArchived() && ($portalCap?->staffRoleUpdate ?? false))
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium text-primary hover:underline">Edit</a>
                                        <form action="{{ route('admin.roles.archive', $role) }}" method="POST" class="inline" onsubmit="return confirm('Archive this role? It will no longer be available when assigning users.');">
                                            @csrf
                                            <button type="submit" class="font-medium text-destructive hover:underline">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $roles->links() }}</div>
    @endif
</div>
@endsection
