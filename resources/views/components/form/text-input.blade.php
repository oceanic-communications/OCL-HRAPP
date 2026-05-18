@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'maxlength' => null,
    'placeholder' => null,
    'inputmode' => null,
    'hint' => null,
    'step' => null,
    'min' => null,
    'max' => null,
    'pattern' => null,
    'inputClass' => '',
    'autofocus' => false,
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
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($required) required @endif
        @if ($autocomplete !== null && $autocomplete !== '') autocomplete="{{ $autocomplete }}" @endif
        @if ($maxlength !== null) maxlength="{{ $maxlength }}" @endif
        @if ($placeholder !== null && $placeholder !== '') placeholder="{{ $placeholder }}" @endif
        @if ($inputmode !== null && $inputmode !== '') inputmode="{{ $inputmode }}" @endif
        @if ($step !== null) step="{{ $step }}" @endif
        @if ($min !== null) min="{{ $min }}" @endif
        @if ($max !== null) max="{{ $max }}" @endif
        @if ($pattern !== null && $pattern !== '') pattern="{{ $pattern }}" @endif
        class="portal-input bg-input{{ $message ? ' border-destructive' : '' }}{{ $inputClass !== '' ? ' '.$inputClass : '' }}"
        @if ($autofocus) autofocus @endif
        @if ($message) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    />
    @if ($hint)
        <p id="{{ $hintId }}" class="portal-field-hint mt-1">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-sm text-destructive" role="alert">{{ $message }}</p>
    @enderror
</div>
