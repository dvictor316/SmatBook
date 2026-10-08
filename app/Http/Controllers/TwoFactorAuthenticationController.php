<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $setupAuthorized = (int) $request->session()->get('two_factor_setup_authorized_until', 0) >= now()->timestamp;

        return $this->noStore(
            response()->view('Settings.two-factor', $this->viewData($user, $setupAuthorized))
        );
    }

    public function beginSetup(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string']);
        $user = $request->user();
        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        abort_if($user->hasTwoFactorAuthenticationEnabled(), 422, 'Two-factor authentication is already enabled.');

        $user->forceFill([
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_timestep' => null,
        ])->save();
        $request->session()->put('two_factor_setup_authorized_until', now()->addMinutes(10)->timestamp);

        ActivityLog::record('security', 'two_factor_setup_started', 'Authenticator-app setup started.');
        return redirect()->route('two-factor')->with('success', 'Scan the QR code, then enter the current six-digit code.');
    }

    public function confirmSetup(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        abort_unless((int) $request->session()->get('two_factor_setup_authorized_until', 0) >= now()->timestamp, 403, 'Two-factor setup authorization expired. Enter your password again.');
        $user = $request->user();
        abort_unless($user->two_factor_secret && ! $user->two_factor_confirmed_at, 422, 'Start authenticator setup first.');

        if (! $this->twoFactor->verifyTotp($user, $data['code'])) {
            return back()->withErrors(['code' => 'The authentication code is invalid, expired, or was already used.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes($user->fresh());
        $request->session()->forget('two_factor_setup_authorized_until');
        ActivityLog::record('security', 'two_factor_enabled', 'Two-factor authentication enabled.');

        return $this->noStore(
            response()->view('Settings.two-factor', $this->viewData($user->fresh(), false, $recoveryCodes))
        );
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string', 'code' => 'required|string|max:32']);
        $user = $request->user();
        abort_unless($user->hasTwoFactorAuthenticationEnabled(), 422, 'Two-factor authentication is not enabled.');
        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        if (! $this->twoFactor->verify($user, $data['code'])) {
            return back()->withErrors(['code' => 'The authentication or recovery code is invalid.']);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes($user->fresh());
        ActivityLog::record('security', 'two_factor_recovery_regenerated', 'Two-factor recovery codes regenerated.');

        return $this->noStore(
            response()->view('Settings.two-factor', $this->viewData($user->fresh(), false, $recoveryCodes))
        );
    }

    public function disable(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string', 'code' => 'required|string|max:32']);
        $user = $request->user();
        abort_unless($user->hasTwoFactorAuthenticationEnabled(), 422, 'Two-factor authentication is not enabled.');
        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        if (! $this->twoFactor->verify($user, $data['code'])) {
            return back()->withErrors(['code' => 'The authentication or recovery code is invalid.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_timestep' => null,
        ])->save();
        $request->session()->forget('two_factor_setup_authorized_until');
        ActivityLog::record('security', 'two_factor_disabled', 'Two-factor authentication disabled.');

        return redirect()->route('two-factor')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function challenge(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('saas-login')->withErrors(['login' => 'Your two-factor login session expired. Please sign in again.']);
        }

        return $this->noStore(response()->view('Pages.Authentication.two-factor-challenge'));
    }

    public function verifyChallenge(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:32']);
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('saas-login')->withErrors(['login' => 'Your two-factor login session expired. Please sign in again.']);
        }

        $key = 'two-factor:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $verified = DB::transaction(function () use ($user, $data) {
            $locked = User::withoutGlobalScopes()->lockForUpdate()->find($user->id);
            return $locked && $locked->hasTwoFactorAuthenticationEnabled()
                ? $this->twoFactor->verify($locked, $data['code'])
                : false;
        });
        if (! $verified) {
            RateLimiter::hit($key, 60);
            ActivityLog::record('security', 'two_factor_challenge_failed', 'Two-factor login challenge failed.', ['user_id' => $user->id, 'company_id' => $user->company_id]);
            return back()->withErrors(['code' => 'The authentication or recovery code is invalid, expired, or already used.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor_login_remember', false);
        $request->session()->forget(['two_factor_login_user_id', 'two_factor_login_started_at']);
        Auth::login($user->fresh(), $remember);
        $request->session()->regenerate();
        ActivityLog::record('security', 'two_factor_challenge_passed', 'Two-factor login challenge completed.');

        return app(AuthController::class)->completeAuthenticatedLogin($request, $user->fresh());
    }

    private function pendingUser(Request $request): ?User
    {
        $startedAt = (int) $request->session()->get('two_factor_login_started_at', 0);
        if ($startedAt < now()->subMinutes(10)->timestamp) {
            $request->session()->forget(['two_factor_login_user_id', 'two_factor_login_remember', 'two_factor_login_started_at']);
            return null;
        }

        $id = (int) $request->session()->get('two_factor_login_user_id', 0);
        return $id > 0 ? User::withoutGlobalScopes()->find($id) : null;
    }

    private function viewData(User $user, bool $setupAuthorized, array $recoveryCodes = []): array
    {
        $showSetup = $setupAuthorized && $user->two_factor_secret && ! $user->two_factor_confirmed_at;
        return [
            'user' => $user,
            'enabled' => $user->hasTwoFactorAuthenticationEnabled(),
            'showSetup' => $showSetup,
            'qrCodeDataUri' => $showSetup ? $this->twoFactor->qrCodeDataUri($user) : null,
            'manualSecret' => $showSetup ? $user->two_factor_secret : null,
            'remainingRecoveryCodes' => $this->twoFactor->remainingRecoveryCodes($user),
            'recoveryCodes' => $recoveryCodes,
        ];
    }

    private function noStore($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
