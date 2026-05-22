@props(['policy' => null])

@php
    $u = auth()->user();
    $isEditor = request()->routeIs('admin.induction.policies.builder') || request()->routeIs('admin.induction.index');
    $isSettings = request()->routeIs('admin.induction.settings.numbering*');
@endphp

<aside class="flex w-[240px] shrink-0 flex-col border-r border-[#E2E8F0] bg-white" aria-label="Policy builder navigation">
    <div class="border-b border-[#E2E8F0] px-5 py-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('oceanic-logo.png') }}" width="36" height="36" alt="" class="h-9 w-9 rounded-lg object-contain">
            <div>
                <span class="block text-sm font-bold text-slate-900">Oceanic HR</span>
                <span class="block text-xs text-slate-500">Policy Management</span>
            </div>
        </a>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Main">
        <a href="{{ route('dashboard') }}" class="policy-builder-nav-link">
            @include('components.portal-sidebar-icon', ['icon' => 'layout'])
            <span>Dashboard</span>
        </a>
        @if ($portalCap?->staffUserRead ?? false)
            <a href="{{ route('admin.users.index') }}" class="policy-builder-nav-link">
                @include('components.portal-sidebar-icon', ['icon' => 'users'])
                <span>Employees</span>
            </a>
        @endif
        @if ($portalCap?->inductionAdminAccess ?? false)
            <a href="{{ route('admin.induction.index') }}" class="policy-builder-nav-link {{ $isEditor ? 'is-active' : '' }}">
                @include('components.portal-sidebar-icon', ['icon' => 'document-plus'])
                <span>Policies</span>
            </a>
            <a href="{{ route('admin.induction.settings.numbering', $policy ? ['policy' => $policy->id] : []) }}" class="policy-builder-nav-link {{ $isSettings ? 'is-active' : '' }}">
                @include('components.portal-sidebar-icon', ['icon' => 'cog'])
                <span>Settings</span>
            </a>
        @endif
    </nav>

    <div class="border-t border-[#E2E8F0] p-4">
        <div class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#4F46E5] text-xs font-semibold text-white">
                {{ strtoupper(mb_substr($u->first_name ?? '', 0, 1).mb_substr($u->last_name ?? '', 0, 1)) }}
            </span>
            <div class="min-w-0 text-xs">
                <p class="truncate font-medium text-slate-900">{{ $u->name }}</p>
                <p class="truncate text-slate-500">{{ $u->email }}</p>
            </div>
        </div>
    </div>
</aside>
