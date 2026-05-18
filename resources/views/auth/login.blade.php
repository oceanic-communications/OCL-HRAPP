@extends('layouts.guest')

@section('title', 'Sign in · '.config('app.name'))

@section('content')
<div class="flex min-h-screen items-center justify-center bg-background p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold text-foreground">{{ config('app.name') }}</h1>
            <p class="mt-2 text-sm text-muted-foreground">HR portal</p>
        </div>

        <div class="portal-card overflow-hidden">
            <div class="relative z-10 border-b border-border px-6 py-5">
                @if ($showOtpStep ?? false)
                    <h2 class="text-xl font-semibold text-foreground">Check your email</h2>
                    <p class="mt-1 text-sm text-muted-foreground">We sent a 6-digit code to <span class="font-medium text-foreground">{{ $maskedEmail }}</span>. It expires in {{ \App\Services\LoginOtpService::TTL_MINUTES }} min.</p>
                @else
                    <h2 class="text-xl font-semibold text-foreground">Welcome back</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Enter your email and we’ll send a one-time code to sign in</p>
                @endif
            </div>
            <div class="relative z-10 p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-primary/30 bg-primary/5 p-3 text-sm text-foreground" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        id="guest-form-errors-summary"
                        class="mb-4 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive"
                        role="status"
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($showOtpStep ?? false)
                    <p id="otp-countdown" class="mb-4 text-center text-sm text-muted-foreground" data-expires-at="{{ $expiresAtTimestamp ?? '' }}" hidden></p>

                    @if (session('otp_resend_feedback'))
                        @php $otpResendType = session('otp_resend_feedback_type', 'success'); @endphp
                        <div
                            class="mb-4 rounded-lg border p-3 text-sm {{ $otpResendType === 'error' ? 'border-destructive/40 bg-destructive/5 text-destructive' : 'border-primary/30 bg-primary/5 text-foreground' }}"
                            role="status"
                            aria-live="polite"
                        >
                            {{ session('otp_resend_feedback') }}
                        </div>
                    @endif

                    <div class="relative" data-wff-form-loading-root>
                        <div id="otp-verify-section">
                            <form id="login-verify-otp-form" action="{{ route('login.verify', absolute: false) }}" method="POST" class="space-y-4" data-wff-form-loading>
                                @csrf
                                <x-form.text-input
                                    name="code"
                                    label="6-digit code"
                                    :value="old('code')"
                                    required
                                    autocomplete="one-time-code"
                                    inputmode="numeric"
                                    maxlength="12"
                                    placeholder="000000"
                                    inputClass="text-center text-lg tracking-[0.35em] font-medium"
                                />
                                <button type="submit" id="login-verify-otp-submit" data-wff-loading-message="Verifying code…" class="w-full rounded-lg bg-primary py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover hover:text-primary">Verify and sign in</button>
                            </form>
                        </div>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4">
                        <div id="otp-resend-section">
                            <form action="{{ route('login.resend-otp', absolute: false) }}" method="POST" data-wff-form-loading>
                                @csrf
                                <button type="submit" data-wff-loading-message="Sending code…" class="text-sm font-medium text-primary hover:underline">Resend code</button>
                            </form>
                        </div>
                        <div class="sm:ml-auto">
                            <form action="{{ route('login.cancel-otp', absolute: false) }}" method="POST" data-wff-form-loading>
                                @csrf
                                <button type="submit" data-wff-loading-message="Please wait…" class="text-sm text-muted-foreground hover:text-foreground hover:underline">Use a different account</button>
                            </form>
                        </div>
                    </div>
                        <x-form.loading-overlay message="Verifying code…" />
                    </div>
                @else
                    <div class="relative" data-wff-form-loading-root>
                    <form action="{{ route('login.post', absolute: false) }}" method="POST" class="space-y-4" data-wff-form-loading>
                        @csrf
                        <x-form.text-input
                            name="email"
                            label="Email address"
                            type="email"
                            :value="old('email')"
                            required
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                        <x-recaptcha />
                        <button type="submit" data-wff-loading-message="Sending sign-in code…" class="w-full rounded-lg bg-primary py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover hover:text-primary">Send sign-in code</button>
                    </form>
                    <x-form.loading-overlay message="Sending sign-in code…" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if (($showOtpStep ?? false) && !empty($expiresAtTimestamp))
<script>
(function () {
    var el = document.getElementById('otp-countdown');
    if (!el) return;
    var expires = parseInt(el.getAttribute('data-expires-at'), 10);
    if (!expires) return;
    el.hidden = false;
    function tick() {
        var now = Math.floor(Date.now() / 1000);
        var left = expires - now;
        if (left <= 0) {
            el.textContent = 'This code has expired. Use the link below to sign in again.';
            el.classList.add('text-destructive');
            var verifySection = document.getElementById('otp-verify-section');
            var resendSection = document.getElementById('otp-resend-section');
            if (verifySection) verifySection.hidden = true;
            if (resendSection) resendSection.hidden = true;
            return;
        }
        var m = Math.floor(left / 60);
        var s = left % 60;
        el.textContent = 'Time left: ' + m + ':' + (s < 10 ? '0' : '') + s;
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endif

@if ($showOtpStep ?? false)
<script>
(function () {
    var input = document.getElementById('code');
    var form = document.getElementById('login-verify-otp-form');
    var submitBtn = document.getElementById('login-verify-otp-submit');
    if (!input || !form) return;
    var submitting = false;
    function digitsOnly(value) {
        return (String(value).match(/\d/g) || []).join('');
    }
    function showAutoSubmitLoading() {
        input.setAttribute('readonly', 'readonly');
        if (submitBtn) submitBtn.disabled = true;
    }
    function trySubmit() {
        if (submitting) return;
        var digits = digitsOnly(input.value);
        if (digits.length !== 6) return;
        submitting = true;
        input.value = digits;
        showAutoSubmitLoading();
        requestAnimationFrame(function () {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
    form.addEventListener('submit', function (e) {
        if (submitting) return;
        if (digitsOnly(input.value).length === 6) {
            submitting = true;
            showAutoSubmitLoading();
        }
    });
    input.addEventListener('input', trySubmit);
    input.addEventListener('change', trySubmit);
    input.addEventListener('paste', function () {
        requestAnimationFrame(trySubmit);
    });
})();
</script>
@endif
@endsection
