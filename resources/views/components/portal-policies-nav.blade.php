@php
    $currentPolicy = request()->route('policy');
    if (! $currentPolicy instanceof \App\Models\InductionPolicy) {
        $currentPolicy = null;
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
        @forelse ($sidebarPolicies ?? [] as $navPolicy)
            @php
                $isActive = $currentPolicy?->id === $navPolicy->id
                    && (request()->routeIs('admin.induction.policies.show')
                        || request()->routeIs('admin.induction.policies.clauses.*'));
            @endphp
            <a
                href="{{ route('admin.induction.policies.show', $navPolicy) }}"
                class="portal-sidebar-sublink {{ $isActive ? 'is-active' : '' }}"
                title="{{ $navPolicy->name }}"
            >
                <span class="font-semibold text-inherit">{{ $navPolicy->abbreviation }}</span>
                <span class="truncate text-inherit/80">· {{ $navPolicy->name }}</span>
            </a>
        @empty
            <p class="px-3 py-2 text-xs text-sidebar-foreground/60">No policies yet</p>
        @endforelse
    </div>
</div>
