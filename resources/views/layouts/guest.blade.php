<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in · '.config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="{{ asset('oceanic-logo.png') }}" type="image/png" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('oceanic-logo.png') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        media="print"
        onload="this.media='all'"
    />
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    </noscript>
    @vite(['resources/css/app.css', 'resources/js/guest.js'])
</head>
<body class="min-h-screen bg-background font-sans antialiased">
    <a href="#main-content" class="portal-skip-link">Skip to main content</a>
    <main id="main-content" class="min-h-screen" tabindex="-1">
        @yield('content')
    </main>
    <x-portal-footer />
    @stack('scripts')
</body>
</html>
