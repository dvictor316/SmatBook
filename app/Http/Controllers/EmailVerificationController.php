<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\SocialAccountLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verification)
    {
    }

    public function show(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('saas-login')
                ->withErrors(['login' => 'Sign in to verify the email address on your account.']);
        }

        if (! $request->session()->has('social_link_pending') && ! $this->verification->hasPending($user)) {
            return redirect()->route('home');
        }

        return $this->noStore(response()->view('Pages.Authentication.email-verification', [
            'email' => $this->maskEmail((string) $user->email),
        ]));
    }

    public function continueFromEmail(Request $request, int $user)
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('saas-login')
                ->withErrors(['login' => 'This verification link has expired or is invalid. Sign in to request a new code.']);
        }

        $pendingUser = User::withoutGlobalScopes()->find($user);
        if (! $pendingUser || ! $this->verification->hasPending($pendingUser)) {
            return redirect()->route('saas-login')
                ->with('success', 'This email is already verified, or the verification request has expired. Sign in to continue.');
        }

        $request->session()->put([
            'email_verification_pending_user_id' => (int) $pendingUser->id,
            'email_verification_remember' => false,
            'email_verification_redirect' => $this->registrationRedirect($pendingUser),
        ]);
        $request->session()->flash('info', 'Enter the six-digit code from this email to verify your account and continue setup.');

        return $this->show($request);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('saas-login')
                ->withErrors(['login' => 'Your email verification session expired. Sign in again to continue.']);
        }

        $socialLink = $request->session()->get('social_link_pending');
        if ($socialLink && ((int) ($socialLink['user_id'] ?? 0) !== (int) $user->id
            || ! in_array($socialLink['provider'] ?? null, ['google', 'facebook', 'linkedin'], true)
            || empty($socialLink['provider_id']))) {
            $request->session()->forget('social_link_pending');
            return redirect()->route('saas-login')->withErrors(['login' => 'The account connection session expired. Please try again.']);
        }

        try {
            $result = DB::transaction(function () use ($request, $user, $data, $socialLink) {
                if ($socialLink) {
                    $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                    $column = $socialLink['provider'].'_id';
                    if ((int) ($user->allow_login ?? 1) !== 1 || filled($user->{$column})
                        || User::where($column, $socialLink['provider_id'])->exists()) {
                        return 'link_unavailable';
                    }
                }

                $result = $socialLink
                    ? app(SocialAccountLinkService::class)->verify($request, $user, $data['code'])
                    : $this->verification->verify($user, $data['code']);
                if ($result === 'verified' && $socialLink) {
                    $user->update([
                        $column => $socialLink['provider_id'],
                        'provider_id' => $socialLink['provider_id'],
                        'provider_name' => $socialLink['provider'],
                    ]);
                    $this->verification->markVerified($user);
                }

                return $result;
            });
        } catch (QueryException $error) {
            report($error);
            return back()->withErrors(['code' => 'The account could not be connected. Please try again.']);
        }
        if ($result !== 'verified') {
            $message = match ($result) {
                'expired' => 'That code has expired. Request a new code and try again.',
                'locked' => $socialLink
                    ? 'Too many incorrect codes. Wait a few minutes before trying again.'
                    : 'Too many incorrect codes. Request a new code to continue.',
                'not_sent' => 'The code was not delivered. Request a new code and try again.',
                'link_unavailable' => 'This account cannot be connected. Sign in with an existing method or contact support.',
                default => 'The code is incorrect. Check your email and try again.',
            };

            return back()->withErrors(['code' => $message]);
        }

        $user = $user->fresh();
        $redirectTo = (string) $request->session()->pull('email_verification_redirect', '');
        $remember = (bool) $request->session()->pull('email_verification_remember', false);
        $request->session()->forget('email_verification_pending_user_id');
        $request->session()->forget(['email_verification_forced', 'social_link_pending', 'social_link_challenge']);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        ActivityLog::record('security', 'registration_email_verified', 'Registration email address verified.', [
            'user_id' => $user->id,
        ]);

        if ($socialLink) {
            return app(AuthController::class)->completeEmailVerifiedLogin($request, $user, $remember);
        }

        if (! $user->hasTwoFactorAuthenticationEnabled()) {
            return redirect()->to($redirectTo !== '' ? $redirectTo : $this->registrationRedirect($user));
        }

        return app(AuthController::class)->completeEmailVerifiedLogin($request, $user, $remember);
    }

    public function resend(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user || (! $request->session()->has('social_link_pending') && ! $this->verification->hasPending($user))) {
            return redirect()->route('saas-login')
                ->withErrors(['login' => 'Sign in to request an email verification code.']);
        }

        $result = $request->session()->has('social_link_pending')
            ? app(SocialAccountLinkService::class)->sendCode($request, $user)
            : $this->verification->sendCode($user, true);
        [$message, $flash] = match ($result) {
            'sent' => ['A new six-digit code has been sent to your email.', 'success'],
            'cooldown' => ['A code was sent recently. Wait one minute before requesting another.', 'warning'],
            'delivery_failed' => ['We could not send the code. Check your email settings or try again shortly.', 'error'],
            default => ['The account does not have a valid email address. Contact support.', 'error'],
        };

        return redirect()->route('email-verification.notice')->with($flash, $message);
    }

    private function pendingUser(Request $request): ?User
    {
        $current = $request->user();
        if ($current instanceof User && $this->verification->hasPending($current)) {
            return $current;
        }

        $id = (int) $request->session()->get('email_verification_pending_user_id', 0);

        return $id > 0 ? User::withoutGlobalScopes()->find($id) : null;
    }

    private function registrationRedirect(User $user): string
    {
        $role = strtolower((string) $user->role);
        if ($role === 'agent') {
            return route('agent.dashboard');
        }
        if (in_array($role, ['state_manager', 'deployment_manager', 'manager'], true)) {
            return route('manager.verification.form');
        }

        $subscription = Subscription::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($subscription?->isTrial() && ! $subscription->company_id) {
            return route('saas.setup', ['id' => $subscription->id]);
        }

        return route('home');
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, 1);

        return $visible.str_repeat('*', max(3, mb_strlen($name) - 1)).'@'.$domain;
    }

    private function noStore($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
