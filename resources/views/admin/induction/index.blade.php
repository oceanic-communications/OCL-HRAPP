@extends('layouts.portal')

@section('title', 'Induction policies · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Induction policies</h1>
        <p class="text-sm text-muted-foreground">Add a policy, write sections, and save. Staff complete each section by reading the content, confirming, and signing.</p>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    <div class="portal-card p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">New policy</h2>
        <form action="{{ route('admin.induction.policies.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="portal-label" for="new_policy_name">Policy name</label>
                <input id="new_policy_name" name="create_name" type="text" class="portal-input" required value="{{ old('create_name') }}" maxlength="255" placeholder="e.g. Productivity principles &amp; policies">
            </div>
            @include('admin.induction.partials.staff-repeat-prompt')
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Create policy</button>
        </form>
    </div>

    @forelse ($policies as $policy)
        @php
            $version = $policy->publishedVersion() ?? $policy->versions->first();
            $sections = $version?->sections ?? collect();
        @endphp
        <div class="portal-card space-y-6 p-5">
            <form action="{{ route('admin.induction.policies.update', $policy) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2 class="font-heading text-lg font-semibold text-foreground">{{ $policy->name }}</h2>
                    @if ($policy->is_active)
                        <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-medium text-accent">Active</span>
                    @else
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">Inactive</span>
                    @endif
                </div>
                <div>
                    <label class="portal-label" for="policy_name_{{ $policy->id }}">Policy name</label>
                    <input id="policy_name_{{ $policy->id }}" name="policy[{{ $policy->id }}][name]" type="text" class="portal-input" required value="{{ old('policy.'.$policy->id.'.name', $policy->name) }}" maxlength="255">
                </div>
                <fieldset>
                    <legend class="portal-label">Status</legend>
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
                </fieldset>
                @include('admin.induction.partials.staff-repeat-prompt')
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save policy</button>
            </form>

            <div class="space-y-4 border-t border-border pt-5">
                <h3 class="text-sm font-semibold text-foreground">Sections</h3>

                @foreach ($sections as $section)
                    <div class="rounded-lg border border-border p-4">
                        <form action="{{ route('admin.induction.policies.sections.update', [$policy, $section]) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="w-20">
                                    <label class="portal-label" for="sort{{ $section->id }}">Order</label>
                                    <input id="sort{{ $section->id }}" name="sort_order" type="number" class="portal-input" required value="{{ old('sort_order', $section->sort_order) }}" min="0" max="9999">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label class="portal-label" for="title{{ $section->id }}">Title</label>
                                    <input id="title{{ $section->id }}" name="title" type="text" class="portal-input" required value="{{ old('title', $section->title) }}">
                                </div>
                            </div>
                            <div>
                                <label class="portal-label" for="body{{ $section->id }}">Content</label>
                                <textarea id="body{{ $section->id }}" name="body" rows="8" class="portal-input text-sm" required>{{ old('body', $section->body) }}</textarea>
                            </div>
                            @include('admin.induction.partials.staff-repeat-prompt')
                            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save section</button>
                        </form>
                        <form action="{{ route('admin.induction.policies.sections.destroy', [$policy, $section]) }}" method="POST" class="mt-3" onsubmit="return confirm('Delete this section?');">
                            @csrf
                            @method('DELETE')
                            @include('admin.induction.partials.staff-repeat-prompt')
                            <button type="submit" class="text-sm font-medium text-destructive hover:underline">Delete section</button>
                        </form>
                    </div>
                @endforeach

                <form action="{{ route('admin.induction.policies.sections.store', $policy) }}" method="POST" class="space-y-4 rounded-lg border border-dashed border-border p-4">
                    @csrf
                    <p class="text-sm font-medium text-foreground">Add section</p>
                    <div>
                        <label class="portal-label" for="new_title_{{ $policy->id }}">Title</label>
                        <input id="new_title_{{ $policy->id }}" name="title" type="text" class="portal-input" required value="{{ old('title') }}">
                    </div>
                    <div>
                        <label class="portal-label" for="new_body_{{ $policy->id }}">Content</label>
                        <textarea id="new_body_{{ $policy->id }}" name="body" rows="6" class="portal-input text-sm" required placeholder="Policy text employees will read for this section.">{{ old('body') }}</textarea>
                    </div>
                    @include('admin.induction.partials.staff-repeat-prompt')
                    <button type="submit" class="rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:bg-secondary/90">Add section</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted-foreground">No policies yet. Create one above.</p>
    @endforelse
</div>
@endsection
