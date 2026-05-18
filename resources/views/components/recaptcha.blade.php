@if (config('recaptcha.enabled') && filled(config('recaptcha.site_key')))
    <div class="flex flex-col items-center gap-2 py-1">
        <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
        @error('g-recaptcha-response')
            <p class="text-center text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>
    @once
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endpush
    @endonce
@endif
