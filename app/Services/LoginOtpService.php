<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoginOtpService
{
    public const TTL_MINUTES = 2;

    public const MAX_ATTEMPTS = 3;

    public const CODE_LENGTH = 6;

    public function syncPendingState(): void
    {
        $token = session('login_otp_token');
        if (! $token) {
            return;
        }

        $payload = Cache::get($this->key($token));
        if (! $payload || now()->timestamp > $payload['expires_at']) {
            $this->forgetPending($token);
        }
    }

    public function hasPending(): bool
    {
        $token = session('login_otp_token');

        return $token && Cache::has($this->key($token));
    }

    public function maskedEmail(): ?string
    {
        $email = session('login_otp_email');
        if (! $email || ! is_string($email)) {
            return null;
        }

        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }

        [$local, $domain] = $parts;
        $visible = $local === '' ? '' : $local[0];

        return $visible.'***@'.$domain;
    }

    public function expiresAtTimestamp(): ?int
    {
        $token = session('login_otp_token');
        if (! $token) {
            return null;
        }
        $payload = Cache::get($this->key($token));

        return isset($payload['expires_at']) ? (int) $payload['expires_at'] : null;
    }

    public function issue(User $user): string
    {
        $token = session('login_otp_token') ?? Str::random(64);
        session([
            'login_otp_token' => $token,
            'login_otp_email' => $user->email,
        ]);

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        return $code;
    }

    /**
     * @return array{status: 'ok', user: User}|array{status: 'invalid'}|array{status: 'expired'}|array{status: 'locked'}|array{status: 'none'}
     */
    public function verify(string $digits): array
    {
        $token = session('login_otp_token');
        if (! $token) {
            return ['status' => 'none'];
        }

        $payload = Cache::get($this->key($token));
        if (! $payload || now()->timestamp > $payload['expires_at']) {
            $this->forgetPending($token);

            return ['status' => 'expired'];
        }

        if (! hash_equals($payload['code_hash'], hash('sha256', $digits))) {
            $payload['attempts']++;
            if ($payload['attempts'] >= self::MAX_ATTEMPTS) {
                $this->forgetPending($token);

                return ['status' => 'locked'];
            }
            $ttl = max(60, $payload['expires_at'] - time());
            Cache::put($this->key($token), $payload, $ttl);

            return ['status' => 'invalid'];
        }

        $user = User::query()->active()->find($payload['user_id']);
        if (! $user) {
            $this->forgetPending($token);

            return ['status' => 'expired'];
        }

        Cache::forget($this->key($token));
        session()->forget(['login_otp_token', 'login_otp_email']);

        return ['status' => 'ok', 'user' => $user];
    }

    /**
     * @return array{code: string, user: User}|null
     */
    public function resend(): ?array
    {
        $token = session('login_otp_token');
        if (! $token) {
            return null;
        }

        $payload = Cache::get($this->key($token));
        if (! $payload || now()->timestamp > $payload['expires_at']) {
            $this->forgetPending($token);

            return null;
        }

        $user = User::query()->active()->find($payload['user_id']);
        if (! $user) {
            $this->forgetPending($token);

            return null;
        }

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        return ['code' => $code, 'user' => $user];
    }

    public function forgetPending(?string $token = null): void
    {
        $token ??= session('login_otp_token');
        if ($token) {
            Cache::forget($this->key($token));
        }
        session()->forget(['login_otp_token', 'login_otp_email']);
    }

    private function key(string $token): string
    {
        return 'login_otp:'.$token;
    }
}
