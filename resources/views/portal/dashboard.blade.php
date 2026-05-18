@extends('layouts.portal')

@section('title', 'Dashboard · '.config('app.name'))

@section('content')
@php
    $u = auth()->user();
    $nameParts = collect(preg_split('/\s+/', trim($u->name)))->filter();
    $first = $nameParts->first() ?: 'there';
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $portalHr = $u->isStaffSuperUser() || ($portalCap->staffUserRead ?? false);
    $rolesLabel = $u->roles->pluck('name')->join(', ') ?: 'Team member';
    $initials = $nameParts->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
@endphp

<div class="mx-auto max-w-6xl space-y-6 sm:space-y-8">
    @if ($portalHr)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 via-violet-500 to-purple-600 p-5 text-white shadow-lg sm:p-6 lg:p-8">
            <div class="pointer-events-none absolute right-0 top-0 h-72 w-72 -translate-y-1/2 translate-x-1/2 rounded-full bg-white/5 sm:h-96 sm:w-96"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 h-48 w-48 -translate-x-1/2 translate-y-1/2 rounded-full bg-white/5 sm:h-64 sm:w-64"></div>
            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-white shadow-xl sm:h-16 sm:w-16 lg:h-20 lg:w-20">
                        @include('components.portal-sidebar-icon', ['icon' => 'building'])
                    </div>
                    <div class="min-w-0">
                        <p class="mb-1 flex items-center gap-2 text-sm text-white/80">
                            <span aria-hidden="true">☀</span> {{ $greet }}
                        </p>
                        <h1 class="font-heading text-pretty text-xl font-bold tracking-tight sm:text-2xl lg:text-3xl">HR dashboard</h1>
                        <p class="mt-1 text-sm text-white/80 sm:text-base">Oceanic employee portal overview</p>
                    </div>
                </div>
                <div class="flex min-w-0 flex-wrap gap-2 sm:gap-3">
                    <div class="flex min-w-0 flex-1 basis-[8.5rem] items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-white backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3">
                        @include('components.portal-sidebar-icon', ['icon' => 'users'])
                        <div class="min-w-0">
                            <p class="text-xs text-white/70">Workforce</p>
                            <p class="text-lg font-semibold sm:text-xl">—</p>
                        </div>
                    </div>
                    <div class="flex min-w-0 flex-1 basis-[8.5rem] items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-white backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3">
                        @include('components.portal-sidebar-icon', ['icon' => 'clipboard'])
                        <div class="min-w-0">
                            <p class="text-xs text-white/70">Probation</p>
                            <p class="text-lg font-semibold sm:text-xl">—</p>
                        </div>
                    </div>
                    <div class="flex min-w-0 flex-1 basis-[8.5rem] items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-white backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3">
                        @include('components.portal-sidebar-icon', ['icon' => 'chart'])
                        <div class="min-w-0">
                            <p class="text-xs text-white/70">Approvals</p>
                            <p class="text-lg font-semibold sm:text-xl">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-primary/90 to-primary/70 p-5 text-primary-foreground shadow-lg sm:p-6 lg:p-8">
            <div class="pointer-events-none absolute right-0 top-0 h-72 w-72 -translate-y-1/2 translate-x-1/2 rounded-full bg-white/5 sm:h-96 sm:w-96"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 h-48 w-48 -translate-x-1/2 translate-y-1/2 rounded-full bg-white/5 sm:h-64 sm:w-64"></div>
            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border-4 border-white/20 bg-white/20 text-xl font-bold shadow-xl sm:h-16 sm:w-16 sm:text-2xl lg:h-20 lg:w-20 lg:text-3xl" aria-hidden="true">{{ $initials }}</span>
                    <div class="min-w-0">
                        <p class="mb-1 flex items-center gap-2 text-sm text-white/80">
                            <span aria-hidden="true">☀</span> {{ $greet }}
                        </p>
                        <h1 class="font-heading text-pretty text-xl font-bold tracking-tight sm:text-2xl lg:text-3xl">{{ $first }}</h1>
                        <div class="mt-2 flex min-w-0 flex-col gap-1 text-sm text-white/80 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-2 sm:gap-y-1">
                            <span class="min-w-0 break-words">{{ $rolesLabel }}</span>
                            <span class="hidden text-white/40 sm:inline" aria-hidden="true">|</span>
                            <span class="min-w-0 break-all text-xs sm:text-sm" title="{{ $u->email }}">{{ $u->email }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex min-w-0 flex-wrap gap-2 sm:gap-3">
                    <div class="flex min-w-0 flex-1 basis-[9rem] items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-primary-foreground backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3">
                        @include('components.portal-sidebar-icon', ['icon' => 'calendar'])
                        <div class="min-w-0">
                            <p class="text-xs text-white/70">Annual leave</p>
                            <p class="text-base font-semibold sm:text-lg">—</p>
                        </div>
                    </div>
                    <div class="flex min-w-0 flex-1 basis-[9rem] items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-primary-foreground backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3">
                        @include('components.portal-sidebar-icon', ['icon' => 'clock'])
                        <div class="min-w-0">
                            <p class="text-xs text-white/70">Sick leave</p>
                            <p class="text-base font-semibold sm:text-lg">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (! $portalHr)
        <div class="portal-card border-2 border-warning/40 bg-gradient-to-r from-warning/15 to-transparent p-4 sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-warning text-lg font-bold text-warning-foreground" aria-hidden="true">!</div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-heading font-bold text-warning-foreground">Action required</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Complete any pending policy acknowledgements in Induction.</p>
                </div>
                <a href="{{ route('portal.induction') }}" class="inline-flex w-full shrink-0 items-center justify-center rounded-lg bg-warning px-4 py-2.5 text-center text-sm font-semibold text-warning-foreground hover:bg-warning/90 sm:w-auto sm:py-2">Go to induction</a>
            </div>
        </div>
    @endif

    <div id="notifications" class="portal-card space-y-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-heading text-lg font-semibold text-foreground">Notifications</h2>
            @if ($dashboardNotifications->filter(fn ($n) => $n->read_at === null)->isNotEmpty())
                <span class="rounded-full bg-primary/15 px-2 py-0.5 text-xs font-semibold text-primary">{{ $dashboardNotifications->filter(fn ($n) => $n->read_at === null)->count() }} unread</span>
            @endif
        </div>
        <ul class="space-y-3 text-sm">
            @forelse ($dashboardNotifications as $note)
                <li class="rounded-lg border border-border p-3 {{ $note->read_at ? 'bg-muted/20' : 'border-warning/40 bg-warning/5' }}">
                    <p class="font-medium text-foreground">{{ $note->title }}</p>
                    @if ($note->body)
                        <p class="mt-1 text-xs text-muted-foreground">{{ $note->body }}</p>
                    @endif
                    <p class="mt-1 text-[11px] text-muted-foreground">{{ $note->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                    @if (! $note->read_at)
                        <form method="POST" action="{{ route('portal.notifications.read', $note) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ route('portal.induction', [], false) }}">
                            <button type="submit" class="text-xs font-semibold text-primary hover:underline">Mark read and open induction</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="text-sm text-muted-foreground">You have no notifications yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
        <a href="{{ route('portal.induction') }}" class="portal-card block min-h-[4.5rem] p-4 transition-shadow hover:shadow-md sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <h2 class="font-heading text-pretty text-lg font-semibold text-foreground">Induction</h2>
                <span class="shrink-0 text-foreground" aria-hidden="true">@include('components.portal-sidebar-icon', ['icon' => 'book'])</span>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">Policies, modules, and acknowledgements.</p>
            <p class="mt-4 text-xs font-medium text-primary">Continue →</p>
        </a>
        <a href="{{ route('portal.settings') }}" class="portal-card block min-h-[4.5rem] p-4 transition-shadow hover:shadow-md sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <h2 class="font-heading text-pretty text-lg font-semibold text-foreground">Settings</h2>
                <span class="shrink-0 text-foreground" aria-hidden="true">@include('components.portal-sidebar-icon', ['icon' => 'cog'])</span>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">Account preferences and security.</p>
            <p class="mt-4 text-xs font-medium text-primary">Open →</p>
        </a>
    </div>
</div>
@endsection
