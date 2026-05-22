@props([
    'acknowledgementMode' => \App\Support\InductionAcknowledgementMode::READ_ONLY,
    'showNotify' => true,
])

@php
    $mode = old('acknowledgement_mode', $acknowledgementMode);
@endphp

<div class="rounded-lg border border-border bg-muted/20 p-4 space-y-4">
    <p class="text-sm font-semibold text-foreground">Employee acknowledgement</p>
    <p class="text-xs text-muted-foreground">Choose how staff must confirm this content. One of Read and sign or Read only is required.</p>

    <div class="space-y-2">
        <label class="flex cursor-pointer items-start gap-3">
            <input
                type="radio"
                name="acknowledgement_mode"
                value="{{ \App\Support\InductionAcknowledgementMode::READ_AND_SIGN }}"
                class="mt-1 h-4 w-4 border-border text-primary"
                @checked($mode === \App\Support\InductionAcknowledgementMode::READ_AND_SIGN)
                required
            >
            <span>
                <span class="text-sm font-medium text-foreground">Read and sign</span>
                <span class="mt-0.5 block text-xs text-muted-foreground">Staff must declare they have read the content and provide a digital signature.</span>
            </span>
        </label>
        <label class="flex cursor-pointer items-start gap-3">
            <input
                type="radio"
                name="acknowledgement_mode"
                value="{{ \App\Support\InductionAcknowledgementMode::READ_ONLY }}"
                class="mt-1 h-4 w-4 border-border text-primary"
                @checked($mode === \App\Support\InductionAcknowledgementMode::READ_ONLY)
                required
            >
            <span>
                <span class="text-sm font-medium text-foreground">Read only</span>
                <span class="mt-0.5 block text-xs text-muted-foreground">Staff must declare they have read the content; no signature is required.</span>
            </span>
        </label>
    </div>
    @error('acknowledgement_mode')
        <p class="text-sm text-destructive">{{ $message }}</p>
    @enderror

    @if ($showNotify)
        <div class="border-t border-border pt-4">
            <label class="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    name="notify_employees"
                    value="1"
                    class="mt-1 h-4 w-4 rounded border-border text-primary"
                    @checked(old('notify_employees'))
                >
                <span>
                    <span class="text-sm font-medium text-foreground">Notify</span>
                    <span class="mt-0.5 block text-xs text-muted-foreground">Send a dashboard alert and email to all active employees about this change when you save.</span>
                </span>
            </label>
            @error('notify_employees')
                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>
