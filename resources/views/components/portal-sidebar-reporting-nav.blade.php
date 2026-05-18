@props(['items'])

@foreach ($items as $item)
    @if (! empty($item['section'] ?? null))
        <p class="px-3 pb-0.5 pt-2 text-[10px] font-semibold uppercase tracking-wide text-sidebar-foreground/55">{{ $item['section'] }}</p>
    @endif
    @php $active = request()->routeIs($item['pattern']); @endphp
    <a href="{{ route($item['route']) }}" class="portal-sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-sidebar-primary text-sidebar-primary-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
        @include('components.portal-sidebar-icon', ['icon' => $item['icon']])
        <div class="flex min-w-0 flex-col">
            <span>{{ $item['title'] }}</span>
            <span class="text-xs {{ $active ? 'text-sidebar-primary-foreground/80' : 'text-sidebar-foreground/60' }}">{{ $item['desc'] }}</span>
        </div>
    </a>
@endforeach
