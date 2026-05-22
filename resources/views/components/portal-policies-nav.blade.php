@props([
    'policy' => null,
])

@php
    $currentPolicy = $policy;
    if ($currentPolicy === null && request()->route('policy') instanceof \App\Models\InductionPolicy) {
        $currentPolicy = request()->route('policy');
    }

    $sectionActive = request()->routeIs('admin.induction.index')
        || request()->routeIs('admin.induction.policies.*');
@endphp

<div class="portal-sidebar-group {{ $sectionActive ? 'is-open' : '' }}" data-portal-nav-group>
    <div class="flex items-center gap-1">
        <a
            href="{{ route('admin.induction.index') }}"
            class="portal-sidebar-link {{ $sectionActive ? 'is-parent-active' : '' }} flex min-h-10 min-w-0 flex-1 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium"
        >
            @include('components.portal-sidebar-icon', ['icon' => 'document-plus'])
            <span class="truncate">Policies</span>
        </a>
        <button
            type="button"
            class="portal-sidebar-group-toggle shrink-0 rounded-md p-2 text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground"
            aria-expanded="{{ $sectionActive ? 'true' : 'false' }}"
            aria-controls="policies-nav-sub"
            data-portal-nav-group-toggle
        >
            <svg class="h-4 w-4 transition-transform {{ $sectionActive ? 'rotate-180' : '' }}" data-portal-nav-chevron xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
    </div>

    <div
        id="policies-nav-sub"
        class="portal-sidebar-group-items {{ $sectionActive ? '' : 'hidden' }}"
        data-portal-nav-group-items
    >
        <a
            href="{{ route('admin.induction.index') }}"
            class="portal-sidebar-sublink {{ request()->routeIs('admin.induction.index') ? 'is-active' : '' }}"
        >
            All policies
        </a>

        @if ($currentPolicy)
            <a
                href="{{ route('admin.induction.policies.builder', $currentPolicy) }}"
                class="portal-sidebar-sublink {{ request()->routeIs('admin.induction.policies.builder') ? 'is-active' : '' }}"
            >
                <span class="font-semibold text-inherit">{{ $currentPolicy->abbreviation }}</span>
                <span class="text-inherit/80">· Document builder</span>
            </a>
            <a
                href="{{ route('admin.induction.policies.show', $currentPolicy) }}"
                class="portal-sidebar-sublink {{ request()->routeIs('admin.induction.policies.show') || request()->routeIs('admin.induction.policies.clauses.*') ? 'is-active' : '' }}"
            >
                <span class="font-semibold text-inherit">{{ $currentPolicy->abbreviation }}</span>
                <span class="text-inherit/80">· Manage clauses</span>
            </a>
        @endif
    </div>
</div>
