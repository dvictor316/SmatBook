<?php

namespace App\Services;

use App\Mail\RegistrationEmailApprovalMail;
use App\Support\AppMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrationEmailApproval
{
    private const TABLE = 'registration_email_approvals';

    public function send(Request $request, string $email, string $purpose): void
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)
            || ($scheme !== 'https' && ! app()->environment(['local', 'testing']))) {
            Log::error('Registration approval requires a valid HTTPS application URL.');
            throw ValidationException::withMessages([
                'email' => 'Email verification is temporarily unavailable. Please contact support.',
            ]);
        }
        $email = strtolower(trim($email));
        $actorId = $purpose === 'assisted' ? $request->user()?->id : null;
        $secret = Str::random(64);
        $token = Str::random(64);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();

        DB::transaction(function () use ($email, $purpose, $actorId, $secret, $token, $code, $now) {
            $existing = DB::table(self::TABLE)->where('email', $email)->lockForUpdate()->first();
            if ($existing && Carbon::parse($existing->sent_at)->addSeconds(60)->isFuture()) {
                throw ValidationException::withMessages([
                    'email' => 'A code was sent recently. Please wait one minute before requesting another.',
                ]);
            }

            DB::table(self::TABLE)->updateOrInsert(['email' => $email], [
                'purpose' => $purpose,
                'actor_id' => $actorId,
                'session_secret_hash' => hash('sha256', $secret),
                'link_token_hash' => hash('sha256', $token),
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'sent_at' => $now,
                'expires_at' => $now->copy()->addMinutes(5),
                'approved_at' => null,
                'created_at' => $existing?->created_at ?? $now,
                'updated_at' => $now,
            ]);
        });

        try {
            AppMailer::sendMailable($email, new RegistrationEmailApprovalMail(
                $code,
                $baseUrl.route('registration-verification.show', ['token' => $token], false)
            ));
        } catch (Throwable $e) {
            DB::table(self::TABLE)->where('link_token_hash', hash('sha256', $token))->delete();
            Log::error('Registration approval email delivery failed.', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'email' => 'The verification email could not be sent. Please try again or contact support.',
            ]);
        }

        $request->session()->put('registration_email_approval', [
            'email' => $email,
            'purpose' => $purpose,
            'actor_id' => $actorId,
            'secret' => $secret,
        ]);
    }

    public function verify(string $token, string $code): string
    {
        return DB::transaction(function () use ($token, $code) {
            $row = DB::table(self::TABLE)->where('link_token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $row || Carbon::parse($row->expires_at)->isPast()) {
                return 'expired';
            }
            if ($row->approved_at) {
                return 'approved';
            }
            if ($row->attempts >= 5) {
                return 'locked';
            }
            if (! Hash::check($code, $row->code_hash)) {
                $attempts = $row->attempts + 1;
                DB::table(self::TABLE)->where('id', $row->id)->update([
                    'attempts' => $attempts,
                    'updated_at' => now(),
                ]);
                return $attempts >= 5 ? 'locked' : 'invalid';
            }
            DB::table(self::TABLE)->where('id', $row->id)->update([
                'approved_at' => now(),
                'updated_at' => now(),
            ]);
            return 'approved';
        });
    }

    public function isApproved(Request $request, string $email, string $purpose): bool
    {
        return $this->query($request, $email, $purpose)->whereNotNull('approved_at')->where('expires_at', '>', now())->exists();
    }

    public function consume(Request $request, string $email, string $purpose): void
    {
        $row = $this->query($request, $email, $purpose)->lockForUpdate()->first();
        if (! $row || ! $row->approved_at || Carbon::parse($row->expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'email' => 'Verify the business owner’s email before completing registration.',
            ]);
        }
        DB::table(self::TABLE)->where('id', $row->id)->delete();
    }

    private function query(Request $request, string $email, string $purpose)
    {
        $proof = $request->session()->get('registration_email_approval', []);
        $actorId = $purpose === 'assisted' ? $request->user()?->id : null;
        $secret = (string) ($proof['secret'] ?? '');
        return DB::table(self::TABLE)
            ->where('email', strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->where('actor_id', $actorId)
            ->where('session_secret_hash', hash('sha256', $secret));
    }
}
