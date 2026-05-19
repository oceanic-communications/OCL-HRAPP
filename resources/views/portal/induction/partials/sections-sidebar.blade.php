@php
    $completed = collect($completedSectionIds);
    $currentSectionId = $section->id;
@endphp

<aside class="portal-card flex flex-col overflow-hidden lg:max-h-[calc(100dvh-12rem)]" aria-label="Policy sections">
    <div class="shrink-0 border-b border-border bg-muted/30 px-4 py-4 sm:px-5">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-secondary/20 text-secondary" aria-hidden="true">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-foreground">Policy sections</h2>
                <p class="text-xs text-muted-foreground">Select a section to view</p>
            </div>
        </div>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto p-3" aria-label="Section list">
        <ul class="space-y-2">
            @foreach ($sections as $idx => $item)
                @php
                    $isDone = $completed->contains($item->id);
                    $prev = $idx > 0 ? $sections->get($idx - 1) : null;
                    $locked = $prev && ! $completed->contains($prev->id);
                    $isCurrent = $item->id === $currentSectionId;
                    $canOpen = ! $locked;
                @endphp
                <li>
                    @if ($canOpen)
                        <a
                            href="{{ route('portal.induction.section', $item) }}"
                            @class([
                                'flex items-center gap-3 rounded-xl border p-3 text-left transition-colors sm:p-4',
                                'border-secondary/50 bg-secondary/10 shadow-sm ring-1 ring-secondary/20' => $isCurrent,
                                'border-border hover:border-secondary/40 hover:bg-muted/40' => ! $isCurrent,
                            ])
                            @if ($isCurrent) aria-current="page" @endif
                        >
                    @else
                        <span class="flex items-center gap-3 rounded-xl border border-border bg-muted/30 p-3 opacity-60 sm:p-4" aria-disabled="true">
                    @endif

                    <span @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        'bg-accent text-accent-foreground' => $isDone,
                        'bg-secondary/25 text-secondary ring-2 ring-secondary/40' => $isCurrent && ! $isDone,
                        'bg-muted text-muted-foreground' => ! $isDone && ! $isCurrent,
                    ]) aria-hidden="true">
                        @if ($locked)
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        @elseif ($isDone)
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @elseif ($isCurrent)
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span @class([
                            'block truncate text-sm font-semibold',
                            'text-muted-foreground' => $locked,
                            'text-foreground' => ! $locked,
                        ])>{{ $item->title }}</span>
                        <span class="mt-1 block text-xs font-medium {{ $isDone ? 'text-accent' : ($isCurrent ? 'text-secondary' : 'text-muted-foreground') }}">
                            @if ($isDone)
                                Completed
                            @elseif ($isCurrent)
                                In progress
                            @elseif ($locked)
                                Locked
                            @else
                                Not started
                            @endif
                        </span>
                    </span>

                    @if ($canOpen && ! $isCurrent)
                        <svg class="h-5 w-5 shrink-0 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    @endif

                    @if ($canOpen)
                        </a>
                    @else
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
