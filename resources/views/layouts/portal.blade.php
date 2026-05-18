<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="{{ asset('oceanic-logo.png') }}" type="image/png" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('oceanic-logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-background font-sans antialiased">
    <a href="#portal-main" class="portal-skip-link">Skip to main content</a>
    <div class="flex min-h-dvh min-w-0">
        <div id="portal-sidebar-overlay" class="fixed inset-0 z-40 hidden bg-black/40 md:hidden" aria-hidden="true"></div>

        <aside
            id="portal-sidebar"
            class="portal-sidebar-shell fixed bottom-0 left-0 top-0 z-50 flex w-[min(280px,100%)] max-w-[min(280px,calc(100vw-3rem))] shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl transition-transform duration-200 ease-out md:static md:z-auto md:h-auto md:max-h-none md:min-h-dvh md:w-[280px] md:max-w-[280px] md:shadow-none"
            aria-label="Sidebar navigation"
        >
            <x-portal-employee-sidebar />
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex min-h-16 shrink-0 items-center justify-between gap-2 border-b border-border bg-card/95 px-3 backdrop-blur supports-[backdrop-filter]:bg-card/80 sm:gap-4 sm:px-4 lg:px-6">
                <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                    <button type="button" id="portal-menu-open" class="portal-touch-target shrink-0 rounded-lg p-2 text-foreground hover:bg-muted md:hidden" aria-controls="portal-sidebar" aria-expanded="false" aria-label="Open menu">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <p class="min-w-0 truncate text-sm font-semibold text-foreground md:hidden">{{ config('app.name') }}</p>
                    <div class="hidden min-w-0 max-w-md flex-1 items-center gap-2 rounded-lg border border-border bg-muted/50 px-3 py-2 md:flex">
                        @include('components.portal-sidebar-icon', ['icon' => 'search'])
                        <input type="search" name="q" class="min-w-0 flex-1 border-0 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground" placeholder="Search…" autocomplete="off" disabled aria-disabled="true">
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <nav class="hidden flex-wrap items-center gap-2 text-sm font-medium lg:flex" aria-label="Administration">
                        @include('components.portal-admin-nav-links')
                    </nav>
                    @isset($portalHeaderNotifications)
                        <details class="relative">
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-foreground hover:bg-muted sm:px-3 [&::-webkit-details-marker]:hidden">
                                <span class="inline-flex items-center gap-2">
                                    <span>Notifications</span>
                                    @if (($portalUnreadNotificationCount ?? 0) > 0)
                                        <span class="inline-flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-bold text-primary-foreground">{{ $portalUnreadNotificationCount }}</span>
                                    @endif
                                </span>
                            </summary>
                            <div class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-border bg-card py-2 text-sm shadow-lg">
                                <p class="border-b border-border px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Your notifications</p>
                                <div class="max-h-80 overflow-y-auto">
                                    @forelse ($portalHeaderNotifications as $n)
                                        <div class="border-b border-border px-3 py-2 last:border-0 {{ $n->read_at ? 'opacity-70' : 'bg-warning/5' }}">
                                            <p class="font-medium text-foreground">{{ $n->title }}</p>
                                            @if ($n->body)
                                                <p class="mt-1 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($n->body, 160) }}</p>
                                            @endif
                                            <form method="POST" action="{{ route('portal.notifications.read', $n) }}" class="mt-2">
                                                @csrf
                                                <input type="hidden" name="redirect_to" value="{{ route('portal.induction', [], false) }}">
                                                <button type="submit" class="text-xs font-semibold text-primary hover:underline">Open induction</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="px-3 py-4 text-xs text-muted-foreground">You have no notifications.</p>
                                    @endforelse
                                </div>
                                <div class="border-t border-border px-3 py-2">
                                    <a href="{{ route('dashboard') }}#notifications" class="text-xs font-semibold text-primary hover:underline">View on dashboard</a>
                                </div>
                            </div>
                        </details>
                    @endisset
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="whitespace-nowrap rounded-lg px-2 py-2 text-sm font-medium text-muted-foreground hover:bg-muted hover:text-foreground sm:px-3">Sign out</button>
                    </form>
                </div>
            </header>

            <main id="portal-main" class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 lg:p-6" tabindex="-1">
                @if ($errors->any())
                    <div class="portal-card mb-6 border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive" role="status">
                        <p class="font-medium">Please fix the following:</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('success'))
                    <div class="portal-card mb-6 border-accent/40 bg-accent/10 p-4 text-sm text-foreground" role="status">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </main>
            <x-portal-footer />
        </div>
    </div>
    @stack('scripts')
</body>
</html>
