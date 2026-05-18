<?php

namespace App\Http\Controllers;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Rules\RecaptchaV2;
use App\Services\LoginOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(Request $request, LoginOtpService $loginOtp)
    {
        $loginOtp->syncPendingState();

        return response()
            ->view('auth.login', [
                'showOtpStep' => $loginOtp->hasPending(),
                'maskedEmail' => $loginOtp->maskedEmail(),
                'expiresAtTimestamp' => $loginOtp->expiresAtTimestamp(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function login(Request $request, LoginOtpService $loginOtp)
    {
        $credentials = $this->validateWithRecaptcha($request, [
            'email' => 'required|email',
        ]);

        $email = $credentials['email'];

        $user = User::query()->active()->where('email', $email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'We could not find an active account with that email address.',
            ])->onlyInput('email');
        }

        $code = $loginOtp->issue($user);
        $request->session()->put('login_otp_verify_route', 'login.verify');

        try {
            Mail::to($user->email)->send(new LoginOtpMail($code, $user->name));
        } catch (Throwable $e) {
            Log::error('Login OTP email failed', ['exception' => $e]);
            $loginOtp->forgetPending();

            return back()
                ->withErrors([
                    'email' => 'We could not send the sign-in email. Please try again shortly or contact support.',
                ])
                ->onlyInput('email');
        }

        return redirect()
            ->route('login')
            ->with('success', 'We sent a 6-digit code to your email. Enter it below to sign in.');
    }

    public function verifyLoginOtp(Request $request, LoginOtpService $loginOtp)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $digits = preg_replace('/\D/', '', (string) $request->input('code'));
        if (strlen($digits) !== LoginOtpService::CODE_LENGTH) {
            return back()->withErrors([
                'code' => 'Enter the 6-digit code from your email.',
            ])->onlyInput('code');
        }

        $result = $loginOtp->verify($digits);

        if ($result['status'] === 'ok') {
            Auth::login($result['user']);
            $request->session()->regenerate();
            $request->session()->forget('login_otp_verify_route');

            return redirect()
                ->intended($result['user']->homeRoute())
                ->with('success', 'Logged in successfully!');
        }

        if ($result['status'] === 'invalid') {
            return back()
                ->withErrors([
                    'code' => 'That code is not valid. Please try again.',
                ])
                ->onlyInput('code');
        }

        if ($result['status'] === 'locked') {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Too many incorrect attempts. Please sign in again.',
                ])
                ->onlyInput('email');
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Your sign-in code expired or this session is no longer valid. Please sign in again.',
            ])
            ->onlyInput('email');
    }

    public function resendLoginOtp(LoginOtpService $loginOtp)
    {
        $sent = $loginOtp->resend();
        if (! $sent) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'The code could not be resent. Your verification session expired—please sign in again.',
                ])
                ->onlyInput('email');
        }

        try {
            Mail::to($sent['user']->email)->send(new LoginOtpMail($sent['code'], $sent['user']->name));
        } catch (Throwable $e) {
            Log::error('Login OTP resend email failed', ['exception' => $e]);

            return back()->with([
                'otp_resend_feedback' => 'The code could not be resent. Please try again in a moment.',
                'otp_resend_feedback_type' => 'error',
            ]);
        }

        return redirect()
            ->route('login')
            ->with([
                'otp_resend_feedback' => 'The code has been resent successfully. Check your email for the new 6-digit code.',
                'otp_resend_feedback_type' => 'success',
            ]);
    }

    public function cancelLoginOtp(LoginOtpService $loginOtp)
    {
        $loginOtp->forgetPending();
        session()->forget('login_otp_verify_route');

        return redirect()
            ->route('login')
            ->with('success', 'You can sign in with a different account below.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully!');
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validateWithRecaptcha(Request $request, array $rules): array
    {
        if (config('recaptcha.enabled')) {
            if (! filled((string) config('recaptcha.secret_key'))) {
                Log::warning('RECAPTCHA_ENABLED is true but RECAPTCHA_SECRET_KEY is empty; reCAPTCHA verification is skipped.');
            } else {
                $rules['g-recaptcha-response'] = ['required', 'string', new RecaptchaV2];
            }
        }

        return $request->validate($rules);
    }
}
