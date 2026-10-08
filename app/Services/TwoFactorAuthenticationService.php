<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    public function __construct(private readonly Google2FA $google2fa)
    {
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeDataUri(User $user): string
    {
        $uri = $this->google2fa->getQRCodeUrl(
            (string) config('app.name', 'SmartProbook'),
            (string) $user->email,
            (string) $user->two_factor_secret
        );
        $renderer = new ImageRenderer(new RendererStyle(260, 2), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($uri);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function verifyTotp(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6 || ! $user->two_factor_secret) {
            return false;
        }

        $timestep = $this->google2fa->verifyKeyNewer(
            (string) $user->two_factor_secret,
            $code,
            (int) ($user->two_factor_last_used_timestep ?? 0),
            1
        );
        if ($timestep === false) {
            return false;
        }

        $updated = User::withoutGlobalScopes()
            ->whereKey($user->id)
            ->where(function ($query) use ($timestep) {
                $query->whereNull('two_factor_last_used_timestep')
                    ->orWhere('two_factor_last_used_timestep', '<', (int) $timestep);
            })
            ->update(['two_factor_last_used_timestep' => (int) $timestep]);

        if ($updated !== 1) {
            return false;
        }

        $user->forceFill(['two_factor_last_used_timestep' => (int) $timestep]);
        return true;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $normalized = $this->normalizeRecoveryCode($code);
        if ($normalized === '') {
            return false;
        }

        foreach ((array) ($user->two_factor_recovery_codes ?? []) as $index => $hash) {
            if (! Hash::check($normalized, (string) $hash)) {
                continue;
            }

            $codes = (array) $user->two_factor_recovery_codes;
            unset($codes[$index]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
            return true;
        }

        return false;
    }

    public function verify(User $user, string $code): bool
    {
        return preg_match('/^\d{6}$/', preg_replace('/\s+/', '', $code) ?? '') === 1
            ? $this->verifyTotp($user, $code)
            : $this->verifyRecoveryCode($user, $code);
    }

    public function generateRecoveryCodes(User $user, int $count = 8): array
    {
        $plain = collect(range(1, $count))->map(function () {
            $raw = strtolower(Str::random(10));
            return substr($raw, 0, 5).'-'.substr($raw, 5, 5);
        })->all();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                fn (string $code) => Hash::make($this->normalizeRecoveryCode($code)),
                $plain
            ),
        ])->save();

        return $plain;
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return count((array) ($user->two_factor_recovery_codes ?? []));
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', trim($code)) ?? '');
    }
}
