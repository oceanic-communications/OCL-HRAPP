@props([
    'name' => 'body',
    'id' => null,
    'value' => '',
    'maxWords' => \App\Models\InductionSection::BODY_MAX_WORDS,
    'label' => 'Content',
    'rows' => 12,
    'required' => false,
    'placeholder' => '',
])

@php
    $fieldId = $id ?? $name;
    $countId = $fieldId.'-count';
    $errorId = $fieldId.'-client-error';
@endphp

<div>
    <label class="portal-label" for="{{ $fieldId }}">{{ $label }}</label>
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="portal-input text-sm"
        data-rich-editor
        data-max-words="{{ $maxWords }}"
        @if ($required) required @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    >{{ $value }}</textarea>
    <p id="{{ $countId }}" class="mt-1 text-xs text-muted-foreground" data-rich-editor-count aria-live="polite"></p>
    <p id="{{ $errorId }}" class="mt-1 hidden text-sm text-destructive" data-rich-editor-error role="alert"></p>
    @error($name)
        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
        @vite('resources/js/rich-editor.js')
    @endpush
@endonce
