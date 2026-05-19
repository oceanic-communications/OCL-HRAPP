@extends('layouts.portal')

@section('title', $role->name.' · Roles · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to roles</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold text-foreground">{{ $role->name }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Template: {{ $role->roleTemplate?->name ?? '—' }}
                    @if ($role->roleTemplate?->slug)
                        · {{ $role->roleTemplate->slug }}
                    @endif
                </p>
            </div>
            @if ($role->isArchived())
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">Archived</span>
            @else
                <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    @if ($errors->has('archive'))
        <div class="portal-card border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive">{{ $errors->first('archive') }}</div>
    @endif

    <div class="portal-card space-y-4 p-6">
        <h2 class="text-sm font-semibold text-foreground">Details</h2>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted-foreground">Role template</dt>
                <dd class="mt-0.5 font-medium text-foreground">{{ $role->roleTemplate?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Audience</dt>
                <dd class="mt-0.5 font-medium capitalize text-foreground">{{ $role->roleTemplate?->audience ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Assigned users</dt>
                <dd class="mt-0.5 font-medium text-foreground">{{ $role->users->count() }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Last updated</dt>
                <dd class="mt-0.5 font-medium text-foreground">{{ $role->updated_at?->timezone(config('app.timezone'))->format('j M Y, g:i a') ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="portal-card p-6">
        <h2 class="text-sm font-semibold text-foreground">Access levels</h2>
        <div class="mt-4 space-y-4">
            @foreach ($accessLevels as $level)
                <div class="rounded-lg border border-border p-4">
                    <h3 class="text-sm font-semibold text-foreground">
                        {{ $level['label'] }}
                        @if ($level['subtitle'])
                            <span class="font-normal text-muted-foreground">({{ $level['subtitle'] }})</span>
                        @endif
                    </h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($level['capabilities'] as $capability)
                            <li>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $capability['granted'] ? 'bg-accent/15 text-accent' : 'bg-muted text-muted-foreground' }}">
                                    {{ $capability['label'] }}{{ $capability['granted'] ? '' : ' — not granted' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

    @if ($role->users->isNotEmpty())
        <div class="portal-card p-6">
            <h2 class="text-sm font-semibold text-foreground">Assigned users</h2>
            <ul class="mt-3 divide-y divide-border text-sm">
                @foreach ($role->users as $user)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="font-medium text-foreground">{{ $user->name }}</span>
                        <span class="text-muted-foreground">{{ $user->email }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if (! $role->isArchived() && ($portalCap?->staffRoleUpdate ?? false))
            <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-hover">Edit role</a>
            @if ($role->users->isEmpty())
                <form action="{{ route('admin.roles.archive', $role) }}" method="POST" onsubmit="return confirm('Archive this role? It will no longer be available when assigning users.');">
                    @csrf
                    <button type="submit" class="inline-flex rounded-lg border border-destructive/40 px-4 py-2 text-sm font-semibold text-destructive hover:bg-destructive/10">Archive</button>
                </form>
            @endif
        @endif
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Back to list</a>
    </div>
</div>
@endsection
