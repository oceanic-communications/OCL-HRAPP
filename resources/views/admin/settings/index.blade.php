@extends('layouts.portal')

@section('title', 'Settings · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Settings</h1>
        <p class="text-sm text-muted-foreground">Configure HR portal options for induction and policies.</p>
    </div>

    <div class="space-y-3">
        @if ($portalCap?->inductionPolicyRead ?? false)
            <a href="{{ route('admin.settings.numbering') }}" class="portal-card flex items-start justify-between gap-4 p-5 transition-colors hover:bg-muted/30">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">Policy numbering</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Section, clause, and sub-clause numbering styles for induction policies.</p>
                </div>
                <span class="shrink-0 text-sm font-medium text-primary">Open →</span>
            </a>
        @endif
    </div>
</div>
@endsection
