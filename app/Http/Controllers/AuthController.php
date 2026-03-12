<?php

namespace App\Http\Controllers;

use App\Models\{User, Company, Subscription, Plan, DeploymentManager};
use App\Support\SystemEventMailer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Password, Schema, Session, Str};
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
        $catalog = $this->registrationPlanCatalog();
        $selectedCatalog = $catalog[$planParam] ?? $catalog['pro'];
        $planData = Plan::findByCatalogName($selectedCatalog['label'], $cycleParam);

        if ($isManager) {
            $finalPrice = 0.00;
            $finalName = 'Partner';
            $finalCycle = 'N/A';
        } else {
            $finalPrice = $planData ? $planData->price : $selectedCatalog['prices'][$cycleParam];
            $finalName = $selectedCatalog['label'];
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
        $requestedRole = $request->role ?? session('reg_role', 'admin');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|required_without:phone|string|email|max:255|unique:users,email',
            'phone' => ['nullable', 'required_without:email', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers(),
            ],
            'profile_photo' => 'nullable|file|mimetypes:image/*|max:2048',
        ];

        if ($requestedRole === 'deployment_manager') {
            // Deployment managers must use a real email so notifications and approval updates reach them.
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
            $rules['phone'] = ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'];
        } else {
            $rules['plan'] = 'required|string';
            $rules['billing_cycle'] = 'required|string';
        }

        $validated = $request->validate($rules, [
            'password.*' => 'Password must be at least 8 characters and include letters and numbers.',
            'phone.required_without' => 'Phone is required when email is not provided.',
            'email.required_without' => 'Email is required when phone is not provided.',
            'phone.regex' => 'Phone format is invalid. Use digits with optional leading +.',
        ]);

        $normalizedPhone = $this->normalizePhoneForAuth($validated['phone'] ?? null);
        if ($normalizedPhone && Schema::hasColumn('users', 'phone')) {
            $phoneExists = User::query()->where('phone', $normalizedPhone)->exists();
            if ($phoneExists) {
                return back()->withErrors(['phone' => 'This phone is already registered.'])->withInput();
            }
        }

        $resolvedEmail = $validated['email'] ?? null;
        if (!$resolvedEmail && $normalizedPhone) {
            $seed = preg_replace('/\D+/', '', $normalizedPhone) ?: Str::lower(Str::random(10));
            $candidate = 'phone' . $seed . '@phone.smartprobook.local';
            while (User::withTrashed()->where('email', $candidate)->exists()) {
                $candidate = 'phone' . $seed . Str::lower(Str::random(3)) . '@phone.smartprobook.local';
            }
            $resolvedEmail = $candidate;
        }

        // Early connectivity check to avoid vague catch-all error messages.
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            Log::error('Registration DB connectivity failure', [
                'email' => $resolvedEmail,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Registration service is temporarily unavailable. Please try again shortly.'])
                ->withInput();
        }

        try {
            return DB::transaction(function () use ($validated, $request, $resolvedEmail, $normalizedPhone) {
                $role = $request->role ?? session('reg_role', 'admin');

                $user = User::create($this->filterPayloadForTable('users', [
                    'name' => $validated['name'],
                    'email' => $resolvedEmail,
                    'phone' => $normalizedPhone,
                    'password' => Hash::make($validated['password']),
                    'role' => $role,
                    'is_verified' => ($role === 'deployment_manager') ? 0 : 1,
                ]));

                if ($request->hasFile('profile_photo') && Schema::hasColumn('users', 'profile_photo')) {
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

                $requestedPlan = strtolower((string) ($request->plan ?? session('selected_plan', 'pro')));
                $requestedCycle = strtolower((string) ($request->billing_cycle ?? session('selected_cycle', 'monthly')));
                $catalog = $this->registrationPlanCatalog();
                $catalogEntry = $catalog[$requestedPlan] ?? null;
                $planId = $request->plan_id ?? session('selected_plan_id');
                $plan = $planId ? Plan::find((int) $planId) : null;

                if (!$plan && $catalogEntry) {
                    $plan = Plan::findByCatalogName($catalogEntry['label'], $requestedCycle);
                }

                if (!$plan && !empty(session('selected_plan'))) {
                    $plan = Plan::findByCatalogName((string) session('selected_plan'), $requestedCycle);
                }

                $planName = $catalogEntry['label'] ?? $plan?->name ?? ucfirst($requestedPlan ?: 'pro');
                $planAmount = (float) ($request->amount ?? $plan?->price ?? session('selected_amount', 19500));
                $planId = $plan?->id ?? $planId;
                $billingCycle = ucfirst($request->billing_cycle ?? session('selected_cycle', 'Monthly'));

                $subscription = Subscription::create($this->filterPayloadForTable('subscriptions', [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'plan' => $planName,
                    'plan_name' => $planName,
                    'billing_cycle' => $billingCycle,
                    'amount' => $planAmount,
                    'status' => 'Pending',
                    'payment_status' => 'unpaid',
                ]));

                DB::afterCommit(function () use ($user, $planName, $planAmount, $billingCycle) {
                    SystemEventMailer::notifyRegistration($user, 'user', [
                        'plan' => $planName,
                        'amount' => (string) $planAmount,
                        'billing_cycle' => $billingCycle,
                    ]);
                });

                $this->clearRegistrationSession();
                return redirect()->route('saas.setup', ['id' => $subscription->id]);
            });
        } catch (\Throwable $e) {
            Log::error('Registration failed', [
                'email' => $resolvedEmail,
                'role' => $request->role ?? null,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            $message = str_contains(strtolower($e->getMessage()), 'duplicate')
                || str_contains(strtolower($e->getMessage()), 'unique')
                ? 'This email is already registered. Please log in instead.'
                : 'Registration failed. Please try again.';

            return back()->withErrors(['error' => $message])->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN METHODS - CRITICAL FIX
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        if (request()->boolean('portal') && Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

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
            'login' => 'nullable|required_without:email|string|max:255',
            'email' => 'nullable|string|max:255',
            'password' => 'required',
        ]);

        $loginInput = trim((string) ($credentials['login'] ?? $credentials['email'] ?? ''));
        $password = (string) $credentials['password'];
        $remember = $request->filled('remember');

        if ($loginInput === '') {
            return back()->withErrors(['login' => 'Email or phone is required.'])->withInput();
        }

        $attemptOk = false;
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $attemptOk = Auth::attempt(['email' => $loginInput, 'password' => $password], $remember);
        } else {
            $normalizedPhone = $this->normalizePhoneForAuth($loginInput);
            if ($normalizedPhone && Schema::hasColumn('users', 'phone')) {
                $user = User::query()->where('phone', $normalizedPhone)->first();
                if ($user) {
                    $attemptOk = Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
                }
            }
        }

        if (!$attemptOk) {
            return back()->withErrors(['login' => 'Invalid credentials.'])->withInput();
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
        $request->session()->forget([
            'url.intended',
            'current_tenant_id',
            'current_tenant_name',
            'selected_plan_id',
            'selected_plan',
            'selected_cycle',
            'selected_amount',
            'billing_cycle',
            'plan',
            'reg_role',
            'checkout_from_deployment',
            'deployment_manager_id',
            'deployment_customer_id',
            'deployment_company_id',
            'deployment_subscription_id',
            'deployment_manager_email',
            'deployment_commission_rate',
            'deployment_plan_name',
            'impersonator_user_id',
            'is_impersonating',
        ]);
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->to('/login')->with('success', 'Logged out successfully. You can now sign in with another account.');
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

    public function redirectToProvider(Request $request, $provider = 'google')
    {
        $provider = strtolower((string) $provider);

        if (!in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('saas-login')->with('error', 'Unsupported login provider.');
        }

        if (!$this->isSocialProviderConfigured($provider)) {
            return redirect()->route('saas-login')->with('error', ucfirst($provider) . ' login is not configured yet.');
        }

        try {
            $this->rememberSocialContext($request, $provider);
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

    public function handleProviderCallback(Request $request, $provider = 'google')
    {
        $provider = strtolower((string) $provider);
        $socialContext = $this->pullSocialContext($request, $provider);

        if (!in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('saas-login')->with('error', 'Unsupported login provider.');
        }

        if ($request->has('error')) {
            $reason = (string) ($request->input('error_description') ?: $request->input('error'));
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

        $subscription = $this->ensureSocialRegistrationSubscription($user, $socialContext);
        if ($subscription) {
            return redirect()->route('saas.setup', ['id' => $subscription->id]);
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

    private function rememberSocialContext(Request $request, string $provider): void
    {
        $intent = strtolower((string) $request->query('intent', 'login'));
        $cycle = strtolower((string) $request->query('cycle', session('selected_cycle', session('billing_cycle', 'monthly'))));
        $planInput = (string) $request->query('plan', session('selected_plan', ''));
        $catalog = $this->registrationPlanCatalog();
        $planKey = $this->resolveRegistrationPlanKey($planInput, $catalog);
        $entry = $planKey ? ($catalog[$planKey] ?? null) : null;
        $planId = $request->query('plan_id', session('selected_plan_id'));
        $amount = $request->query('amount', session('selected_amount'));

        if ($entry && in_array($cycle, ['monthly', 'yearly'], true)) {
            $plan = $planId ? Plan::find((int) $planId) : null;
            if (!$plan) {
                $plan = Plan::findByCatalogName($entry['label'], $cycle);
                $planId = $plan?->id;
            }

            $amount = $plan?->price ?? ($entry['prices'][$cycle] ?? $amount);

            session([
                'selected_plan_id' => $planId,
                'selected_plan' => $entry['label'],
                'selected_cycle' => ucfirst($cycle),
                'selected_amount' => $amount,
                'billing_cycle' => ucfirst($cycle),
                'reg_role' => 'admin',
            ]);
        }

        session([
            'social_auth_context' => [
                'provider' => $provider,
                'intent' => in_array($intent, ['login', 'register'], true) ? $intent : 'login',
                'plan' => $planKey,
                'cycle' => $cycle,
                'plan_id' => $planId ? (int) $planId : null,
                'amount' => $amount !== null ? (float) $amount : null,
            ],
        ]);
    }

    private function pullSocialContext(Request $request, string $provider): array
    {
        $context = (array) session()->pull('social_auth_context', []);

        if (($context['provider'] ?? null) !== $provider) {
            $context = [];
        }

        $intent = strtolower((string) ($context['intent'] ?? $request->query('intent', 'login')));
        $cycle = strtolower((string) ($context['cycle'] ?? $request->query('cycle', session('selected_cycle', 'monthly'))));
        $catalog = $this->registrationPlanCatalog();
        $planKey = $this->resolveRegistrationPlanKey(
            (string) ($context['plan'] ?? $request->query('plan', session('selected_plan', ''))),
            $catalog
        );

        return [
            'intent' => in_array($intent, ['login', 'register'], true) ? $intent : 'login',
            'plan' => $planKey,
            'cycle' => in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly',
            'plan_id' => isset($context['plan_id']) ? (int) $context['plan_id'] : (session('selected_plan_id') ?: null),
            'amount' => isset($context['amount']) ? (float) $context['amount'] : (session('selected_amount') ?: null),
        ];
    }

    private function ensureSocialRegistrationSubscription(User $user, array $context): ?Subscription
    {
        if (($context['intent'] ?? 'login') !== 'register') {
            return null;
        }

        $existingSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($existingSubscription) {
            return null;
        }

        $catalog = $this->registrationPlanCatalog();
        $planKey = $this->resolveRegistrationPlanKey((string) ($context['plan'] ?? ''), $catalog);
        $cycle = strtolower((string) ($context['cycle'] ?? 'monthly'));

        if (!$planKey || !isset($catalog[$planKey]) || !in_array($cycle, ['monthly', 'yearly'], true)) {
            return null;
        }

        $entry = $catalog[$planKey];
        $plan = !empty($context['plan_id'])
            ? Plan::find((int) $context['plan_id'])
            : Plan::findByCatalogName($entry['label'], $cycle);

        $amount = (float) ($plan?->price ?? ($entry['prices'][$cycle] ?? 0));
        $billingCycle = ucfirst($cycle);
        $planName = $plan?->name ?? $entry['label'];

        session([
            'selected_plan_id' => $plan?->id,
            'selected_plan' => $planName,
            'selected_cycle' => $billingCycle,
            'selected_amount' => $amount,
            'billing_cycle' => $billingCycle,
            'reg_role' => 'admin',
        ]);

        return Subscription::create($this->filterPayloadForTable('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan?->id,
            'plan' => $planName,
            'plan_name' => $planName,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'status' => 'Pending',
            'payment_status' => 'unpaid',
        ]));
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
        $role = strtolower((string) ($user->role ?? ''));

        if (strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com') {
            return true;
        }

        return in_array($role, ['superadmin', 'super_admin'], true);
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

    private function registrationPlanCatalog(): array
    {
        return [
            'basic-solo' => [
                'label' => 'Basic Solo',
                'prices' => ['monthly' => 3000, 'yearly' => 30000],
            ],
            'basic' => [
                'label' => 'Basic',
                'prices' => ['monthly' => 5500, 'yearly' => 55000],
            ],
            'pro-solo' => [
                'label' => 'Professional Solo',
                'prices' => ['monthly' => 7000, 'yearly' => 70000],
            ],
            'pro' => [
                'label' => 'Professional',
                'prices' => ['monthly' => 19500, 'yearly' => 195000],
            ],
            'enterprise-solo' => [
                'label' => 'Enterprise Solo',
                'prices' => ['monthly' => 15000, 'yearly' => 150000],
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'prices' => ['monthly' => 28500, 'yearly' => 285000],
            ],
        ];
    }

    private function resolveRegistrationPlanKey(?string $requestedPlan, ?array $catalog = null): ?string
    {
        $catalog ??= $this->registrationPlanCatalog();
        $normalized = strtolower(trim((string) $requestedPlan));

        if ($normalized === '') {
            return null;
        }

        if (isset($catalog[$normalized])) {
            return $normalized;
        }

        $aliases = [
            'basic solo' => 'basic-solo',
            'basic-solo-monthly' => 'basic-solo',
            'basic-solo-yearly' => 'basic-solo',
            'basic-monthly' => 'basic',
            'basic-yearly' => 'basic',
            'professional solo' => 'pro-solo',
            'pro solo' => 'pro-solo',
            'professional-solo' => 'pro-solo',
            'professional-solo-monthly' => 'pro-solo',
            'professional-solo-yearly' => 'pro-solo',
            'pro-solo-monthly' => 'pro-solo',
            'pro-solo-yearly' => 'pro-solo',
            'professional' => 'pro',
            'pro-monthly' => 'pro',
            'pro-yearly' => 'pro',
            'professional-monthly' => 'pro',
            'professional-yearly' => 'pro',
            'enterprise solo' => 'enterprise-solo',
            'enterprise-solo-monthly' => 'enterprise-solo',
            'enterprise-solo-yearly' => 'enterprise-solo',
            'partner' => null,
        ];

        if (array_key_exists($normalized, $aliases)) {
            return $aliases[$normalized];
        }

        foreach ($catalog as $key => $entry) {
            if (strtolower((string) $entry['label']) === $normalized) {
                return $key;
            }
        }

        return null;
    }

    private function normalizePhoneForAuth(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        return $hasPlus ? ('+' . $digits) : $digits;
    }

    private function filterPayloadForTable(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($_value, $column) => Schema::hasColumn($table, (string) $column))
            ->all();
    }

    private function isSocialProviderConfigured(string $provider): bool
    {
        $cfg = (array) config('services.' . $provider, []);
        return !empty($cfg['client_id']) && !empty($cfg['client_secret']) && !empty($cfg['redirect']);
    }

}
