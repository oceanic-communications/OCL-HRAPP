@extends('layouts.ocl-app')

@section('title', 'Induction policies · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="font-heading text-2xl font-bold text-foreground">Induction policies</h1>
        <p class="text-sm text-muted-foreground">Create and deactivate policies, manage draft and published versions, sections, and master PDFs. Every save is audited.</p>
    </div>

    <div class="portal-card p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">Create policy</h2>
        <form action="{{ route('admin.induction.policies.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="portal-label" for="new_policy_name">Name</label>
                <input id="new_policy_name" name="create_name" type="text" class="portal-input" required value="{{ old('create_name') }}" maxlength="255">
            </div>
            <div>
                <label class="portal-label" for="new_policy_slug">Slug (optional)</label>
                <input id="new_policy_slug" name="create_slug" type="text" class="portal-input" value="{{ old('create_slug') }}" maxlength="64" placeholder="auto-generated from name when left blank">
            </div>
            @include('admin.induction.partials.staff-repeat-prompt')
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Create policy</button>
        </form>
    </div>

    @forelse ($policies as $policy)
        <div class="portal-card space-y-6 p-5">
            <div>
                <h2 class="font-heading text-lg font-semibold text-foreground">{{ $policy->name }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">Slug: <code class="rounded bg-muted px-1 py-0.5">{{ $policy->slug }}</code>
                    @if ($policy->is_active)
                        <span class="ml-2 rounded-full bg-accent/15 px-2 py-0.5 text-xs text-accent">Active</span>
                    @else
                        <span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">Deactivated</span>
                    @endif
                </p>
            </div>

            <form action="{{ route('admin.induction.policies.update', $policy) }}" method="POST" class="space-y-4 border-t border-border pt-5">
                @csrf
                @method('PUT')
                <h3 class="text-sm font-semibold text-foreground">Edit policy</h3>
                <div>
                    <label class="portal-label" for="policy_name_{{ $policy->id }}">Name</label>
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
                            Deactivated
                        </label>
                    </div>
                </fieldset>
                @include('admin.induction.partials.staff-repeat-prompt')
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save policy</button>
            </form>

            <form action="{{ route('admin.induction.policies.versions.store', $policy) }}" method="POST" class="space-y-4 border-t border-border pt-5">
                @csrf
                <h3 class="text-sm font-semibold text-foreground">New draft version</h3>
                <div class="flex flex-wrap gap-4">
                    <div class="min-w-[12rem] flex-1">
                        <label class="portal-label" for="version_label_{{ $policy->id }}">Version label</label>
                        <input id="version_label_{{ $policy->id }}" name="version[{{ $policy->id }}][version_label]" type="text" class="portal-input" required maxlength="64" value="{{ old('version.'.$policy->id.'.version_label') }}" placeholder="e.g. Feb 2026">
                    </div>
                    <div class="min-w-[12rem] flex-1">
                        <label class="portal-label" for="effective_date_{{ $policy->id }}">Effective date (optional)</label>
                        <input id="effective_date_{{ $policy->id }}" name="version[{{ $policy->id }}][effective_date]" type="date" class="portal-input" value="{{ old('version.'.$policy->id.'.effective_date') }}">
                    </div>
                </div>
                @include('admin.induction.partials.staff-repeat-prompt')
                <button type="submit" class="rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:bg-secondary/90">Create draft version</button>
            </form>

            <div class="border-t border-border pt-5">
                <h3 class="text-sm font-semibold text-foreground">Versions</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($policy->versions as $v)
                        <li class="flex flex-wrap items-center justify-between gap-2 border-b border-border py-2 last:border-0">
                            <span>
                                <span class="font-medium">{{ $v->version_label }}</span>
                                @if ($v->published_at)
                                    <span class="ml-2 rounded-full bg-accent/15 px-2 py-0.5 text-xs text-accent">Published {{ $v->published_at->format('Y-m-d') }}</span>
                                @else
                                    <span class="ml-2 text-xs text-muted-foreground">Draft</span>
                                @endif
                            </span>
                            <a href="{{ route('admin.induction.versions.show', $v) }}" class="text-sm font-medium text-primary hover:underline">Edit version</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted-foreground">No policies defined.</p>
    @endforelse
</div>
@endsection
