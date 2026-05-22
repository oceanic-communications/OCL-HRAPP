@php
    $u = auth()->user();
@endphp

<div class="flex min-h-0 flex-1 flex-col">
    <div class="shrink-0 border-b border-sidebar-border px-4 py-4 md:px-5 md:py-5">
        <div class="flex items-start justify-between gap-2">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3">
                <img src="{{ asset('oceanic-logo.png') }}" width="40" height="40" alt="" class="h-10 w-10 shrink-0 rounded-xl bg-white object-contain p-0.5 shadow-md">
                <div class="min-w-0">
                    <span class="block truncate text-base font-bold tracking-tight text-sidebar-foreground">Oceanic</span>
                    <span class="block truncate text-xs text-sidebar-foreground/60">Employee portal</span>
                </div>
            </a>
            <button type="button" id="portal-sidebar-close" class="portal-touch-target shrink-0 rounded-lg px-2 py-1 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground md:hidden">
                Close
            </button>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain px-3 py-4">
        <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/40">Menu</p>
        <nav class="space-y-1" aria-label="Primary">
            <x-portal-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="layout" label="Dashboard" />
            <x-portal-nav-link :href="route('portal.induction')" :active="request()->routeIs('portal.induction*')" icon="book" label="My induction" />
            {{-- Settings hidden for now --}}
        </nav>

        @if (($portalCap?->staffUserRead ?? false) || ($portalCap?->staffRoleRead ?? false) || ($portalCap?->inductionAdminAccess ?? false) || $u->isStaffSuperUser())
            <p class="mb-2 mt-6 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/40">Administration</p>
            <nav class="space-y-1" aria-label="Administration">
                @if ($portalCap?->staffUserRead ?? false)
                    <x-portal-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users*')" icon="users" label="User management" />
                @endif
                @if ($portalCap?->staffRoleRead ?? false)
                    <x-portal-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles*')" icon="shield" label="Roles" />
                @endif
                @if ($portalCap?->inductionEnrollmentRead ?? false)
                    <x-portal-nav-link :href="route('admin.induction.progress.index')" :active="request()->routeIs('admin.induction.progress*')" icon="chart" label="Employee progress" />
                @endif
                @if ($portalCap?->inductionAdminAccess ?? false)
                    <x-portal-policies-nav variant="portal" />
                @endif
            </nav>
        @endif
    </div>

    <div class="shrink-0 border-t border-sidebar-border p-3">
        <div class="flex min-w-0 items-center gap-3 rounded-lg bg-sidebar-foreground/5 px-3 py-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sidebar-primary text-sm font-semibold text-sidebar-primary-foreground" aria-hidden="true">
                {{ strtoupper(mb_substr($u->first_name ?? '', 0, 1).mb_substr($u->last_name ?? '', 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1 text-xs">
                <p class="truncate font-medium text-sidebar-foreground">{{ $u->name }}</p>
                <p class="truncate text-sidebar-foreground/60" title="{{ $u->email }}">{{ $u->email }}</p>
            </div>
        </div>
    </div>
</div>
