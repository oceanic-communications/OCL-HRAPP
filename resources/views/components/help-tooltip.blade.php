@props([
    'title' => 'Search help',
    'text' => '',
    'examples' => [],
])

<details class="group relative inline-flex items-center align-middle leading-none">
    <summary
        class="portal-touch-target inline-flex cursor-pointer list-none items-center justify-center rounded-full border border-border px-1 text-[11px] font-semibold leading-none text-muted-foreground transition-colors hover:bg-muted marker:hidden [&::-webkit-details-marker]:hidden"
        aria-label="{{ $title }}"
    >
        ?
    </summary>
    <div class="absolute right-0 z-40 mt-2 w-72 rounded-lg border border-border bg-card p-3 shadow-lg">
        <p class="text-xs font-semibold text-foreground">{{ $title }}</p>
        @if ($text !== '')
            <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $text }}</p>
        @endif
        @if (count($examples) > 0)
            <div class="mt-2 border-t border-border pt-2">
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Examples</p>
                <ul class="mt-1 space-y-1 text-xs text-foreground">
                    @foreach ($examples as $example)
                        <li>{{ $example }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</details>
