<?php

namespace App\Http\Controllers;

use App\Models\{User, Company, Subscription, Plan, DeploymentManager, Setting};
use App\Support\ActiveBranchResolver;
use App\Support\AppMailer;
use App\Support\DeviceSessionManager;
use App\Support\InternalTestAccess;
use App\Support\PartnerLocationRepository;
use App\Support\SystemEventMailer;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail, Password, RateLimiter, Schema, Session, Storage};
use Illuminate\Support\Str;
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

        $isPartner = in_array($request->query('type'), ['partner', 'manager'], true);
        $planParam = strtolower($request->query('plan', 'pro'));
        $cycleParam = strtolower($request->query('cycle', 'monthly'));
        $catalog = $this->registrationPlanCatalog();
        $selectedCatalog = $catalog[$planParam] ?? $catalog['pro'];
        $planData = Plan::findByCatalogName($selectedCatalog['label'], $cycleParam);

        if ($isPartner) {
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
            'reg_role' => $isPartner ? 'agent' : 'admin'
        ]);

        return view('Pages.Authentication.saas-register', [
            'company' => $this->getTenantDetails(),
            'selectedPlan' => $finalName,
            'billing_cycle' => $finalCycle,
            'plan_id' => $planData->id ?? null,
            'amount' => $finalPrice,
            'isManager' => $isPartner,
            'countryOptions' => PartnerLocationRepository::countryOptions(),
        ]);
    }

    public function register(Request $request)
    {
        $requestedRole = $request->role ?? session('reg_role', 'admin');
        if ($this->isManagerRoleName($requestedRole) && !Auth::check()) {
            $requestedRole = 'agent';
            $request->merge(['role' => 'agent']);
        }
        $retryableUser = $this->findRetryableRegistrationUser((string) $request->input('email', ''));
        $isPartnerAgent = strtolower((string) $requestedRole) === 'agent';

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|required_without:phone|string|email|max:255',
            'phone' => ['nullable', 'required_without:email', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers(),
            ],
            'profile_photo' => 'nullable|file|mimetypes:image/*|max:2048',
        ];

        if ($isPartnerAgent) {
            $rules['email'] = 'nullable|required_without:phone|string|email|max:255';
            $rules['phone'] = ['nullable', 'required_without:email', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'];
            $rules['country'] = 'required|string|max:120';
            $rules['state_region'] = 'required|string|max:120';
            $rules['local_council'] = 'nullable|string|max:120';
        } elseif ($this->isManagerRoleName($requestedRole)) {
            $rules['email'] = 'required|string|email|max:255';
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

        if ($isPartnerAgent) {
            $validated['country'] = trim((string) ($validated['country'] ?? ''));
            $validated['state_region'] = trim((string) ($validated['state_region'] ?? ''));
            $validated['local_council'] = trim((string) ($validated['local_council'] ?? ''));

            if (!PartnerLocationRepository::hasCountry($validated['country'])) {
                return back()->withErrors(['country' => 'Select a valid country from the list.'])->withInput();
            }

            if (!PartnerLocationRepository::hasState($validated['country'], $validated['state_region'])) {
                return back()->withErrors(['state_region' => 'Select a valid state, region, or county from the list.'])->withInput();
            }

            if (!PartnerLocationRepository::hasCouncil(
                $validated['country'],
                $validated['state_region'],
                $validated['local_council']
            )) {
                return back()->withErrors(['local_council' => 'Select a valid local government or council from the list.'])->withInput();
            }
        }

        $normalizedPhone = $this->normalizePhoneForAuth($validated['phone'] ?? null);
        if ($normalizedPhone && Schema::hasColumn('users', 'phone')) {
            $phoneQuery = User::query()->where('phone', $normalizedPhone);
            if ($retryableUser) {
                $phoneQuery->where('id', '!=', $retryableUser->id);
            }
            $phoneExists = $phoneQuery->exists();
            if ($phoneExists) {
                return back()->withErrors(['phone' => 'This phone is already registered.'])->withInput();
            }
        }

        $resolvedEmail = $this->normalizeEmailForAuth($validated['email'] ?? null);
        if ($resolvedEmail) {
            $emailOwner = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$resolvedEmail])
                ->first();

            if ($emailOwner && (!$retryableUser || (int) $emailOwner->id !== (int) $retryableUser->id)) {
                $response = back()->withErrors(['email' => 'This email is already registered.'])->withInput();

                if ($this->isManagerRoleName($requestedRole)) {
                    $response->with('registered_manager_email', $resolvedEmail)
                        ->with('registered_manager_hint', 'We found an existing account for this email. Sign in to continue, or use a different email.');
                }

                return $response;
            }
        }

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
            $registrationResult = DB::transaction(function () use ($validated, $request, $resolvedEmail, $normalizedPhone, $retryableUser) {
                $role = $request->role ?? session('reg_role', 'admin');
                if ($this->isManagerRoleName($role) && !Auth::check()) {
                    $role = 'agent';
                }
                $stateManagerId = strtolower((string) $role) === 'agent'
                    ? $this->findStateManagerForLocation(
                        (string) ($validated['country'] ?? ''),
                        (string) ($validated['state_region'] ?? '')
                    )
                    : null;

                $requiresSuperAdminApproval = !$this->isManagerRoleName($role) && strtolower((string) $role) !== 'agent';

                $userPayload = $this->filterPayloadForTable('users', [
                    'name' => $validated['name'],
                    'email' => $resolvedEmail,
                    'phone' => $normalizedPhone,
                    'password' => Hash::make($validated['password']),
                    'role' => $role,
                    'is_verified' => 0,
                    'status' => ($requiresSuperAdminApproval || strtolower((string) $role) === 'agent') ? 'pending' : 'active',
                    'country' => $validated['country'] ?? null,
                    'state_region' => $validated['state_region'] ?? null,
                    'local_council' => $validated['local_council'] ?? null,
                    'state_manager_id' => $stateManagerId,
                ]);

                $user = $retryableUser ?: new User();
                if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }
                $user->fill($userPayload);
                $user->save();

                if ($request->hasFile('profile_photo') && Schema::hasColumn('users', 'profile_photo')) {
                    $profileExtension = $request->file('profile_photo')->getClientOriginalExtension() ?: 'jpg';
                    $profileFilename = 'reg-' . $user->id . '-' . Str::lower(Str::random(12)) . '.' . $profileExtension;

                    Storage::disk('public')->putFileAs('profiles/registrations', $request->file('profile_photo'), $profileFilename);
                    $user->profile_photo = 'profiles/registrations/' . $profileFilename;
                    $user->save();
                }

                if ($this->isManagerRoleName($role)) {
                    $manager = DeploymentManager::firstOrNew([
                        'user_id' => $user->id,
                    ]);

                    if (!$manager->exists) {
                        $manager->status = 'pending_info';
                        $manager->commission_rate = 35.00;
                        $manager->auto_payout_enabled = true;
                        $manager->save();

                        DB::afterCommit(function () use ($user) {
                            SystemEventMailer::notifyRegistration($user, 'state_manager');
                        });

                        return [
                            'user' => $user,
                            'role' => $role,
                            'subscription' => null,
                            'resumed' => false,
                        ];
                    }

                    if (in_array(strtolower((string) $manager->status), ['pending', 'pending_info'], true)) {
                        $manager->fill([
                            'status' => 'pending_info',
                            'commission_rate' => $manager->commission_rate ?? 35.00,
                            'auto_payout_enabled' => $manager->auto_payout_enabled ?? true,
                        ]);
                        $manager->save();

                        return [
                            'user' => $user,
                            'role' => $role,
                            'subscription' => null,
                            'resumed' => true,
                        ];
                    }

                    return [
                        'user' => $user,
                        'role' => $role,
                        'subscription' => null,
                        'resumed' => false,
                    ];
                }

                if (strtolower((string) $role) === 'agent') {
                    DB::afterCommit(function () use ($user) {
                        SystemEventMailer::notifyRegistration($user, 'agent');
                    });

                    return [
                        'user' => $user,
                        'role' => $role,
                        'subscription' => null,
                        'resumed' => false,
                    ];
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

                $subscription = Subscription::create($this->filterPayloadForTable('subscriptions', array_merge([
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'plan' => $planName,
                    'plan_name' => $planName,
                    'billing_cycle' => $billingCycle,
                    'amount' => $planAmount,
                    'user_limit' => $plan?->resolvedUserLimit(),
                ], Subscription::trialPayload())));

                DB::afterCommit(function () use ($user, $planName, $planAmount, $billingCycle) {
                    SystemEventMailer::notifyRegistration($user, 'user', [
                        'plan' => $planName,
                        'amount' => (string) $planAmount,
                        'billing_cycle' => $billingCycle,
                    ]);
                });

                return ['user' => $user, 'role' => $role, 'subscription' => $subscription];
            });

            // Login AFTER the transaction commits to ensure the user record exists in the DB.
            // This prevents stale session state if the transaction were to roll back.
            Auth::login($registrationResult['user']);
            $request->session()->regenerate();
            $this->clearRegistrationSession();

            if ($this->isManagerRoleName($registrationResult['role'])) {
                return redirect()->route('manager.verification.form')
                    ->with(
                        'success',
                        ($registrationResult['resumed'] ?? false)
                            ? 'We found your previous partner signup and restored it. Continue your verification profile to finish.'
                            : 'Registration successful. Complete your verification profile to continue.'
                    );
            }

            if (strtolower((string) $registrationResult['role']) === 'agent') {
                return redirect()->route('manager.pending.notice')
                    ->with('success', 'Partner registration submitted. Once approved, your profile will appear under your state manager.');
            }

            return redirect()->route('registration.pending.notice')
                ->with('success', 'Registration submitted successfully. A super admin must approve your account before workspace access opens.');

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

    private function findRetryableDeploymentManager(string $email): ?User
    {
        $email = (string) $this->normalizeEmailForAuth($email);
        if ($email === '') {
            return null;
        }

        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user) {
            return null;
        }

        $manager = DeploymentManager::query()
            ->where('user_id', $user->id)
            ->first();

        if (!$manager) {
            return null;
        }

        return in_array(strtolower((string) $manager->status), ['pending', 'pending_info'], true)
            ? $user
            : null;
    }

    private function findRetryableRegistrationUser(string $email): ?User
    {
        $email = (string) $this->normalizeEmailForAuth($email);
        if ($email === '') {
            return null;
        }

        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user) {
            return null;
        }

        if ($this->isRegistrationRetryableUser($user)) {
            return $user;
        }

        return $this->findRetryableDeploymentManager($email);
    }

    private function isRegistrationRetryableUser(User $user): bool
    {
        if (method_exists($user, 'trashed') && $user->trashed()) {
            return true;
        }

        return strtolower((string) ($user->status ?? 'pending')) !== 'active';
    }

    private function normalizeEmailForAuth(?string $email): ?string
    {
        $email = trim(strtolower((string) $email));

        return $email !== '' ? $email : null;
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN METHODS - CRITICAL FIX
    |--------------------------------------------------------------------------
    */

    public function showLogin(Request $request)
    {
        $currentUser = Auth::user();
        $isDemoSession = $currentUser
            && (
                strtolower((string) $currentUser->email) === 'demo@smartprobook.local'
                || $currentUser->isDemoUser()
                || $request->session()->boolean('is_demo_workspace')
            );

        if (($request->boolean('portal') || $request->boolean('demo') || $isDemoSession) && Auth::check()) {
            Auth::logout();
            $request->session()->forget([
                'is_demo_workspace',
                'user_plan',
                'current_tenant_id',
                'current_tenant_name',
                'workspace_context',
                'demo_customer_preview_id',
                'demo_customer_preview_plan',
                'demo_customer_preview_started_at',
            ]);
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->boolean('flush') || $request->boolean('expired')) {
            $this->clearClientAuthState($request, false);
        }

        if (Auth::check()) {
            return $this->handlePostLoginRedirect();
        }

        // Keep the existing CSRF token for plain form renders. Regenerating it on
        // every GET can leave a freshly-opened auth page with a token that becomes
        // stale after redirects, multiple tabs, or other auth page hops.
        if (!$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }

        if ($request->session()->get('success') === 'Action completed successfully.') {
            $request->session()->flash('success', 'Logout successful.');
        }

        return $this->applyNoStoreHeaders(response()
            ->view('Pages.Authentication.saas-login', ['company' => $this->getTenantDetails()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT'));
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
            return back()->withErrors(['login' => 'Username, email, or phone is required.'])->withInput();
        }

        $throttleKey = Str::lower($loginInput) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['login' => "Too many login attempts. Please wait {$seconds} seconds and try again."])
                ->withInput($request->except('password'));
        }

        $attemptOk = false;
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $attemptOk = Auth::attempt(['email' => $this->normalizeEmailForAuth($loginInput), 'password' => $password], $remember);
        } else {
            $normalizedPhone = $this->normalizePhoneForAuth($loginInput);
            if ($normalizedPhone && Schema::hasColumn('users', 'phone')) {
                $user = User::query()->where('phone', $normalizedPhone)->first();
                if ($user) {
                    $attemptOk = Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
                }
            }

            if (!$attemptOk && Schema::hasColumn('users', 'username')) {
                $user = User::query()
                    ->whereRaw('LOWER(username) = ?', [Str::lower($loginInput)])
                    ->first();

                if ($user) {
                    $attemptOk = Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
                }
            }
        }

        if (!$attemptOk) {
            RateLimiter::hit($throttleKey, 300);
            return back()->withErrors(['login' => 'Invalid credentials.'])->withInput();
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        
        $user = Auth::user();
        $this->resetWorkspaceSessionState($request);

        if ($user?->isDemoUser()) {
            $this->forceDemoWorkspaceSession($request, $user);
        }

        $deviceSession = app(DeviceSessionManager::class)->ensureCurrentSession($request, $user);
        if (($deviceSession['allowed'] ?? true) !== true) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('saas-login')->withErrors([
                'login' => (string) ($deviceSession['message'] ?? 'This account cannot be used on another device right now.'),
            ]);
        }

        app(ActiveBranchResolver::class)->ensureSession($user);

        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        try {
            SystemEventMailer::sendMessage(
                $user->email,
                'Login Alert: SmartProbook',
                'Login Alert',
                'A new login was detected on your account.',
                [
                    'User' => $user->name ?? $user->email,
                    'Email' => $user->email,
                    'IP Address' => $request->ip(),
                    'Time' => now()->toDateTimeString(),
                    'Device' => (string) $request->userAgent(),
                ],
                (int) ($user->company_id ?? 0)
            );
        } catch (\Throwable $mailError) {
            Log::warning('Login alert email failed', ['error' => $mailError->getMessage()]);
        }

        $intended = (string) $request->session()->pull('url.intended', '');
        if ($this->isAllowedPostLoginRedirect($intended)) {
            return redirect()->to($intended);
        }

        // CRITICAL FIX: ALWAYS redirect to /home
        // HomeController@index will handle role-based redirects
        return redirect()->route('home');
    }

    private function isAllowedPostLoginRedirect(?string $target): bool
    {
        $target = trim((string) $target);
        if ($target === '') {
            return false;
        }

        $path = '/' . ltrim((string) parse_url($target, PHP_URL_PATH), '/');

        return str_starts_with($path, '/saas/checkout/')
            || str_starts_with($path, '/saas/setup/')
            || str_starts_with($path, '/saas/success/')
            || str_starts_with($path, '/membership-plans/upgrade');
    }

    public function logout(Request $request)
    {
        $this->clearClientAuthState($request, false);

        $request->session()->flash('success', 'Logout successful.');

        return $this->applyNoStoreHeaders(
            redirect()->route('login')
        );
    }

    public function clearClientAuthState(Request $request, bool $forgetBrowserCookies = true): void
    {
        app(DeviceSessionManager::class)->forgetCurrentSession($request);
        Auth::logout();

        $request->session()->forget([
            'url.intended',
            'current_tenant_id',
            'current_tenant_name',
            'active_branch_id',
            'active_branch_name',
            'active_branch_scope',
            'workspace_context',
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
            'is_demo_workspace',
            'demo_customer_preview_id',
            'demo_customer_preview_plan',
            'demo_customer_preview_started_at',
            'social_auth_context',
            'last_activity',
        ]);

        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (!$forgetBrowserCookies) {
            return;
        }

        // Forget cookies with the exact domain/secure/samesite the session cookie
        // was originally set with — otherwise Safari ignores the Max-Age=0 header
        // because the attributes don't match the stored cookie.
        $domain   = (string) config('session.domain', '');
        $secure   = (bool)   config('session.secure', false);
        $sameSite = (string) config('session.same_site', 'lax');

        $makeExpired = function (string $name) use ($domain, $secure, $sameSite): \Symfony\Component\HttpFoundation\Cookie {
            return \Symfony\Component\HttpFoundation\Cookie::create(
                $name, '', 1, '/',
                $domain !== '' ? $domain : null,
                $secure, true, false, $sameSite ?: 'lax'
            );
        };

        /** @var CookieJar $cookies */
        $cookies = app(CookieJar::class);
        $cookies->queue($makeExpired((string) config('session.cookie')));
        $cookies->queue($makeExpired('XSRF-TOKEN'));
    }

    private function applyNoStoreHeaders($response, bool $clearSiteData = false)
    {
        $response = $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        if ($clearSiteData) {
            $response->header('Clear-Site-Data', '"cache", "cookies", "storage"');
        }

        return $response;
    }

    /**
     * Controlled internal impersonation for final QA only.
     */
    public function impersonateUser(Request $request, int $id): RedirectResponse
    {
        $actor = Auth::user();
        $internalTestAccess = app(InternalTestAccess::class);

        if (!$actor || !$internalTestAccess->canImpersonate($actor)) {
            abort(403, 'Unauthorized impersonation request.');
        }

        if (!$internalTestAccess->isEnabled()) {
            return redirect()->route('super_admin.dashboard')
                ->with('error', 'Internal test impersonation mode is disabled.');
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
        $internalTestAccess->logUsage($actor, 'impersonation_started', [
            'target_user_id' => $target->id,
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
        app(InternalTestAccess::class)->logUsage($admin, 'impersonation_ended');

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

        if ($user?->isDemoUser()) {
            $this->forceDemoWorkspaceSession(request(), $user);
            app(ActiveBranchResolver::class)->ensureSession($user);

            return redirect()->route('home');
        }

        app(ActiveBranchResolver::class)->ensureSession($user);

        // Super Admin
        if ($this->isSuperAdmin($user)) {
            return redirect()->route('super_admin.dashboard');
        }

        // State Manager
        if ($this->isDeploymentManager($user)) {
            return $this->handleDeploymentManagerRedirect($user);
        }

        // Regular users - honour any intended URL (e.g. checkout page the user was trying
        // to reach before session expiry), then fall back to /home.
        // HomeController@index handles all role-based routing from /home.
        return redirect()->intended(route('home'));
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
            return $this->socialiteDriver($provider, $redirectUrl)
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
            $socialUser = $this->socialiteDriver($provider, $this->socialCallbackUrl($provider))
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

        if (($socialContext['intent'] ?? 'login') === 'register') {
            $existingSubscription = Subscription::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($existingSubscription) {
                if (!in_array(strtolower((string) $existingSubscription->payment_status), ['paid', 'free'], true)) {
                    return redirect()->route('saas.checkout', ['id' => $existingSubscription->id])
                        ->with('success', ucfirst($provider) . ' account connected. Complete your checkout to continue.');
                }

                if (!in_array(strtolower((string) $existingSubscription->status), ['active', 'trial'], true) || empty($existingSubscription->company_id)) {
                    return redirect()->route('saas.setup', ['id' => $existingSubscription->id])
                        ->with('success', ucfirst($provider) . ' account connected. Complete your workspace setup to continue.');
                }
            }
        }

        return redirect()->route('home');
    }

    /**
     * Build callback URL for OAuth providers.
     * Prefer configured provider redirect for strict OAuth providers like Google,
     * then fallback to the current runtime host, then APP_URL.
     */
    private function socialCallbackUrl(string $provider): string
    {
        $provider = strtolower(trim($provider));

        $configured = (string) config("services.{$provider}.redirect", '');
        if ($configured !== '') {
            return $configured;
        }

        if (request()) {
            return url('/auth/' . $provider . '/callback');
        }

        return rtrim((string) config('app.url'), '/') . '/auth/' . $provider . '/callback';
    }

    private function socialiteDriver(string $provider, ?string $redirectUrl = null)
    {
        $driver = Socialite::driver($provider);

        if ($redirectUrl) {
            $driver = $driver->redirectUrl($redirectUrl);
        }

        if ($provider === 'google') {
            $driver = $driver
                ->scopes(['openid', 'profile', 'email'])
                ->with([
                    'prompt' => 'select_account',
                    'access_type' => 'offline',
                ]);
        }

        return $driver->stateless();
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

        return Subscription::create($this->filterPayloadForTable('subscriptions', array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan?->id,
            'plan' => $planName,
            'plan_name' => $planName,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'user_limit' => $plan?->resolvedUserLimit(),
        ], Subscription::trialPayload())));
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
        Log::info('Password reset form submitted', [
            'email' => trim((string) $request->input('email')),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'at' => now()->toDateTimeString(),
        ]);

        $request->validate(['email' => 'required|email']);
        $email = trim((string) $request->input('email'));
        Log::info('Password reset validation passed', ['email' => $email]);

        $user = User::withTrashed()->where('email', $email)->first();
        Log::info('Password reset user lookup completed', [
            'email' => $email,
            'user_found' => (bool) $user,
        ]);

        // Keep the response generic so the flow remains secure.
        if (!$user) {
            Log::info('Password reset exited with generic success for missing user', ['email' => $email]);
            return back()->with('reset_success', 'If that email exists in our system, a reset link has been sent.');
        }

        try {
            AppMailer::bootCurrentSettings();
            Log::info('Password reset mail settings booted', [
                'email' => $email,
                'mailer' => AppMailer::preferredMailer(),
                'smtp_ready' => AppMailer::smtpReady(),
            ]);

            $token = Password::broker()->createToken($user);
            Log::info('Password reset token created', [
                'email' => $email,
                'token_length' => strlen((string) $token),
            ]);

            $resetPath = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false);
            $resetUrl = $request->getSchemeAndHttpHost() . $resetPath;

            $mailData = [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            ];

            $buildMessage = function ($message) use ($user) {
                $fromAddress = Setting::mailFromAddress((string) config('mail.from.address', 'support@smartprobook.com'));
                $fromName = Setting::mailFromName((string) config('mail.from.name', config('app.name', 'SmartProbook')));
                $replyTo = trim((string) config('mail.admin_inbox', ''));

                $message->from(
                    $fromAddress,
                    $fromName
                )
                    ->to($user->email, $user->name)
                    ->subject('Reset your password');

                if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($replyTo, $fromName);
                }
            };

            Log::info('Password reset attempting delivery', [
                'email' => $email,
                'to' => $user->email,
                'from' => Setting::mailFromAddress((string) config('mail.from.address', 'support@smartprobook.com')),
            ]);
            $this->sendPasswordResetMailOrFail('emails.password-reset', $mailData, $buildMessage, $email);
            Log::info('Password reset delivery completed', ['email' => $email]);

            return back()->with('reset_success', 'If that email exists in our system, a reset link has been sent.');
        } catch (\Throwable $e) {
            Log::error('Password reset email failed', [
                'email' => $email,
                'mailer' => AppMailer::preferredMailer(),
                'smtp_ready' => AppMailer::smtpReady(),
                'smtp_host' => (string) config('mail.mailers.smtp.host'),
                'smtp_port' => (string) config('mail.mailers.smtp.port'),
                'mail_from' => (string) config('mail.from.address'),
                'error' => $e->getMessage(),
            ]);

            $message = 'We could not send the reset link right now. Please confirm the server SMTP settings and try again.';

            return back()
                ->with('reset_error', $message)
                ->withInput($request->only('email'));
        }
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

        $passwordWasUpdated = false;

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request, &$passwordWasUpdated) {
                $user = $user ?: User::where('email', $request->email)->first();

                if (!$user) {
                    Log::warning('Password reset callback resolved null user', [
                        'email' => $request->email,
                    ]);
                    return;
                }

                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
                $user->setRememberToken(Str::random(60));
                $user->save();

                $passwordWasUpdated = true;
                event(new PasswordReset($user));
            }
        );

        return ($status === Password::PASSWORD_RESET && $passwordWasUpdated)
            ? redirect()->route('saas-login')->with('success', 'Reset successful!')
            : back()->withErrors(['email' => ['Password reset could not be completed. Please request a fresh reset link and try again.']]);
    }

    private function sendPasswordResetMailOrFail(string $view, array $data, callable $callback, string $email): void
    {
        $attempts = [];

        if (AppMailer::smtpReady()) {
            try {
                Mail::mailer('smtp')->send($view, $data, $callback);
                return;
            } catch (\Throwable $smtpException) {
                $attempts['smtp'] = $smtpException->getMessage();
                Log::warning('Password reset SMTP send failed', [
                    'email' => $email,
                    'error' => $smtpException->getMessage(),
                ]);
            }
        }

        try {
            Mail::mailer('sendmail')->send($view, $data, $callback);
            return;
        } catch (\Throwable $sendmailException) {
            $attempts['sendmail'] = $sendmailException->getMessage();
            Log::warning('Password reset sendmail fallback failed', [
                'email' => $email,
                'error' => $sendmailException->getMessage(),
            ]);
        }

        throw new \RuntimeException(
            'Password reset delivery failed via configured mailers: ' . json_encode($attempts, JSON_UNESCAPED_SLASHES)
        );
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
            $deviceSession = app(DeviceSessionManager::class)->ensureCurrentSession($request, Auth::user());
            if (($deviceSession['allowed'] ?? true) !== true) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => (string) ($deviceSession['message'] ?? 'This account cannot be used on another device right now.'),
                ])->withInput($request->only('email'));
            }
            
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
        return in_array(strtolower($user->role), ['state_manager', 'deployment_manager'], true);
    }

    private function isManagerRoleName(?string $role): bool
    {
        return in_array(strtolower((string) $role), ['state_manager', 'deployment_manager'], true);
    }

    private function findStateManagerForLocation(string $country, string $stateRegion): ?int
    {
        if (!Schema::hasTable('deployment_managers') || !Schema::hasColumn('deployment_managers', 'country') || !Schema::hasColumn('deployment_managers', 'state_region')) {
            return null;
        }

        $manager = DeploymentManager::query()
            ->whereRaw('LOWER(COALESCE(country, "")) = ?', [strtolower(trim($country))])
            ->whereRaw('LOWER(COALESCE(state_region, "")) = ?', [strtolower(trim($stateRegion))])
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['active'])
            ->first();

        return $manager?->user_id ? (int) $manager->user_id : null;
    }

    private function handleDeploymentManagerRedirect($user)
    {
        $manager = DeploymentManager::where('user_id', $user->id)->first();
        
        if (!$manager || $manager->status === 'pending_info') {
            return redirect()->route('manager.verification.form');
        }

        if (strtolower((string) $manager->status) === 'active') {
            return redirect()->route('deployment.dashboard');
        }

        return redirect()->route('manager.pending.notice');
    }

    private function getTenantDetails()
    {
        $tenantId = session('current_tenant_id');
        if ($tenantId) {
            return Company::find($tenantId);
        }

        $host = Str::lower((string) request()->getHost());
        if ($host === '') {
            return null;
        }

        $mainDomain = Str::lower(ltrim((string) (config('app.domain') ?: config('session.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''), '.'));
        $centralHosts = array_filter([
            $mainDomain,
            $mainDomain ? 'www.' . preg_replace('/^www\./i', '', $mainDomain) : null,
            parse_url((string) config('app.url'), PHP_URL_HOST),
            'localhost',
            '127.0.0.1',
        ]);

        if (in_array($host, array_map(fn ($value) => Str::lower((string) $value), $centralHosts), true)) {
            return null;
        }

        $subdomain = explode('.', $host)[0] ?? $host;
        $hostWithoutWww = preg_replace('/^www\./i', '', $host);

        return Company::query()
            ->where(function ($query) use ($subdomain, $host, $hostWithoutWww) {
                if (Schema::hasColumn('companies', 'domain_prefix')) {
                    $query->orWhere('domain_prefix', $subdomain);
                }

                if (Schema::hasColumn('companies', 'subdomain')) {
                    $query->orWhere('subdomain', $subdomain);
                }

                if (Schema::hasColumn('companies', 'domain')) {
                    $query->orWhere('domain', $host)
                        ->orWhere('domain', $hostWithoutWww);
                }
            })
            ->first();
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

    private function resetWorkspaceSessionState(Request $request): void
    {
        $request->session()->forget([
            'current_tenant_id',
            'current_tenant_name',
            'active_branch_id',
            'active_branch_name',
            'active_branch_scope',
            'workspace_context',
            'is_demo_workspace',
            'demo_customer_preview_id',
            'demo_customer_preview_plan',
            'demo_customer_preview_started_at',
            'url.intended',
        ]);
    }

    private function forceDemoWorkspaceSession(Request $request, User $user): void
    {
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId <= 0) {
            return;
        }

        $request->session()->put('is_demo_workspace', true);
        $request->session()->put('workspace_context', 'business');
        $request->session()->put('current_tenant_id', $companyId);

        $companyName = $user->company?->company_name ?? $user->company?->name ?? null;
        if ($companyName) {
            $request->session()->put('current_tenant_name', $companyName);
        }
    }

    public function registrationStates(Request $request)
    {
        $country = (string) $request->query('country', '');

        return response()->json([
            'country' => $country,
            'states' => PartnerLocationRepository::statesForCountry($country),
        ]);
    }

    public function registrationCouncils(Request $request)
    {
        $country = (string) $request->query('country', '');
        $state = (string) $request->query('state', '');

        return response()->json([
            'country' => $country,
            'state' => $state,
            'councils' => PartnerLocationRepository::councilsForCountryState($country, $state),
        ]);
    }

    private function regionOptions(): array
    {
        return PartnerLocationRepository::regions();
    }

    private function registrationPlanCatalog(): array
    {
        return [
            'starter-solo' => [
                'label' => 'Starter Solo',
                'prices' => ['monthly' => 1000, 'yearly' => 10000],
            ],
            'starter' => [
                'label' => 'Starter',
                'prices' => ['monthly' => 1000, 'yearly' => 10000],
            ],
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
            'starter solo' => 'starter-solo',
            'starter-solo-monthly' => 'starter-solo',
            'starter-solo-yearly' => 'starter-solo',
            'starter-monthly' => 'starter',
            'starter-yearly' => 'starter',
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
