<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Policy builder · '.config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/policy-document-builder.js'])
    @stack('head')
</head>
<body class="policy-builder min-h-dvh bg-[#F4F5F7] font-sans antialiased text-slate-800">
    <div class="flex min-h-dvh">
        <x-policy-builder.sidebar :policy="$builderPolicy ?? null" />

        <div class="flex min-w-0 flex-1 flex-col">
            @yield('builder-header')

            <main class="min-h-0 flex-1 overflow-auto p-4 lg:p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
