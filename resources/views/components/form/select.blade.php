@props([
    'name',
    'label',
    'id' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'inputClass' => '',
    'extraSelectAttrs' => '',
])

@php
    $fieldId = $id ?? $name;
    $message = $errors->first($name);
    $hintId = $hint ? "{$fieldId}-hint" : null;
    $errorId = "{$fieldId}-error";
    $describedBy = trim(implode(' ', array_filter([$hintId, $message ? $errorId : null])));
@endphp

<div>
    <label for="{{ $fieldId }}" class="portal-label">{{ $label }}</label>
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        class="portal-input bg-input{{ $message ? ' border-destructive' : '' }}{{ $inputClass !== '' ? ' '.$inputClass : '' }}"
        @if ($extraSelectAttrs !== '') {!! $extraSelectAttrs !!} @endif
        @if ($message) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    >{{ $slot }}</select>
    @if ($hint)
        <p id="{{ $hintId }}" class="portal-field-hint mt-1">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-sm text-destructive" role="alert">{{ $message }}</p>
    @enderror
</div>
