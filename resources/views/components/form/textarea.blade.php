@props([
    'name',
    'label',
    'id' => null,
    'value' => '',
    'required' => false,
    'rows' => null,
    'maxlength' => null,
    'placeholder' => null,
    'hint' => null,
    'inputClass' => '',
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
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($rows !== null) rows="{{ $rows }}" @endif
        @if ($maxlength !== null) maxlength="{{ $maxlength }}" @endif
        @if ($placeholder !== null && $placeholder !== '') placeholder="{{ $placeholder }}" @endif
        class="portal-input bg-input{{ $message ? ' border-destructive' : '' }}{{ $inputClass !== '' ? ' '.$inputClass : '' }}"
        @if ($message) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    >{{ $value }}</textarea>
    @if ($hint)
        <p id="{{ $hintId }}" class="portal-field-hint mt-1">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-sm text-destructive" role="alert">{{ $message }}</p>
    @enderror
</div>
