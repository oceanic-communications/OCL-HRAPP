@extends('layouts.portal')

@section('title', 'Users · '.config('app.name'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-bold text-foreground">Users</h1>
            <p class="mt-2 text-muted-foreground">Manage accounts and role assignments.</p>
            @if (($stats['archived'] ?? 0) > 0)
                <p class="mt-2 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{{ $stats['archived'] }}</span> archived account{{ $stats['archived'] === 1 ? '' : 's' }}.
                </p>
            @endif
        </div>
        @if ($portalCap?->staffUserCreate ?? false)
            <a href="{{ route('admin.users.create') }}" class="inline-flex w-fit shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover hover:text-primary">
                New user
            </a>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="portal-card p-6">
            <p class="text-sm font-medium text-muted-foreground">Active accounts</p>
            <p class="mt-2 text-2xl font-bold text-foreground">{{ $stats['active'] }}</p>
            <p class="mt-1 text-xs text-muted-foreground">of {{ $stats['total'] }} total</p>
        </div>
        <div class="portal-card p-6">
            <p class="text-sm font-medium text-muted-foreground">Archived</p>
            <p class="mt-2 text-2xl font-bold text-foreground">{{ $stats['archived'] }}</p>
        </div>
    </div>

    @if ($users->isEmpty())
        <div class="portal-card p-12 text-center text-muted-foreground">No users yet.</div>
    @else
        <div class="portal-card overflow-hidden">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-4 py-3 text-foreground">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $u->email }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                @if ($u->is_staff_super_user)
                                    <span class="font-medium text-foreground">Super admin</span>
                                @else
                                    {{ $u->roles->pluck('name')->join(', ') ?: '—' }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->isArchived())
                                    <span class="text-destructive">Archived</span>
                                @else
                                    <span class="text-muted-foreground">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @php
                                    $actor = auth()->user();
                                    $canEdit = ($portalCap?->staffUserUpdate ?? false) || $actor?->isStaffSuperUser();
                                    $canEditThis = $canEdit && (! $u->is_staff_super_user || $actor?->isStaffSuperUser());
                                    $canArchive = ($portalCap?->staffUserArchive ?? false) || $actor?->isStaffSuperUser();
                                    $canArchiveThis = $canArchive && ! $u->is_staff_super_user && ! $u->isArchived();
                                @endphp
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($canEditThis)
                                        <a href="{{ route('admin.users.edit', $u) }}" class="text-primary hover:underline">Edit</a>
                                    @endif
                                    @if ($canArchiveThis)
                                        <form action="{{ route('admin.users.archive', $u) }}" method="POST" class="inline" onsubmit="return confirm('Archive this user? They will no longer be able to sign in.');">
                                            @csrf
                                            <button type="submit" class="text-destructive hover:underline">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    @endif
</div>
@endsection
