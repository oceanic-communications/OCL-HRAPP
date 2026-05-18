@props([
    'message' => 'Please wait…',
])
<div
    data-wff-form-loading-overlay
    class="pointer-events-none absolute inset-0 z-30 hidden flex-col items-center justify-center gap-4 rounded-[inherit] bg-background/90 text-center backdrop-blur-[2px]"
    role="status"
    aria-live="polite"
    aria-hidden="true"
    data-default-message="{{ e($message) }}"
>
    <div class="relative flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center" aria-hidden="true">
        <span class="absolute inset-0 animate-spin rounded-full border-2 border-[#c3cf21] border-t-transparent"></span>
        <img
            src="{{ asset('oceanic-logo.png') }}"
            alt=""
            width="46"
            height="46"
            class="relative h-10 w-10 rounded-full bg-white object-contain p-0.5 shadow-sm"
        />
    </div>
    <p class="px-4 text-sm font-medium text-foreground" data-wff-form-loading-message>{{ $message }}</p>
</div>
