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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-background font-sans antialiased">
    <a href="#main-content" class="portal-skip-link">Skip to main content</a>
    <header class="sticky top-0 z-50 border-b border-border bg-card shadow-sm">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 md:px-6">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-foreground">{{ config('app.name') }}</a>
            <nav class="flex flex-wrap items-center gap-3 text-sm font-medium">
                <a href="{{ route('dashboard') }}" class="text-foreground hover:text-primary">Dashboard</a>
                @include('components.portal-admin-nav-links')
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-muted-foreground hover:text-foreground">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    <main id="main-content" class="mx-auto max-w-6xl px-4 py-6 md:px-6 md:py-8" tabindex="-1">
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
    @stack('scripts')
</body>
</html>
