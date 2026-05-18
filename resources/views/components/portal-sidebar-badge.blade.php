@props([
    'count' => 0,
])

@if ($count > 0)
    <span
        class="ml-auto inline-flex h-6 min-w-[1.5rem] shrink-0 items-center justify-center rounded-full bg-destructive px-1.5 text-[11px] font-semibold tabular-nums text-destructive-foreground"
        aria-label="{{ $count }} pending"
    >{{ $count > 99 ? '99+' : $count }}</span>
@endif
