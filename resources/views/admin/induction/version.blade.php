@extends('layouts.ocl-app')

@section('title', $version->version_label.' · Induction · '.config('app.name'))

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ route('admin.induction.index') }}" class="text-sm font-medium text-primary hover:underline">← All policies</a>
        <h1 class="font-heading mt-2 text-2xl font-bold text-foreground">{{ $version->policy->name }}</h1>
        <p class="text-sm text-muted-foreground">Version {{ $version->version_label }}</p>
    </div>

    @if (session('success'))
        <div class="portal-card border-accent/40 bg-accent/10 p-4 text-sm text-foreground">{{ session('success') }}</div>
    @endif

    <div class="portal-card p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">Publishing</h2>
        <p class="mt-1 text-sm text-muted-foreground">Only one published version applies to new employee enrollments.</p>
        @if ($version->published_at)
            <p class="mt-3 text-sm font-medium text-accent">This version is currently published.</p>
        @else
            <form action="{{ route('admin.induction.versions.publish', $version) }}" method="POST" class="mt-3 space-y-4">
                @csrf
                @include('admin.induction.partials.staff-repeat-prompt')
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Publish this version</button>
            </form>
        @endif
    </div>

    <div class="portal-card p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">Master policy PDF</h2>
        <p class="mt-1 text-sm text-muted-foreground">Optional reference document (max 20&nbsp;MB).</p>
        @if ($version->policy_pdf_path)
            <p class="mt-2 text-sm text-foreground">A file is attached to this version.</p>
            <form action="{{ route('admin.induction.versions.master-pdf.destroy', $version) }}" method="POST" class="mt-2 space-y-4">
                @csrf
                @method('DELETE')
                @include('admin.induction.partials.staff-repeat-prompt')
                <button type="submit" class="text-sm font-medium text-destructive hover:underline">Remove PDF</button>
            </form>
        @endif
        <form action="{{ route('admin.induction.versions.master-pdf.store', $version) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <input type="file" name="policy_pdf" accept=".pdf" required class="block w-full max-w-md text-sm">
            @include('admin.induction.partials.staff-repeat-prompt')
            <button type="submit" class="rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:bg-secondary/90">Upload PDF</button>
        </form>
    </div>

    <div class="portal-card p-5">
        <h2 class="font-heading text-lg font-semibold text-foreground">Add section</h2>
        <form action="{{ route('admin.induction.versions.sections.store', $version) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="portal-label" for="new_title">Title</label>
                <input id="new_title" name="title" type="text" class="portal-input" required value="{{ old('title') }}">
            </div>
            <div>
                <label class="portal-label" for="new_body">Body</label>
                <textarea id="new_body" name="body" rows="6" class="portal-input" required>{{ old('body') }}</textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="requires_signature" id="new_sig" value="1" class="h-4 w-4 rounded border-border" {{ old('requires_signature') ? 'checked' : '' }}>
                <label for="new_sig" class="text-sm text-foreground">Requires digital signature</label>
            </div>
            <div>
                <label class="portal-label" for="new_hint">Acknowledgement hint (optional)</label>
                <textarea id="new_hint" name="acknowledgement_hint" rows="2" class="portal-input">{{ old('acknowledgement_hint') }}</textarea>
            </div>
            @include('admin.induction.partials.staff-repeat-prompt')
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Add section</button>
        </form>
    </div>

    <div class="space-y-6">
        <h2 class="font-heading text-lg font-semibold text-foreground">Sections (order)</h2>
        @foreach ($version->sections as $section)
            <div class="portal-card p-5">
                <form action="{{ route('admin.induction.versions.sections.update', [$version, $section]) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="min-w-[6rem]">
                            <label class="portal-label" for="sort{{ $section->id }}">Order</label>
                            <input id="sort{{ $section->id }}" name="sort_order" type="number" class="portal-input" required value="{{ old('sort_order', $section->sort_order) }}">
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="portal-label" for="title{{ $section->id }}">Title</label>
                            <input id="title{{ $section->id }}" name="title" type="text" class="portal-input" required value="{{ old('title', $section->title) }}">
                        </div>
                    </div>
                    <div>
                        <label class="portal-label" for="body{{ $section->id }}">Body</label>
                        <textarea id="body{{ $section->id }}" name="body" rows="8" class="portal-input" required>{{ old('body', $section->body) }}</textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="requires_signature" id="sig{{ $section->id }}" value="1" class="h-4 w-4 rounded border-border" {{ old('requires_signature', $section->requires_signature) ? 'checked' : '' }}>
                        <label for="sig{{ $section->id }}" class="text-sm text-foreground">Requires digital signature</label>
                    </div>
                    <div>
                        <label class="portal-label" for="hint{{ $section->id }}">Acknowledgement hint</label>
                        <textarea id="hint{{ $section->id }}" name="acknowledgement_hint" rows="2" class="portal-input">{{ old('acknowledgement_hint', $section->acknowledgement_hint) }}</textarea>
                    </div>
                    @include('admin.induction.partials.staff-repeat-prompt')
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save section</button>
                    </div>
                </form>
                <form action="{{ route('admin.induction.versions.sections.destroy', [$version, $section]) }}" method="POST" class="mt-3 space-y-4" onsubmit="return confirm('Delete this section?');">
                    @csrf
                    @method('DELETE')
                    @include('admin.induction.partials.staff-repeat-prompt')
                    <button type="submit" class="rounded-lg border border-destructive/40 px-4 py-2 text-sm font-semibold text-destructive hover:bg-destructive/10">Delete section</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
