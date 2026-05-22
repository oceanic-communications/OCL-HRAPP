@extends('layouts.portal')

@section('title', 'Acknowledgement history · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Acknowledgement history</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Your recorded induction acknowledgements and digital signatures across all policy versions.
        </p>
    </div>

    <div class="portal-card space-y-4 p-5">
        <x-employee-acknowledgement-history-table :completions="$completions" />
    </div>
</div>
@endsection
