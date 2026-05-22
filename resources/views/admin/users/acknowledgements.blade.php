@extends('layouts.portal')

@section('title', $user->name.' · Acknowledgement history · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary hover:underline">← Back to employees</a>
        <h1 class="mt-3 font-heading text-2xl font-bold text-foreground">Acknowledgement history</h1>
        <p class="text-sm text-muted-foreground">{{ $user->name }} · {{ $user->email }}</p>
        <p class="mt-2 text-sm text-muted-foreground">
            Induction acknowledgements and digital signatures recorded for this employee across all policy versions.
        </p>
    </div>

    <div class="portal-card space-y-4 p-5">
        <x-employee-acknowledgement-history-table :completions="$completions" />
    </div>
</div>
@endsection
