<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\AppMailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationService
{
    private const TABLE = 'email_verification_challenges';
    private const CODE_LIFETIME_MINUTES = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function isEnabled(): bool
    {
        $request = app(Request::class);
        $cacheKey = self::class.'.email_verification_enabled';

        if ($request->attributes->has($cacheKey)) {
            return (bool) $request->attributes->get($cacheKey);
        }

        $enabled = filter_var(Setting::get('saas_email_verification', '0'), FILTER_VALIDATE_BOOLEAN);
        $request->attributes->set($cacheKey, $enabled);

        return $enabled;
    }

    public function hasPending(User $user): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return DB::table(self::TABLE)->where('user_id', $user->id)->exists();
    }

    public function sendCode(User $user): string
    {
        if (! $this->isEnabled()) {
            return 'disabled';
        }

        $email = trim((string) $user->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }

        $now = now();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $status = DB::transaction(function () use ($user, $email, $now, $code) {
            $challenge = DB::table(self::TABLE)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($challenge?->sent_at) {
                $availableAt = Carbon::parse($challenge->sent_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS);
                if ($availableAt->isAfter($now)) {
                    return 'cooldown';
                }
            }

            DB::table(self::TABLE)->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'email' => $email,
                    'code_hash' => Hash::make($code),
                    'expires_at' => $now->copy()->addMinutes(self::CODE_LIFETIME_MINUTES),
                    'sent_at' => $now,
                    'attempts' => 0,
                    'created_at' => $challenge?->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );

            return 'ready';
        });

        if ($status === 'cooldown') {
            return $status;
        }

        try {
            AppMailer::sendMailable(
                $email,
                new EmailVerificationCodeMail(
                    $code,
                    $now->copy()->addMinutes(self::CODE_LIFETIME_MINUTES)->format('g:i A'),
                    URL::temporarySignedRoute(
                        'email-verification.continue',
                        $now->copy()->addDay(),
                        ['user' => $user->id]
                    )
                )
            );
        } catch (Throwable $error) {
            DB::table(self::TABLE)
                ->where('user_id', $user->id)
                ->update(['sent_at' => null, 'updated_at' => now()]);

            Log::error('Registration email verification code delivery failed.', [
                'user_id' => $user->id,
                'error' => $error->getMessage(),
            ]);

            return 'delivery_failed';
        }

        return 'sent';
    }

    public function verify(User $user, string $code): string
    {
        return DB::transaction(function () use ($user, $code) {
            $challenge = DB::table(self::TABLE)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $challenge || ! hash_equals(strtolower((string) $challenge->email), strtolower((string) $user->email))) {
                return 'missing';
            }

            if (! $challenge->sent_at) {
                return 'not_sent';
            }

            if (Carbon::parse($challenge->expires_at)->isPast()) {
                return 'expired';
            }

            if ((int) $challenge->attempts >= self::MAX_ATTEMPTS) {
                return 'locked';
            }

            if (! Hash::check($code, (string) $challenge->code_hash)) {
                $attempts = (int) $challenge->attempts + 1;
                DB::table(self::TABLE)->where('user_id', $user->id)->update([
                    'attempts' => $attempts,
                    'updated_at' => now(),
                ]);

                return $attempts >= self::MAX_ATTEMPTS ? 'locked' : 'invalid';
            }

            DB::table('users')->where('id', $user->id)->update([
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table(self::TABLE)->where('user_id', $user->id)->delete();

            return 'verified';
        });
    }
}
