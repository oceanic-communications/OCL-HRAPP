@extends('layouts.portal')

@section('title', 'Induction policies · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Induction policies</h1>
        <p class="text-sm text-muted-foreground">Manage policy sections staff complete during induction.</p>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    @if ($canReadPolicies ?? false)
        @forelse ($policies as $policy)
            @php
                $version = $policy->publishedVersion() ?? $policy->versions->first();
                $sections = $version?->sections ?? collect();
            @endphp
            <div class="portal-card overflow-hidden">
                @if ($portalCap?->inductionPolicyUpdate ?? false)
                    <form action="{{ route('admin.induction.policies.update', $policy) }}" method="POST" class="border-b border-border bg-muted/30 px-5 py-4">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-6">
                            <div class="min-w-0 flex-1">
                                <label class="portal-label" for="policy_name_{{ $policy->id }}">Policy name</label>
                                <input id="policy_name_{{ $policy->id }}" name="policy[{{ $policy->id }}][name]" type="text" class="portal-input mt-1" required value="{{ old('policy.'.$policy->id.'.name', $policy->name) }}" maxlength="255">
                            </div>
                            <div class="shrink-0">
                                <span class="portal-label">Status</span>
                                <div class="mt-2 flex flex-wrap gap-4 text-sm text-foreground">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input type="radio" name="policy[{{ $policy->id }}][is_active]" value="1" class="h-4 w-4 border-border" @checked(old('policy.'.$policy->id.'.is_active', $policy->is_active ? '1' : '0') === '1') required>
                                        Active
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input type="radio" name="policy[{{ $policy->id }}][is_active]" value="0" class="h-4 w-4 border-border" @checked(old('policy.'.$policy->id.'.is_active', $policy->is_active ? '1' : '0') === '0') required>
                                        Inactive
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="shrink-0 rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:bg-secondary/90 lg:mb-0.5">Save</button>
                        </div>
                    </form>
                @else
                    <div class="border-b border-border bg-muted/30 px-5 py-4">
                        <h2 class="font-heading text-lg font-semibold text-foreground">{{ $policy->name }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $policy->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                @endif

                <div class="space-y-4 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-foreground">Sections</h2>
                        @if ($portalCap?->inductionPolicyCreate ?? false)
                            <a href="{{ route('admin.induction.policies.sections.create', $policy) }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                                Add section
                            </a>
                        @endif
                    </div>

                    @if ($sections->isEmpty())
                        <p class="text-sm text-muted-foreground">No sections yet.@if ($portalCap?->inductionPolicyCreate ?? false) Add the first section to get started.@endif</p>
                    @else
                        <div class="overflow-x-auto rounded-lg border border-border">
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Order</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Title</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-foreground">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    @foreach ($sections as $section)
                                        <tr class="{{ $section->isArchived() ? 'bg-muted/20 text-muted-foreground' : '' }}">
                                            <td class="whitespace-nowrap px-4 py-3">{{ $section->sort_order }}</td>
                                            <td class="px-4 py-3 font-medium">{{ $section->title }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if ($section->isArchived())
                                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">Archived</span>
                                                @else
                                                    <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <a href="{{ route('admin.induction.policies.sections.show', [$policy, $section]) }}" class="font-medium text-primary hover:underline">View</a>
                                                    @if (! $section->isArchived() && ($portalCap?->inductionPolicyUpdate ?? false))
                                                        <a href="{{ route('admin.induction.policies.sections.edit', [$policy, $section]) }}" class="font-medium text-primary hover:underline">Edit</a>
                                                    @endif
                                                    @if (! $section->isArchived() && ($portalCap?->inductionPolicyArchive ?? false))
                                                        <form action="{{ route('admin.induction.policies.sections.archive', [$policy, $section]) }}" method="POST" class="inline" onsubmit="return confirm('Archive this section? Staff will no longer see it during induction.');">
                                                            @csrf
                                                            <button type="submit" class="font-medium text-destructive hover:underline">Archive</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">No policies configured.</p>
        @endforelse
    @else
        <div class="portal-card p-8 text-center text-sm text-muted-foreground">You do not have permission to view this area.</div>
    @endif
</div>
@endsection
