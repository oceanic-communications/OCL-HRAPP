@extends('layouts.portal')

@section('title', 'Policies · '.config('app.name'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="font-heading text-2xl font-bold text-foreground">Policies</h1>
            <p class="text-sm text-muted-foreground">Manage induction policies and their clauses (e.g. HR, IT, OHS).</p>
        </div>
        @if ($portalCap?->inductionPolicyCreate ?? false)
            <details class="group w-full md:w-auto" @if ($errors->has('create_name') || $errors->has('create_abbreviation')) open @endif>
                <summary class="inline-flex w-full cursor-pointer list-none items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 md:w-auto [&::-webkit-details-marker]:hidden">
                    Add policy
                </summary>
                <form action="{{ route('admin.induction.policies.store') }}" method="POST" class="portal-card mt-3 space-y-3 p-4 md:min-w-[20rem]">
                    @csrf
                    <div>
                        <label class="portal-label" for="create_abbreviation">Abbreviation</label>
                        <input id="create_abbreviation" name="create_abbreviation" type="text" class="portal-input mt-1 uppercase" required maxlength="{{ \App\Models\InductionPolicy::ABBREVIATION_MAX_LENGTH }}" placeholder="e.g. HR, IT, OHS" value="{{ old('create_abbreviation') }}" autocapitalize="characters" spellcheck="false">
                        @error('create_abbreviation')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="portal-label" for="create_name">Policy name</label>
                        <input id="create_name" name="create_name" type="text" class="portal-input mt-1" required maxlength="255" placeholder="e.g. Human Resources policy" value="{{ old('create_name') }}">
                        @error('create_name')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Create policy</button>
                </form>
            </details>
        @endif
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    @if ($canReadPolicies ?? false)
        @if ($policies->isEmpty())
            <div class="portal-card p-8 text-center text-sm text-muted-foreground">
                No policies configured yet.
                @if ($portalCap?->inductionPolicyCreate ?? false)
                    Use <span class="font-medium text-foreground">Add policy</span> to create one (e.g. HR, IT, OHS).
                @endif
            </div>
        @else
            <div class="portal-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Abbreviation</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Policy</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Clauses</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($policies as $policy)
                                @php
                                    $version = $policy->versions->first();
                                    $clauseCount = (int) ($version?->sections_count ?? 0);
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-foreground">{{ $policy->abbreviation }}</td>
                                    <td class="px-4 py-3 font-medium text-foreground">{{ $policy->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($policy->is_active)
                                            <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
                                        @else
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $clauseCount }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <a href="{{ route('admin.induction.policies.show', $policy) }}" class="font-medium text-primary hover:underline">Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="portal-card p-8 text-center text-sm text-muted-foreground">You do not have permission to view this area.</div>
    @endif
</div>
@endsection
