@extends('layouts.portal')

@section('title', $portalPageTitle.' · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    <div class="min-w-0">
        <h1 class="font-heading text-pretty text-xl font-bold text-foreground sm:text-2xl">{{ $portalPageTitle }}</h1>
        <p class="mt-1 max-w-2xl text-pretty text-sm text-muted-foreground sm:text-base">{{ $portalPageIntro }}</p>
    </div>
    <div class="portal-card max-w-2xl p-4 text-sm text-muted-foreground sm:p-6">
        <p>This screen is served from the Laravel employee portal. Connect your domain services and policies here when backend APIs are ready.</p>
    </div>
</div>
@endsection
