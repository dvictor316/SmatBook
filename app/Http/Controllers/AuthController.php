<?php

namespace App\Http\Controllers;

use App\Models\{User, Company, Subscription, Plan, DeploymentManager};
use App\Support\SystemEventMailer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Password, Session, Str};
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTRATION METHODS
    |--------------------------------------------------------------------------
    */

    public function showRegister(Request $request)
    {
        if (Auth::check()) {
            return $this->handlePostLoginRedirect();
        }

        $isManager = $request->query('type') === 'manager';
        $planParam = strtolower($request->query('plan', 'pro'));
        $cycleParam = strtolower($request->query('cycle', 'monthly'));

        $planData = Plan::whereRaw('LOWER(name) = ?', [$planParam])
            ->whereRaw('LOWER(billing_cycle) = ?', [$cycleParam])
            ->where('status', 'active')
            ->where('is_active', 1)
            ->first();

        $fallbackPrices = [
            'monthly' => ['basic' => 3000, 'pro' => 7000, 'enterprise' => 15000],
            'yearly' => ['basic' => 30000, 'pro' => 70000, 'enterprise' => 150000]
        ];

        if ($isManager) {
            $finalPrice = 0.00;
            $finalName = 'Partner';
            $finalCycle = 'N/A';
        } else {
            $finalPrice = $planData ? $planData->price : ($fallbackPrices[$cycleParam][$planParam] ?? 7000);
            $finalName = $planData ? $planData->name : ucfirst($planParam);
            $finalCycle = $planData ? $planData->billing_cycle : ucfirst($cycleParam);
        }

        session([
            'selected_plan_id' => $planData->id ?? null,
            'selected_plan' => $finalName,
            'selected_cycle' => $finalCycle,
            'selected_amount' => $finalPrice,
            'reg_role' => $isManager ? 'deployment_manager' : 'admin'
        ]);

        return view('Pages.Authentication.saas-register', [
            'company' => $this->getTenantDetails(),
            'selectedPlan' => $finalName,
            'billing_cycle' => $finalCycle,
            'plan_id' => $planData->id ?? null,
            'amount' => $finalPrice,
            'isManager' => $isManager
        ]);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        if ($request->role !== 'deployment_manager') {
            $rules['plan'] = 'required|string';
            $rules['billing_cycle'] = 'required|string';
        }

        $validated = $request->validate($rules);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $role = $request->role ?? session('reg_role', 'admin');

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => $role,
                    'is_verified' => ($role === 'deployment_manager') ? 0 : 1,
                ]);

                if ($request->hasFile('profile_photo')) {
                    $user->profile_photo = $request->file('profile_photo')->store('users/profiles', 'public');
                    $user->save();
                }

                Auth::login($user);

                if ($role === 'deployment_manager') {
                    DeploymentManager::create([
                        'user_id' => $user->id,
                        'status' => 'pending_info',
                        'commission_rate' => 35.00,
                    ]);
                    DB::afterCommit(function () use ($user) {
                        SystemEventMailer::notifyRegistration($user, 'deployment_manager');
                    });
                    $this->clearRegistrationSession();
                    return redirect()->route('manager.verification.form');
                }

                $planName = session('selected_plan', $request->plan ?? 'Pro');
                $planAmount = (float) ($request->amount ?? session('selected_amount', 7000));

                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => session('selected_plan_id'),
                    'plan' => $planName,
                    'plan_name' => $planName,
                    'billing_cycle' => ucfirst($request->billing_cycle ?? session('selected_cycle', 'Monthly')),
                    'amount' => $planAmount,
                    'status' => 'Pending',
                    'payment_status' => 'unpaid',
                ]);

                DB::afterCommit(function () use ($user, $planName, $planAmount, $request) {
                    SystemEventMailer::notifyRegistration($user, 'user', [
                        'plan' => $planName,
                        'amount' => (string) $planAmount,
                        'billing_cycle' => ucfirst($request->billing_cycle ?? session('selected_cycle', 'Monthly')),
                    ]);
                });

                $this->clearRegistrationSession();
                return redirect()->route('saas.setup', ['id' => $subscription->id]);
            });
        } catch (\Exception $e) {
            Log::error('Registration Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Registration failed.'])->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN METHODS - CRITICAL FIX
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->handlePostLoginRedirect();
        }
        return view('Pages.Authentication.saas-login', ['company' => $this->getTenantDetails()]);
    }

    /**
     * LOGIN METHOD - ALWAYS REDIRECTS TO /home
     * HomeController@index handles role-based routing
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $request->session()->regenerate();
        
        $user = Auth::user();

        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        // CRITICAL FIX: ALWAYS redirect to /home
        // HomeController@index will handle role-based redirects
        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $mainDomain = ltrim(config('session.domain', 'smatbook.com'), '.');
        $protocol = $request->secure() ? 'https://' : 'http://';

        return redirect()->away($protocol . $mainDomain . '/login')
            ->withCookie(cookie()->forget('laravel_session', '/', '.' . $mainDomain))
            ->withCookie(cookie()->forget('XSRF-TOKEN', '/', '.' . $mainDomain))
            ->withCookie(cookie()->forget('laravel_session', '/', $mainDomain));
    }

    /**
     * TEMP: Superadmin-only impersonation for UI/dashboard editing.
     * Controlled by TEMP_OPEN_ACCESS.
     */
    public function impersonateUser(Request $request, int $id): RedirectResponse
    {
        $actor = Auth::user();

        if (!$actor || strtolower((string) $actor->email) !== 'donvictorlive@gmail.com') {
            abort(403, 'Unauthorized impersonation request.');
        }

        if (!(bool) env('TEMP_OPEN_ACCESS', false)) {
            return redirect()->route('super_admin.dashboard')
                ->with('error', 'Temporary impersonation mode is disabled.');
        }

        $target = User::findOrFail($id);

        if ((int) $target->id === (int) $actor->id) {
            return redirect()->route('super_admin.dashboard');
        }

        if (!$request->session()->has('impersonator_user_id')) {
            $request->session()->put('impersonator_user_id', (int) $actor->id);
        }

        Auth::login($target, true);
        $request->session()->regenerate();
        $request->session()->put('is_impersonating', true);

        Log::warning('User impersonation started', [
            'impersonator_id' => $actor->id,
            'impersonator_email' => $actor->email,
            'target_id' => $target->id,
            'target_email' => $target->email,
        ]);

        return redirect()->route('home')
            ->with('success', "Now viewing as {$target->email}");
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->pull('impersonator_user_id', 0);
        $request->session()->forget('is_impersonating');

        if ($impersonatorId <= 0) {
            return redirect()->route('home');
        }

        $admin = User::find($impersonatorId);
        if (!$admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('saas-login')->with('error', 'Original admin session not found.');
        }

        Auth::login($admin, true);
        $request->session()->regenerate();

        Log::warning('User impersonation ended', [
            'restored_admin_id' => $admin->id,
            'restored_admin_email' => $admin->email,
        ]);

        return redirect()->route('super_admin.dashboard')
            ->with('success', 'Returned to superadmin account.');
    }

    /**
     * LEGACY REDIRECT HANDLER (Kept for compatibility)
     * This is ONLY used by showRegister/showLogin checks
     */
    private function handlePostLoginRedirect()
    {
        $user = Auth::user();

        // Super Admin
        if ($this->isSuperAdmin($user)) {
            return redirect()->route('super_admin.dashboard');
        }

        // Deployment Manager
        if ($this->isDeploymentManager($user)) {
            return $this->handleDeploymentManagerRedirect($user);
        }

        // Regular users - redirect to /home
        // HomeController@index will handle the rest
        return redirect()->route('home');
    }

    /*
    |--------------------------------------------------------------------------
    | SOCIAL LOGIN
    |--------------------------------------------------------------------------
    */

    public function redirectToProvider($provider = 'google')
    {
        $provider = strtolower((string) $provider);

        if (!in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('saas-login')->with('error', 'Unsupported login provider.');
        }

        if (!$this->isSocialProviderConfigured($provider)) {
            return redirect()->route('saas-login')->with('error', ucfirst($provider) . ' login is not configured yet.');
        }

        try {
            $redirectUrl = $this->socialCallbackUrl($provider);
            Log::info('Social redirect init', [
                'provider' => $provider,
                'redirect_url' => $redirectUrl,
            ]);

            // Stateless avoids "Invalid state" errors from cookie/session mismatch across domains.
            return Socialite::driver($provider)
                ->redirectUrl($redirectUrl)
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Social redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('saas-login')->with('error', 'Social login unavailable.');
        }
    }

    public function handleProviderCallback($provider = 'google')
    {
        $provider = strtolower((string) $provider);

        if (!in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('saas-login')->with('error', 'Unsupported login provider.');
        }

        if (request()->has('error')) {
            $reason = (string) (request('error_description') ?: request('error'));
            return redirect()->route('saas-login')
                ->with('error', ucfirst($provider) . ' login was cancelled or failed. ' . $reason);
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->redirectUrl($this->socialCallbackUrl($provider))
                ->stateless()
                ->user();
        } catch (InvalidStateException $e) {
            Log::error('Social callback invalid state', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('saas-login')->with('error', ucfirst($provider) . ' login session expired. Please retry.');
        } catch (\Exception $e) {
            Log::error('Social callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('saas-login')->with('error', ucfirst($provider) . ' login failed. Please try again.');
        }

        $providerId = (string) $socialUser->getId();
        $email = $socialUser->getEmail() ?: $provider . '_' . $providerId . '@social.local';
        $name = $socialUser->getName() ?: $socialUser->getNickname() ?: 'User';
        $providerColumn = $provider . '_id';

        $user = User::where($providerColumn, $providerId)
            ->orWhere('email', $email)
            ->first();
        $createdNow = false;

        if ($user) {
            $payload = [
                'provider_id' => $providerId,
                'provider_name' => $provider,
                'is_verified' => 1,
            ];

            if (empty($user->{$providerColumn})) {
                $payload[$providerColumn] = $providerId;
            }
            if (!$user->name) {
                $payload['name'] = $name;
            }
            if (!$user->email_verified_at) {
                $payload['email_verified_at'] = now();
            }

            $user->update($payload);
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'admin',
                'is_verified' => 1,
                'email_verified_at' => now(),
                'provider_id' => $providerId,
                'provider_name' => $provider,
                $providerColumn => $providerId,
            ]);
            $createdNow = true;
        }

        Auth::login($user, true);

        if ($createdNow) {
            DB::afterCommit(function () use ($user, $provider) {
                \App\Support\SystemEventMailer::notifyRegistration($user, 'user', [
                    'auth_provider' => ucfirst($provider),
                ]);
            });
        }

        return redirect()->route('home');
    }

    /**
     * Build callback URL for OAuth providers.
     * Prefer current request host (supports real runtime domain), fallback to APP_URL.
     */
    private function socialCallbackUrl(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (request()) {
            return url('/auth/' . $provider . '/callback');
        }

        $configured = (string) config("services.{$provider}.redirect", '');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/') . '/auth/' . $provider . '/callback';
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD RESET
    |--------------------------------------------------------------------------
    */

    public function showForgotPasswordForm()
    {
        return view('Pages.Authentication.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Link sent!')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('Pages.Authentication.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60))->save();
                
                event(new PasswordReset($user));
            }
        );
        
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('saas-login')->with('success', 'Reset successful!')
            : back()->withErrors(['email' => [__($status)]]);
    }

    /*
    |--------------------------------------------------------------------------
    | TENANT LOGIN
    |--------------------------------------------------------------------------
    */

    public function showTenantLogin()
    {
        return view('auth.tenant-login');
    }

    public function tenantLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            $subdomain = $request->route('subdomain');
            $company = Company::where('domain_prefix', $subdomain)->first();
            
            if ($company) {
                session(['current_tenant_id' => $company->id]);
            }

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    private function isSuperAdmin($user): bool
    {
        return in_array(strtolower($user->role), ['superadmin', 'super_admin']) 
            || $user->email === 'donvictorlive@gmail.com';
    }

    private function isDeploymentManager($user): bool
    {
        return in_array(strtolower($user->role), ['deployment_manager', 'manager']);
    }

    private function handleDeploymentManagerRedirect($user)
    {
        $manager = DeploymentManager::where('user_id', $user->id)->first();
        
        if (!$manager || $manager->status === 'pending_info') {
            return redirect()->route('manager.verification.form');
        }
        
        return redirect()->route('deployment.dashboard');
    }

    private function getTenantDetails()
    {
        $tenantId = session('current_tenant_id');
        return $tenantId ? Company::find($tenantId) : null;
    }

    private function clearRegistrationSession()
    {
        session()->forget([
            'selected_plan_id',
            'selected_plan',
            'selected_cycle',
            'selected_amount',
            'reg_role'
        ]);
    }

    private function isSocialProviderConfigured(string $provider): bool
    {
        $cfg = (array) config('services.' . $provider, []);
        return !empty($cfg['client_id']) && !empty($cfg['client_secret']) && !empty($cfg['redirect']);
    }

}
