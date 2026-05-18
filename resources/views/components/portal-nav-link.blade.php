@props([
    'href',
    'active' => false,
    'icon' => 'layout',
    'label',
    'badge' => null,
])

<a
    href="{{ $href }}"
    class="portal-sidebar-link flex min-h-10 items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}"
>
    <span class="flex min-w-0 items-center gap-3">
        @include('components.portal-sidebar-icon', ['icon' => $icon])
        <span class="truncate">{{ $label }}</span>
    </span>
    @if ($badge !== null && $badge !== '')
        <span class="shrink-0 rounded-full border border-sidebar-border/80 bg-sidebar-foreground/10 px-2 py-0.5 text-[10px] font-semibold {{ $active ? 'border-sidebar-primary-foreground/30 text-sidebar-primary-foreground' : 'text-sidebar-foreground/80' }}">{{ $badge }}</span>
    @endif
</a>
