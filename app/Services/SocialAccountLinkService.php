<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Support\AppMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SocialAccountLinkService
{
    public function sendCode(Request $request, User $user): string
    {
        $email = trim((string) $user->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }

        $sendKey = 'social-link-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($sendKey, 1)) {
            return 'cooldown';
        }

        RateLimiter::hit($sendKey, 60);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);
        $request->session()->forget('social_link_challenge');
        try {
            AppMailer::sendMailable($email, new EmailVerificationCodeMail(
                $code,
                $expiresAt->format('g:i A'),
                route('email-verification.notice'),
                true
            ));
        } catch (Throwable $error) {
            RateLimiter::clear($sendKey);
            Log::error('Social account connection code delivery failed.', [
                'user_id' => $user->id,
                'error' => $error->getMessage(),
            ]);
            return 'delivery_failed';
        }

        $request->session()->put('social_link_challenge', [
            'user_id' => (int) $user->id,
            'email' => strtolower($email),
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt->timestamp,
        ]);
        return 'sent';
    }

    public function verify(Request $request, User $user, string $code): string
    {
        $challenge = $request->session()->get('social_link_challenge');
        $attemptKey = 'social-link-attempt:'.$user->id;
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return 'locked';
        }
        if (! is_array($challenge) || (int) ($challenge['user_id'] ?? 0) !== (int) $user->id
            || ($challenge['email'] ?? '') !== strtolower((string) $user->email)) {
            return 'not_sent';
        }
        if ((int) ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            return 'expired';
        }
        if (! Hash::check($code, (string) ($challenge['code_hash'] ?? ''))) {
            RateLimiter::hit($attemptKey, 300);
            return RateLimiter::tooManyAttempts($attemptKey, 5) ? 'locked' : 'invalid';
        }

        RateLimiter::clear($attemptKey);
        return 'verified';
    }
}
