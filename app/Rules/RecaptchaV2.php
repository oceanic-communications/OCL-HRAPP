<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecaptchaV2 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('recaptcha.enabled')) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the reCAPTCHA verification.');

            return;
        }

        $secret = config('recaptcha.secret_key');
        if (! is_string($secret) || $secret === '') {
            Log::error('reCAPTCHA is enabled but RECAPTCHA_SECRET_KEY is not set.');
            $fail('Sign-in verification is not configured. Please contact support.');

            return;
        }

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable $e) {
            Log::warning('reCAPTCHA verification request failed', ['exception' => $e]);
            $fail('Unable to verify reCAPTCHA. Please try again.');

            return;
        }

        if (! $response->successful()) {
            Log::warning('reCAPTCHA verification HTTP error', ['status' => $response->status()]);
            $fail('Unable to verify reCAPTCHA. Please try again.');

            return;
        }

        $body = $response->json();
        if (! is_array($body) || empty($body['success'])) {
            Log::notice('reCAPTCHA verification rejected', ['error-codes' => $body['error-codes'] ?? null]);
            $fail('reCAPTCHA verification failed. Please try again.');
        }
    }
}
