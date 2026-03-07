<?php

namespace App\Http\Controllers;

use App\Models\{Subscription, Plan, User, Company, DeploymentManager, Domain, Bank, Setting};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Http, Log, Mail, Schema};
use Carbon\Carbon;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PLANS
    |--------------------------------------------------------------------------
    */
    public function plans()
    {
        $plans = Plan::where('status', 'active')
            ->where('is_active', 1)
            ->orderBy('price', 'asc')
            ->get();

        return view('Membership.membership-plans', compact('plans'));
    }

    /*
    |--------------------------------------------------------------------------
    | SETUP WIZARD — domain config
    | Route: GET /saas/setup/{id?}   name: saas.setup
    |--------------------------------------------------------------------------
    */
    public function create(Request $request, $id = null)
    {
        $subscription = $id
            ? Subscription::where('id', $id)->where('user_id', auth()->id())->first()
            : Subscription::where('user_id', auth()->id())
                ->where('status', 'Pending')
                ->latest()
                ->first();

        if (!$subscription) {
            return redirect()->route('membership-plans')
                ->with('error', 'Please select a plan to begin setup.');
        }

        $planModel = Plan::find($subscription->plan_id);

        return view('SuperAdmin.domain-request', [
            'subscription'   => $subscription,
            'plan'           => strtolower($planModel->name ?? 'Standard'),
            'cycle'          => strtolower($subscription->billing_cycle),
            'selectedPrice'  => $subscription->amount,
            'session_domain' => env('SESSION_DOMAIN', 'smatbook.com'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SETUP STORE — process domain setup
    | Route: POST /saas/setup   name: saas.store
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'domain_prefix'   => 'required|alpha_dash|unique:subscriptions,domain_prefix|unique:companies,domain_prefix',
            'subscription_id' => 'required|exists:subscriptions,id',
            'employees'       => 'nullable|string',
        ]);

        $subscription = Subscription::findOrFail($request->subscription_id);
        $user = auth()->user();

        if ($subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $prefix = strtolower($request->domain_prefix);

            $subscription->update([
                'domain_prefix' => $prefix,
                'employee_size' => $request->employees,
            ]);

            $company = Company::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'domain_prefix' => $prefix,
                    'company_name'  => $user->name . "'s Workspace",
                    'status'        => 'pending',
                    'owner_id'      => $user->id,
                ]
            );

            $subscription->update(['company_id' => $company->id]);
            $user->update(['company_id' => $company->id]);

            if (in_array($user->role, ['deployment_manager', 'manager'])) {
                DeploymentManager::firstOrCreate(
                    ['user_id' => $user->id],
                    ['deployment_limit' => 100, 'commission_rate' => 35, 'status' => 'active']
                );
                $user->update(['status' => 'active', 'is_verified' => 1]);
                DB::commit();
                return redirect()->route('deployment.dashboard')
                    ->with('success', 'Deployment hub initialized!');
            }

            DB::commit();
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('success', 'Workspace configured! Proceed to payment.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Workspace setup failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Setup failed: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECKOUT PAGE
    | Route: GET /saas/checkout/{id}   name: saas.checkout
    |
    | Accessible by:
    |  - The subscription owner (regular customer logging in first time)
    |  - A deployment manager paying on behalf of a customer they created
    |
    | NEVER redirects to /home — that sends manager back to dashboard.
    |--------------------------------------------------------------------------
    */
    public function checkout($id)
    {
        $id = (int) $id;

        if (!$id) {
            return redirect()->route('membership-plans')
                ->with('error', 'Invalid subscription.');
        }

        try {
            $subscription = Subscription::with(['user', 'company'])->findOrFail($id);

            // Already paid
            if ($subscription->payment_status === 'paid') {
                return redirect()->route('saas.payment.success')
                    ->with('info', 'This subscription is already active.');
            }

            $currentUser          = auth()->user();
            $isDeploymentCheckout = $this->isDeploymentCheckout($subscription);
            $isManager            = DeploymentManager::where('user_id', $currentUser->id)->exists();

            // Keep manager identity intact for deployment-assisted checkout.
            // Switching auth user here can cause wrong dashboard/sidebar context on failure.
            if ($isManager && $isDeploymentCheckout) {
                session([
                    'checkout_from_deployment'   => true,
                    'deployment_manager_id'      => $currentUser->id,
                    'deployment_customer_id'     => $subscription->user_id,
                    'deployment_company_id'      => $subscription->company_id,
                    'deployment_subscription_id' => $subscription->id,
                ]);
            }

            // Auth check: own subscription OR a deployment manager
            if ($subscription->user_id !== $currentUser->id && !$isManager) {
                Log::warning('Unauthorized checkout attempt', [
                    'subscription_owner' => $subscription->user_id,
                    'current_user'       => $currentUser->id,
                ]);
                // Do NOT redirect to /home — redirect to dashboard instead
                return redirect()->route('deployment.dashboard')
                    ->with('error', 'You do not have permission to access that checkout.');
            }

            Log::info('Checkout loaded', [
                'subscription_id'       => $subscription->id,
                'by'                    => $currentUser->id,
                'is_manager'            => $isManager,
                'is_deployment_checkout'=> $isDeploymentCheckout,
            ]);

            $bankAccounts = collect();
            if (Schema::hasTable('banks')) {
                $columns = ['id'];

                foreach (['name', 'account_number', 'account_holder_name', 'branch'] as $column) {
                    if (Schema::hasColumn('banks', $column)) {
                        $columns[] = $column;
                    }
                }

                $bankAccounts = Bank::query()
                    ->select($columns)
                    ->when(in_array('name', $columns, true), fn ($q) => $q->orderBy('name'))
                    ->get();
            }

            return view('Saas.checkout', [
                'subscription'          => $subscription,
                'isDeploymentCheckout'  => $isDeploymentCheckout,
                'isManager'             => $isManager,
                'bankAccounts'          => $bankAccounts,
                'stripePublishableKey'  => $this->resolveStripePublishableKey(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('membership-plans')
                ->with('error', 'Subscription not found.');
        } catch (\Exception $e) {
            Log::error('Checkout error', ['id' => $id, 'error' => $e->getMessage()]);
            // NEVER redirect to /home — it loops managers back to dashboard
            return redirect()->route('membership-plans')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESS PAYMENT — Stripe-only
    | Route: POST /saas/payment/process/{id}   name: saas.payment.process.checkout
    |--------------------------------------------------------------------------
    */
    public function processPayment(Request $request, $id)
    {
        Log::info('processPayment hit', [
            'subscription_id' => (int) $id,
            'actor_user_id' => auth()->id(),
            'gateway' => $request->input('gateway'),
            'has_reference' => (bool) $request->input('transfer_reference'),
        ]);

        $subscription = Subscription::findOrFail($id);

        $request->validate([
            'gateway' => 'required|in:stripe,paystack,flutterwave',
        ]);

        switch ($request->gateway) {
            case 'stripe':
                return $this->initStripe($subscription, $request);
            case 'paystack':
                return $this->initPaystack($subscription);
            case 'flutterwave':
                return $this->initFlutterwave($subscription);
            default:
                return redirect()->route('saas.checkout', $id)
                    ->with('error', 'Unsupported payment method selected.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GATEWAY INITIATORS
    |--------------------------------------------------------------------------
    */
    private function initStripe(Subscription $subscription, Request $request)
    {
        $secretSource = 'none';
        $secret = $this->resolveStripeSecret();
        if ($secret !== '') {
            $envSecret = trim((string) config('services.stripe.secret'));
            if ($this->isUsableStripeSecret($envSecret)) {
                $secretSource = '.env';
            } else {
                $dbSecret = trim((string) Setting::getSensitive('stripe_secret', ''));
                $secretSource = $this->isUsableStripeSecret($dbSecret) ? 'settings' : 'unknown';
            }
        }

        Log::info('Stripe key resolution', [
            'subscription_id' => $subscription->id,
            'source' => $secretSource,
            'mode' => str_starts_with($secret, 'sk_live_') ? 'live' : (str_starts_with($secret, 'sk_test_') ? 'test' : 'invalid'),
            'usable' => $secret !== '',
        ]);

        if ($secret === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stripe secret key is missing or invalid. Update it in Payment Settings.'
                ], 422);
            }

            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Stripe secret key is missing or invalid. Update it in Payment Settings.');
        }

        $embedded = $request->boolean('embedded')
            || strtolower((string) $request->input('ui_mode')) === 'embedded'
            || $request->expectsJson();

        $amountKobo = max(1, (int) round(((float) $subscription->amount) * 100));
        $planName = (string) ($subscription->plan_name ?? $subscription->plan ?? 'Subscription');
        $returnUrl = route('saas.payment.callback', [
            'sub_id' => $subscription->id,
            'gateway' => 'stripe',
        ]) . '&reference={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('saas.checkout', $subscription->id);

        try {
            $payload = [
                'mode' => 'payment',
                'customer_email' => (string) optional(auth()->user())->email,
                'line_items[0][price_data][currency]' => 'ngn',
                'line_items[0][price_data][unit_amount]' => $amountKobo,
                'line_items[0][price_data][product_data][name]' => 'SmartProbook ' . $planName,
                'line_items[0][quantity]' => 1,
                'metadata[subscription_id]' => (string) $subscription->id,
                'metadata[user_id]' => (string) $subscription->user_id,
            ];

            if ($embedded) {
                $payload['ui_mode'] = 'embedded';
                $payload['return_url'] = $returnUrl;
                $payload['redirect_on_completion'] = 'always';
            } else {
                $payload['success_url'] = $returnUrl;
                $payload['cancel_url'] = $cancelUrl;
            }

            $response = Http::asForm()
                ->withToken($secret)
                ->acceptJson()
                ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

            if (!$response->successful()) {
                $stripeError = (string) data_get($response->json(), 'error.message', '');
                Log::error('Stripe session init failed', [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $message = $stripeError !== '' ? ('Stripe error: ' . $stripeError) : 'Unable to initialize Stripe checkout right now.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return redirect()->route('saas.checkout', $subscription->id)->with('error', $message);
            }

            if ($embedded) {
                $clientSecret = (string) $response->json('client_secret', '');
                $sessionId = (string) $response->json('id', '');

                if ($clientSecret === '' || $sessionId === '') {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Stripe embedded checkout token was not returned.'], 422);
                    }

                    return redirect()->route('saas.checkout', $subscription->id)
                        ->with('error', 'Stripe embedded checkout token was not returned.');
                }

                return response()->json([
                    'client_secret' => $clientSecret,
                    'session_id' => $sessionId,
                ]);
            }

            $url = (string) $response->json('url', '');
            if ($url === '') {
                return redirect()->route('saas.checkout', $subscription->id)
                    ->with('error', 'Stripe checkout URL was not returned.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('Stripe session init exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Stripe checkout is temporarily unavailable.'], 500);
            }

            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Stripe checkout is temporarily unavailable.');
        }
    }

    private function initPaystack(Subscription $subscription)
    {
        $secret = $this->resolvePaystackSecret();
        if ($secret === '') {
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Paystack secret key is missing or invalid. Update it in Payment Settings.');
        }

        $amountKobo = max(1, (int) round(((float) $subscription->amount) * 100));
        $callbackUrl = route('saas.payment.callback', [
            'sub_id' => $subscription->id,
            'gateway' => 'paystack',
        ]);

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => (string) optional(auth()->user())->email,
                    'amount' => $amountKobo,
                    'callback_url' => $callbackUrl,
                    'metadata' => [
                        'subscription_id' => (string) $subscription->id,
                        'user_id' => (string) $subscription->user_id,
                    ],
                ]);

            if (!$response->successful() || !(bool) $response->json('status')) {
                Log::error('Paystack init failed', [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return redirect()->route('saas.checkout', $subscription->id)
                    ->with('error', 'Unable to initialize Paystack right now.');
            }

            $url = (string) data_get($response->json(), 'data.authorization_url', '');
            if ($url === '') {
                return redirect()->route('saas.checkout', $subscription->id)
                    ->with('error', 'Paystack checkout URL was not returned.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('Paystack init exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Paystack checkout is temporarily unavailable.');
        }
    }

    private function initFlutterwave(Subscription $subscription)
    {
        $secret = $this->resolveFlutterwaveSecret();
        if ($secret === '') {
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Flutterwave secret key is missing or invalid. Update it in Payment Settings.');
        }

        $txRef = 'SPB-FLW-' . $subscription->id . '-' . Str::upper(Str::random(10));
        $callbackUrl = route('saas.payment.callback', [
            'sub_id' => $subscription->id,
            'gateway' => 'flutterwave',
        ]);

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $txRef,
                    'amount' => (float) $subscription->amount,
                    'currency' => 'NGN',
                    'redirect_url' => $callbackUrl,
                    'customer' => [
                        'email' => (string) optional(auth()->user())->email,
                        'name' => (string) optional(auth()->user())->name,
                    ],
                    'meta' => [
                        'subscription_id' => (string) $subscription->id,
                        'user_id' => (string) $subscription->user_id,
                    ],
                    'customizations' => [
                        'title' => 'SmartProbook Subscription',
                    ],
                ]);

            if (!$response->successful() || strtolower((string) $response->json('status')) !== 'success') {
                Log::error('Flutterwave init failed', [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return redirect()->route('saas.checkout', $subscription->id)
                    ->with('error', 'Unable to initialize Flutterwave right now.');
            }

            $url = (string) data_get($response->json(), 'data.link', '');
            if ($url === '') {
                return redirect()->route('saas.checkout', $subscription->id)
                    ->with('error', 'Flutterwave checkout URL was not returned.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('Flutterwave init exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Flutterwave checkout is temporarily unavailable.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CALLBACK — verify & route to correct handler
    | Route: GET/POST /saas/payment/callback   name: saas.payment.callback
    |
    | FIX: Detects deployment checkout via BOTH session AND database (deployed_by).
    | This means even if session is lost, deployment payments are handled correctly.
    |--------------------------------------------------------------------------
    */
    public function handlePaymentCallback(Request $request)
    {
        $subId     = $request->sub_id    ?? $request->query('sub_id');
        $gateway   = strtolower((string) ($request->gateway ?? $request->query('gateway') ?? 'stripe'));
        $reference = (string) ($request->reference ?? $request->query('reference') ?? '');

        if ($reference === '' && $gateway === 'flutterwave') {
            $reference = (string) ($request->query('tx_ref') ?? $request->input('tx_ref') ?? '');
        }

        if (!$subId) {
            return redirect()->route('membership-plans')
                ->with('error', 'Missing payment verification data.');
        }

        $subscription = Subscription::with(['user', 'company'])->findOrFail($subId);

        $verification = $this->verifyPayment($reference, $gateway, $request);
        if (!(bool) ($verification['ok'] ?? false)) {
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Payment verification failed. Please try again.');
        }
        $reference = (string) ($verification['reference'] ?? $reference);

        // ── DEPLOYMENT DETECTION: session OR database ──
        $isDeploymentBySession = session()->has('checkout_from_deployment');
        $resolvedManagerId = $this->resolveDeploymentManagerId($subscription);
        $isDeploymentByDB = (bool) $resolvedManagerId;

        if ($isDeploymentBySession || $isDeploymentByDB) {
            Log::info('Routing to deployment payment handler', [
                'via_session' => $isDeploymentBySession,
                'via_db'      => $isDeploymentByDB,
                'deployed_by' => $subscription->deployed_by,
                'resolved_manager_id' => $resolvedManagerId,
            ]);
            return $this->handleDeploymentPayment($subscription, $reference);
        }

        return $this->handleRegularPayment($subscription, $reference);
    }

    /*
    |--------------------------------------------------------------------------
    | REGULAR PAYMENT — self-registered customers
    |--------------------------------------------------------------------------
    */
    private function handleRegularPayment($subscription, $reference)
    {
        DB::beginTransaction();
        try {
            $startDate = now();
            $endDate   = strtolower($subscription->billing_cycle) === 'yearly'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            $subscriptionUpdateData = [
                'status'                => 'Active',
                'payment_status'        => 'paid',
                'transaction_reference' => $reference,
                'payment_date'          => now(),
                'paid_at'               => now(),
                'start_date'            => $startDate,
                'end_date'              => $endDate,
            ];

            if (Schema::hasColumn('subscriptions', 'payment_gateway')) {
                $subscriptionUpdateData['payment_gateway'] = request('gateway', request()->query('gateway', 'stripe'));
            }
            if (Schema::hasColumn('subscriptions', 'payment_reference')) {
                $subscriptionUpdateData['payment_reference'] = $reference;
            }

            if (Schema::hasColumn('subscriptions', 'activated_at')) {
                $subscriptionUpdateData['activated_at'] = now();
            }

            $subscription->update($subscriptionUpdateData);

            if ($subscription->company) {
                $subscription->company->update(['status' => 'active']);
            }

            if ($subscription->user) {
                $subscription->user->update([
                    'status'            => 'active',
                    'is_verified'       => 1,
                    'email_verified_at' => now(),
                    'company_id'        => $subscription->company_id,
                ]);
            }

            // Ensure workspace/subdomain records are provisioned immediately after payment.
            $this->deployWorkspace($subscription);

            DB::commit();

            $this->sendWelcomeEmail($subscription->user, $subscription, $subscription->company);

            session(['last_paid_subscription_id' => $subscription->id]);

            return redirect()->route('saas.success', ['id' => $subscription->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Regular payment failed', ['error' => $e->getMessage()]);
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('error', 'Activation failed. Please contact support.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DEPLOYMENT PAYMENT — manager paying for a customer
    |--------------------------------------------------------------------------
    */
    private function handleDeploymentPayment($subscription, $reference)
    {
        DB::beginTransaction();
        try {
            $managerId = $this->resolveDeploymentManagerId($subscription);

            $startDate = now();
            $endDate   = strtolower($subscription->billing_cycle) === 'yearly'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            // 1. Activate subscription
            $subscriptionUpdateData = [
                'status'                => 'Active',
                'payment_status'        => 'paid',
                'transaction_reference' => $reference,
                'payment_date'          => now(),
                'paid_at'               => now(),
                'start_date'            => $startDate,
                'end_date'              => $endDate,
            ];

            if (Schema::hasColumn('subscriptions', 'payment_gateway')) {
                $subscriptionUpdateData['payment_gateway'] = request('gateway', request()->query('gateway', 'stripe'));
            }
            if (Schema::hasColumn('subscriptions', 'payment_reference')) {
                $subscriptionUpdateData['payment_reference'] = $reference;
            }

            if (Schema::hasColumn('subscriptions', 'activated_at')) {
                $subscriptionUpdateData['activated_at'] = now();
            }
            if ($managerId && Schema::hasColumn('subscriptions', 'deployed_by')) {
                $subscriptionUpdateData['deployed_by'] = $managerId;
            }

            $subscription->update($subscriptionUpdateData);

            // 2. Activate company
            $company = $subscription->company;
            if ($company) {
                $companyUpdateData = [
                    'status'      => 'active',
                ];

                if ($managerId && Schema::hasColumn('companies', 'deployed_by')) {
                    $companyUpdateData['deployed_by'] = $managerId;
                }

                $company->update($companyUpdateData);
            }

            // 3. Activate customer
            $customer = $subscription->user;
            if ($customer) {
                $customer->update([
                    'is_verified'       => 1,
                    'status'            => 'active',
                    'email_verified_at' => now(),
                    'company_id'        => $company?->id,
                ]);
            }

            // Ensure workspace/subdomain records are provisioned immediately after payment.
            $this->deployWorkspace($subscription);

            // 4. Record commission across configured commission tables.
            $commissionAmount = $this->recordDeploymentCommission($subscription, $managerId, $company?->id);

            // 5. Send credentials email
            $regData = session('pending_registration', []);
            if (!empty($regData['password'])) {
                $this->sendDeploymentWelcomeEmail($company, $customer, $regData['password']);
            } else {
                $this->sendWelcomeEmail($customer, $subscription, $company);
            }

            DB::commit();

            // Store for success page
            session([
                'last_paid_subscription_id'    => $subscription->id,
                'deployment_return_manager_id' => $managerId,
            ]);

            // Clear all deployment session data
            session()->forget([
                'checkout_from_deployment',
                'pending_registration',
                'deployment_manager_id',
                'deployment_customer_id',
                'deployment_company_id',
                'deployment_commission_rate',
                'deployment_plan_name',
                'deployment_subscription_id',
            ]);

            Log::info('Deployment payment activated', [
                'subscription_id' => $subscription->id,
                'manager_id'      => $managerId,
                'commission'      => $commissionAmount,
            ]);

            // Go directly to unified success page
            return redirect()->route('saas.success', ['id' => $subscription->id])
                ->with('success', 'Payment confirmed! Customer workspace is now live.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deployment payment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('saas.checkout', $subscription->id)
                ->with('warning', 'Payment recorded but provisioning was incomplete. Please contact support.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS — regular customer success page (used by handleRegularPayment)
    | Route: GET /saas/success/{id}   name: saas.success
    |--------------------------------------------------------------------------
    */
    public function success(Request $request, $id)
    {
        $subscription = Subscription::with(['user', 'company'])->findOrFail($id);
        $domain       = env('SESSION_DOMAIN', 'smatbook.com');
        $protocol     = request()->secure() ? 'https://' : 'http://';
        $prefix       = $subscription->domain_prefix ?? $subscription->company?->domain_prefix;

        $workspaceUrl = $prefix
            ? $protocol . $prefix . '.' . $domain
            : $protocol . $domain . '/home';

        if (app()->environment('local')) {
            session(['current_tenant_id' => $subscription->company_id]);
            $workspaceUrl = route('home');
        }

        $currentUser = auth()->user();
        $isManager = $currentUser
            ? DeploymentManager::where('user_id', $currentUser->id)->exists()
            : false;

        return view('Saas.success', [
            'subscription'  => $subscription,
            'workspace_url' => $workspaceUrl,
            'workspaceUrl'  => $workspaceUrl,
            'company'       => $subscription->company,
            'domain'        => $domain,
            'isManager'     => $isManager,
            'returnUrl'     => $isManager ? route('deployment.dashboard') : route('home'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUCCESS — deployment manager success page
    | Route: GET /saas/payment/success   name: saas.payment.success
    |
    | Goes here directly after deployment payment — skips /home entirely.
    | The $isManager flag drives whether to show manager or customer view.
    |--------------------------------------------------------------------------
    */
    public function paymentSuccess()
    {
        $subscriptionId = session('last_paid_subscription_id');
        $currentUser    = auth()->user();
        $isManager      = DeploymentManager::where('user_id', $currentUser->id)->exists();

        $subscription = $subscriptionId
            ? Subscription::with(['user', 'company'])->find($subscriptionId)
            : null;

        // Fallback: find most recently paid subscription
        if (!$subscription) {
            $subscription = $isManager
                ? Subscription::with(['user', 'company'])
                    ->where('deployed_by', $currentUser->id)
                    ->where('payment_status', 'paid')
                    ->latest('paid_at')
                    ->first()
                : Subscription::with(['user', 'company'])
                    ->where('user_id', $currentUser->id)
                    ->where('payment_status', 'paid')
                    ->latest('paid_at')
                    ->first();
        }

        $domain       = env('SESSION_DOMAIN', 'smatbook.com');
        $prefix       = $subscription?->domain_prefix
                        ?? $subscription?->company?->domain_prefix;
        $workspaceUrl = $prefix ? 'https://' . $prefix . '.' . $domain : null;

        return view('Saas.success', [
            'subscription' => $subscription,
            'workspaceUrl' => $workspaceUrl,
            'isManager'    => $isManager,
            'returnUrl'    => $isManager ? route('deployment.dashboard') : route('home'),
            'domain'       => $domain,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SWITCH BACK TO DEPLOYMENT MANAGER
    | Route: GET /saas/switch-back-manager   name: saas.switch-back-manager
    |--------------------------------------------------------------------------
    */
    public function switchBackToManager()
    {
        $managerId = (int) session('deployment_return_manager_id');

        if (!$managerId) {
            return redirect()->route('home')
                ->with('warning', 'Manager return session is no longer available.');
        }

        $managerUser = User::find($managerId);
        $isManager = $managerUser
            && DeploymentManager::where('user_id', $managerId)->exists();

        if (!$isManager) {
            return redirect()->route('home')
                ->with('error', 'Unable to switch back to deployment manager.');
        }

        Auth::loginUsingId($managerId);
        session()->forget('deployment_return_manager_id');

        return redirect()->route('deployment.dashboard')
            ->with('success', 'Switched back to deployment manager dashboard.');
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CANCEL
    |--------------------------------------------------------------------------
    */
    public function paymentCancel()
    {
        return view('subscriptions.cancel');
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: subscriptions list
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $subscriptions = Subscription::with(['user', 'company'])->latest()->paginate(15);
        return view('SuperAdmin.subscription', compact('subscriptions'));
    }

    public function show($id)
    {
        $subscription = Subscription::with(['user', 'company'])->findOrFail($id);

        if (view()->exists('SuperAdmin.subscriptions.show')) {
            return view('SuperAdmin.subscriptions.show', compact('subscription'));
        }

        if (view()->exists('SuperAdmin.subscription-show')) {
            return view('SuperAdmin.subscription-show', compact('subscription'));
        }

        return redirect()->route('super_admin.subscriptions.index')
            ->with('info', 'Subscription details loaded. A dedicated details page is not configured yet.');
    }

    public function edit($id)
    {
        $subscription = Subscription::with(['user', 'company'])->findOrFail($id);
        $plans = Plan::query()
            ->where(function ($q) {
                if (Schema::hasColumn('plans', 'status')) {
                    $q->where('status', 'active');
                }
                if (Schema::hasColumn('plans', 'is_active')) {
                    $q->orWhere('is_active', 1);
                }
            })
            ->orderBy('price')
            ->get();

        if (view()->exists('SuperAdmin.subscriptions.edit')) {
            return view('SuperAdmin.subscriptions.edit', compact('subscription', 'plans'));
        }

        if (view()->exists('SuperAdmin.subscription-edit')) {
            return view('SuperAdmin.subscription-edit', compact('subscription', 'plans'));
        }

        $subscriptions = Subscription::with(['user', 'company'])->latest()->paginate(15);
        return view('SuperAdmin.subscription', compact('subscriptions', 'subscription', 'plans'));
    }

    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update($request->validate([
            'status'         => 'required|in:Active,Pending,Cancelled,Expired',
            'payment_status' => 'required|in:paid,unpaid,pending,failed,pending_verification',
            'end_date'       => 'required|date',
        ]));
        return redirect()->route('super_admin.subscriptions.transactions')
            ->with('success', 'Subscription updated.');
    }

    public function transactions()
    {
        $purchasereports = Subscription::with(['user', 'company'])->latest()->paginate(15);
        return view('SuperAdmin.purchase-transaction', compact('purchasereports'));
    }

    public function updateStatus(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $status = trim((string) $validated['status']);
        $paymentStatus = in_array(strtolower($status), ['active', 'paid'], true) ? 'paid' : 'pending';

        $subscription->update([
            'status' => ucfirst(strtolower($status)),
            'payment_status' => $paymentStatus,
        ]);

        return back()->with('success', 'Subscription status updated successfully.');
    }

    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

        return redirect()->route('super_admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    public function downloadPDF($id)
    {
        $subscription = Subscription::with(['company', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('SuperAdmin.subscriptions.show_pdf', compact('subscription'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('SmartProbook_Receipt_' . $subscription->id . '.pdf');
    }

    public function printInvoice($id)
    {
        $subscription = Subscription::with(['company', 'user'])->findOrFail($id);
        return view('print.invoice', compact('subscription'));
    }

    public function customReview()
    {
        return view('management.review');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $host = strtolower((string) $request->getHost());
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true)
            || app()->environment('local');

        if ($isLocalHost) {
            return redirect()->route('saas-login');
        }

        $domain = env('SESSION_DOMAIN', 'smatbook.com');
        return redirect()->away('https://' . $domain . '/login');
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Detect deployment checkout via session OR database.
     * Session alone is unreliable (can be lost after redirect).
     * Database deployed_by is the authoritative fallback.
     */
    private function isDeploymentCheckout(Subscription $subscription): bool
    {
        return session()->has('checkout_from_deployment')
            || (bool) $this->resolveDeploymentManagerId($subscription);
    }

    private function resolveDeploymentManagerId(Subscription $subscription): ?int
    {
        $sessionManagerId = (int) session('deployment_manager_id', 0);
        if ($sessionManagerId > 0) {
            return $sessionManagerId;
        }

        $subscriptionManagerId = (int) ($subscription->deployed_by ?? 0);
        if ($subscriptionManagerId > 0) {
            return $subscriptionManagerId;
        }

        $companyManagerId = (int) optional($subscription->company)->deployed_by;
        if ($companyManagerId > 0) {
            return $companyManagerId;
        }

        if (Schema::hasTable('deployment_companies') && $subscription->company_id) {
            $mappedManagerId = (int) DB::table('deployment_companies')
                ->where('company_id', (int) $subscription->company_id)
                ->value('manager_id');
            if ($mappedManagerId > 0) {
                return $mappedManagerId;
            }
        }

        return null;
    }

    private function resolveCommissionRate(?int $managerId): float
    {
        if (!$managerId) {
            return 35.0;
        }

        $rate = (float) DeploymentManager::query()
            ->where('user_id', $managerId)
            ->value('commission_rate');

        return $rate > 0 ? $rate : 35.0;
    }

    private function calculateCommissionAmount(Subscription $subscription, ?int $managerId): float
    {
        $rate = $this->resolveCommissionRate($managerId);
        return round(((float) $subscription->amount * $rate) / 100, 2);
    }

    private function recordDeploymentCommission(Subscription $subscription, ?int $managerId, ?int $companyId = null): float
    {
        $commissionAmount = $this->calculateCommissionAmount($subscription, $managerId);
        if (!$managerId) {
            Log::warning('Skipped commission write: no deployment manager linked.', [
                'subscription_id' => $subscription->id,
                'company_id' => $companyId ?? $subscription->company_id,
            ]);
            return $commissionAmount;
        }

        $companyId = $companyId ?: $subscription->company_id;
        $commissionRate = $this->resolveCommissionRate($managerId);

        // Write to both legacy/new tables when present so all dashboards stay in sync.
        foreach (['deployment_commissions', 'manager_commissions'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $payload = [
                    'manager_id'      => $managerId,
                    'subscription_id' => $subscription->id,
                    'commission_rate' => $commissionRate,
                    'status'          => $table === 'deployment_commissions' ? 'paid' : 'credited',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                if (Schema::hasColumn($table, 'company_id') && $companyId) {
                    $payload['company_id'] = $companyId;
                }

                if (Schema::hasColumn($table, 'processed_at')) {
                    $payload['processed_at'] = now();
                }

                if (Schema::hasColumn($table, 'commission_amount')) {
                    if (Schema::hasColumn($table, 'amount')) {
                        $payload['amount'] = (float) $subscription->amount;
                    }
                    $payload['commission_amount'] = $commissionAmount;
                } elseif (Schema::hasColumn($table, 'amount')) {
                    $payload['amount'] = $commissionAmount;
                }

                DB::table($table)->updateOrInsert(
                    [
                        'manager_id' => $managerId,
                        'subscription_id' => $subscription->id,
                    ],
                    $payload
                );
            } catch (\Throwable $commissionError) {
                Log::error('Commission write failed; continuing activation.', [
                    'table' => $table,
                    'manager_id' => $managerId,
                    'subscription_id' => $subscription->id,
                    'error' => $commissionError->getMessage(),
                ]);
            }
        }

        return $commissionAmount;
    }

    private function isAdmin(): bool
    {
        return auth()->check() && in_array(
            strtolower(auth()->user()->role ?? ''),
            ['super_admin', 'superadmin']
        );
    }

    private function verifyPayment(string $reference, string $gateway, ?Request $request = null): array
    {
        $gateway = strtolower((string) $gateway);
        $reference = trim((string) $reference);

        if ($gateway === 'stripe') {
            if ($reference === '') {
                return ['ok' => false, 'reference' => ''];
            }
            return ['ok' => $this->verifyStripePayment($reference), 'reference' => $reference];
        }

        if ($gateway === 'paystack') {
            if ($reference === '') {
                return ['ok' => false, 'reference' => ''];
            }
            return ['ok' => $this->verifyPaystackPayment($reference), 'reference' => $reference];
        }

        if ($gateway === 'flutterwave') {
            return $this->verifyFlutterwavePayment($reference, $request);
        }

        return ['ok' => false, 'reference' => $reference];
    }

    private function verifyStripePayment(string $reference): bool
    {
        $secret = $this->resolveStripeSecret();
        if ($secret === '') {
            return app()->environment(['local', 'testing']);
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.stripe.com/v1/checkout/sessions/' . urlencode($reference));

            if (!$response->successful()) return false;

            $status = strtolower((string) $response->json('status', ''));
            $paymentStatus = strtolower((string) $response->json('payment_status', ''));

            return $status === 'complete' && $paymentStatus === 'paid';
        } catch (\Throwable $e) {
            Log::error('Stripe verify exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function verifyPaystackPayment(string $reference): bool
    {
        $secret = $this->resolvePaystackSecret();
        if ($secret === '') {
            return false;
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));

            if (!$response->successful() || !(bool) $response->json('status')) {
                return false;
            }

            return strtolower((string) data_get($response->json(), 'data.status', '')) === 'success';
        } catch (\Throwable $e) {
            Log::error('Paystack verify exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function verifyFlutterwavePayment(string $reference, ?Request $request = null): array
    {
        $secret = $this->resolveFlutterwaveSecret();
        if ($secret === '') {
            return ['ok' => false, 'reference' => $reference];
        }

        $transactionId = (string) ($request?->query('transaction_id') ?? $request?->input('transaction_id') ?? '');
        if ($transactionId === '') {
            return ['ok' => false, 'reference' => $reference];
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.flutterwave.com/v3/transactions/' . urlencode($transactionId) . '/verify');

            if (!$response->successful() || strtolower((string) $response->json('status')) !== 'success') {
                return ['ok' => false, 'reference' => $reference];
            }

            $paymentState = strtolower((string) data_get($response->json(), 'data.status', ''));
            $txRef = (string) data_get($response->json(), 'data.tx_ref', $reference);

            return [
                'ok' => $paymentState === 'successful',
                'reference' => $txRef !== '' ? $txRef : $reference,
            ];
        } catch (\Throwable $e) {
            Log::error('Flutterwave verify exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'reference' => $reference];
        }
    }

    private function resolveStripeSecret(): string
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            $secret = trim((string) env('STRIPE_SECRET_KEY', ''));
        }
        if ($this->isUsableStripeSecret($secret)) {
            return $secret;
        }

        $stored = trim((string) Setting::getSensitive('stripe_secret', ''));
        if ($this->isUsableStripeSecret($stored)) {
            return $stored;
        }

        return '';
    }

    private function resolveStripePublishableKey(): string
    {
        $key = trim((string) config('services.stripe.key'));
        if ($this->isUsableStripePublishableKey($key)) {
            return $key;
        }

        $key = trim((string) env('STRIPE_PUBLISHABLE_KEY', ''));
        if ($this->isUsableStripePublishableKey($key)) {
            return $key;
        }

        $stored = trim((string) Setting::getSensitive('stripe_key', ''));
        if ($this->isUsableStripePublishableKey($stored)) {
            return $stored;
        }

        return '';
    }

    private function resolvePaystackSecret(): string
    {
        $secret = trim((string) config('services.paystack.secretKey'));
        if ($this->isUsableGenericSecret($secret, 'sk_')) {
            return $secret;
        }

        $stored = trim((string) Setting::getSensitive('paystack_secret', Setting::get('paystack_secret', '')));
        if ($this->isUsableGenericSecret($stored, 'sk_')) {
            return $stored;
        }

        return '';
    }

    private function resolveFlutterwaveSecret(): string
    {
        $secret = trim((string) config('services.flutterwave.secret_key'));
        if ($this->isUsableGenericSecret($secret, 'FLWSECK_')) {
            return $secret;
        }

        $stored = trim((string) Setting::getSensitive('flutterwave_secret', Setting::get('flutterwave_secret', '')));
        if ($this->isUsableGenericSecret($stored, 'FLWSECK_')) {
            return $stored;
        }

        return '';
    }

    private function isUsableStripeSecret(string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        if (!str_starts_with($secret, 'sk_')) {
            return false;
        }

        if (str_contains(strtolower($secret), 'xxxx')) {
            return false;
        }

        return strlen($secret) >= 20;
    }

    private function isUsableStripePublishableKey(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        if (!str_starts_with($key, 'pk_')) {
            return false;
        }

        if (str_contains(strtolower($key), 'xxxx')) {
            return false;
        }

        return strlen($key) >= 20;
    }

    private function isUsableGenericSecret(string $value, string $prefix): bool
    {
        if ($value === '') {
            return false;
        }

        if (!str_starts_with($value, $prefix)) {
            return false;
        }

        if (str_contains(strtolower($value), 'xxxx')) {
            return false;
        }

        return strlen($value) >= 16;
    }

    public function approveTransfer(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);

        $subscription = Subscription::with(['user', 'company'])->findOrFail($id);
        if (strtolower((string) $subscription->payment_gateway) !== 'bank_transfer') {
            return back()->with('error', 'This subscription is not a bank transfer request.');
        }
        if (strtolower((string) $subscription->payment_status) !== 'pending_verification') {
            return back()->with('info', 'This transfer is already processed.');
        }

        DB::beginTransaction();
        try {
            $startDate = now();
            $endDate   = strtolower((string) $subscription->billing_cycle) === 'yearly'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            $managerId = $this->resolveDeploymentManagerId($subscription);
            $reference = (string) ($subscription->transfer_reference ?: $subscription->transaction_reference ?: ('BANK_TRANSFER_' . time()));

            $updates = [
                'status' => 'Active',
                'payment_status' => 'paid',
                'payment_date' => now(),
                'paid_at' => now(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'transaction_reference' => $reference,
                'transfer_validated_by' => auth()->id(),
                'transfer_validated_at' => now(),
                'transfer_validation_note' => trim((string) $request->input('note', 'Approved')),
            ];

            foreach (array_keys($updates) as $column) {
                if (!Schema::hasColumn('subscriptions', $column)) {
                    unset($updates[$column]);
                }
            }

            $subscription->update($updates);

            if ($subscription->company) {
                $subscription->company->update(['status' => 'active']);
            }

            if ($subscription->user) {
                $subscription->user->update([
                    'status' => 'active',
                    'is_verified' => 1,
                    'email_verified_at' => now(),
                    'company_id' => $subscription->company_id,
                ]);
            }

            $this->deployWorkspace($subscription);

            $this->recordDeploymentCommission($subscription, $managerId, $subscription->company_id);

            DB::commit();

            $this->sendWelcomeEmail($subscription->user, $subscription, $subscription->company);
            $this->sendTransferDecisionEmail($subscription, 'approved', (string) ($updates['transfer_validation_note'] ?? 'Approved'));

            return back()->with('success', 'Bank transfer approved and subscription activated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Approve bank transfer failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Unable to approve transfer right now.');
        }
    }

    public function rejectTransfer(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);

        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $subscription = Subscription::findOrFail($id);
        if (strtolower((string) $subscription->payment_gateway) !== 'bank_transfer') {
            return back()->with('error', 'This subscription is not a bank transfer request.');
        }

        $updates = [
            'status' => 'Pending',
            'payment_status' => 'failed',
            'transfer_validated_by' => auth()->id(),
            'transfer_validated_at' => now(),
            'transfer_validation_note' => trim((string) $request->input('note', 'Rejected')),
        ];

        foreach (array_keys($updates) as $column) {
            if (!Schema::hasColumn('subscriptions', $column)) {
                unset($updates[$column]);
            }
        }

        $subscription->update($updates);
        $this->sendTransferDecisionEmail($subscription, 'rejected', (string) ($updates['transfer_validation_note'] ?? 'Rejected'));

        return back()->with('warning', 'Bank transfer rejected. Customer can resubmit transfer details.');
    }

    public function suspendTransfer(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);

        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $subscription = Subscription::with(['user', 'company'])->findOrFail($id);
        if (strtolower((string) $subscription->payment_gateway) !== 'bank_transfer') {
            return back()->with('error', 'This subscription is not a bank transfer request.');
        }

        $updates = [
            'status' => 'Suspended',
            'transfer_validated_by' => auth()->id(),
            'transfer_validated_at' => now(),
            'transfer_validation_note' => trim((string) $request->input('note', 'Suspended by super admin')),
        ];

        if (strtolower((string) $subscription->payment_status) === 'pending_verification') {
            $updates['payment_status'] = 'failed';
        }

        foreach (array_keys($updates) as $column) {
            if (!Schema::hasColumn('subscriptions', $column)) {
                unset($updates[$column]);
            }
        }

        $subscription->update($updates);

        if ($subscription->company) {
            $subscription->company->update(['status' => 'suspended']);
        }

        if ($subscription->user) {
            $subscription->user->update([
                'status' => 'suspended',
                'is_verified' => 0,
            ]);
        }

        $this->sendTransferDecisionEmail($subscription, 'suspended', (string) ($updates['transfer_validation_note'] ?? 'Suspended'));

        return back()->with('warning', 'Transfer user has been suspended successfully.');
    }

    private function sendWelcomeEmail($user, $subscription, $company): void
    {
        if (!$user?->email) return;
        try {
            $domain = env('SESSION_DOMAIN', 'smatbook.com');
            $prefix = $company?->domain_prefix ?? $subscription->domain_prefix;
            $url    = $prefix ? 'https://' . $prefix . '.' . $domain : 'https://' . $domain;
            Mail::send('emails.welcome', [
                'userName'     => $user->name,
                'workspaceUrl' => $url,
                'planName'     => $subscription->plan_name ?? $subscription->plan,
            ], fn($m) => $m->to($user->email, $user->name)->subject('Your SmartProbook Workspace is Ready!'));
        } catch (\Exception $e) {
            Log::error('Welcome email failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendDeploymentWelcomeEmail($company, $user, $password): void
    {
        if (!$user?->email) return;
        try {
            $domain = env('SESSION_DOMAIN', 'smatbook.com');
            $prefix = $company?->domain_prefix;
            $url    = $prefix ? 'https://' . $prefix . '.' . $domain : 'https://' . $domain;
            Mail::send('emails.customer-welcome', [
                'email'        => $user->email,
                'password'     => $password,
                'name'         => $user->name,
                'workspaceUrl' => $url,
                'companyName'  => $company?->company_name ?? $company?->name,
            ], fn($m) => $m->to($user->email, $user->name)->subject('Your SmartProbook Login Credentials'));
        } catch (\Exception $e) {
            Log::error('Deployment welcome email failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendTransferDecisionEmail(Subscription $subscription, string $decision, string $note = ''): void
    {
        $user = $subscription->user;
        if (!$user?->email) {
            return;
        }

        $decision = strtolower(trim($decision));
        $decisionLabel = ucfirst($decision);
        $subject = "Bank Transfer {$decisionLabel}: " . ($subscription->plan_name ?? $subscription->plan ?? 'Subscription');

        $message = match ($decision) {
            'approved' => 'Your bank transfer has been approved and your subscription is now active.',
            'rejected' => 'Your bank transfer was rejected. Please review and resubmit your payment details.',
            'suspended' => 'Your account subscription has been suspended by the super admin.',
            default => 'Your bank transfer status was updated.',
        };

        $details = [
            'Name' => $user->name ?? 'User',
            'Plan' => $subscription->plan_name ?? $subscription->plan ?? 'N/A',
            'Amount' => '₦' . number_format((float) ($subscription->amount ?? 0), 2),
            'Status' => strtoupper((string) ($subscription->status ?? '')),
            'Payment Status' => strtoupper((string) ($subscription->payment_status ?? '')),
            'Reference' => (string) ($subscription->transfer_reference ?: $subscription->transaction_reference ?: 'N/A'),
            'Note' => $note !== '' ? $note : 'N/A',
        ];

        try {
            Mail::send('emails.system-event', [
                'title' => "Transfer {$decisionLabel}",
                'intro' => $message,
                'details' => $details,
            ], function ($m) use ($user, $subject) {
                $m->to($user->email, $user->name)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Transfer decision email failed', [
                'subscription_id' => $subscription->id,
                'decision' => $decision,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync subscription/company/domain so the workspace URL is immediately live after payment.
     */
    private function deployWorkspace(Subscription $subscription): void
    {
        $subscription->loadMissing(['company', 'user']);

        $prefix = strtolower(
            (string) (
                $subscription->domain_prefix
                ?? $subscription->company?->domain_prefix
                ?? $subscription->company?->subdomain
            )
        );

        if ($prefix === '') {
            return;
        }

        $domain = ltrim((string) env('SESSION_DOMAIN', 'smatbook.com'), '.');

        $subscriptionUpdates = [];
        if (!$subscription->domain_prefix) {
            $subscriptionUpdates['domain_prefix'] = $prefix;
        }
        if (Schema::hasColumn('subscriptions', 'initialized_at')) {
            $subscriptionUpdates['initialized_at'] = now();
        }
        if (!empty($subscriptionUpdates)) {
            $subscription->update($subscriptionUpdates);
        }

        if ($subscription->company) {
            $companyUpdates = [
                'domain_prefix' => $prefix,
                'subdomain'     => $prefix,
                'status'        => 'active',
            ];
            if (Schema::hasColumn('companies', 'domain')) {
                $companyUpdates['domain'] = $prefix . '.' . $domain;
            }
            $subscription->company->update($companyUpdates);
        }

        if (Schema::hasTable('domains')) {
            $lookup = [];
            if (Schema::hasColumn('domains', 'subscription_id')) {
                $lookup['subscription_id'] = $subscription->id;
            } elseif (Schema::hasColumn('domains', 'tenant_id')) {
                $lookup['tenant_id'] = $subscription->user_id;
            } else {
                $lookup['domain_name'] = $prefix;
            }

            $domainPayload = [];
            if (Schema::hasColumn('domains', 'tenant_id')) {
                $domainPayload['tenant_id'] = $subscription->user_id;
            }
            if (Schema::hasColumn('domains', 'subscription_id')) {
                $domainPayload['subscription_id'] = $subscription->id;
            }
            if (Schema::hasColumn('domains', 'customer_name')) {
                $domainPayload['customer_name'] = $subscription->user?->name ?? $subscription->company?->name;
            }
            if (Schema::hasColumn('domains', 'email')) {
                $domainPayload['email'] = $subscription->user?->email ?? $subscription->company?->email;
            }
            if (Schema::hasColumn('domains', 'domain_name')) {
                $domainPayload['domain_name'] = $prefix;
            }
            if (Schema::hasColumn('domains', 'package_name')) {
                $domainPayload['package_name'] = $subscription->plan_name ?? $subscription->plan;
            }
            if (Schema::hasColumn('domains', 'package_type')) {
                $domainPayload['package_type'] = strtolower((string) $subscription->billing_cycle);
            }
            if (Schema::hasColumn('domains', 'price')) {
                $domainPayload['price'] = $subscription->amount;
            }
            if (Schema::hasColumn('domains', 'status')) {
                $domainPayload['status'] = 'Active';
            }
            if (Schema::hasColumn('domains', 'approved_at')) {
                $domainPayload['approved_at'] = now();
            }
            if (Schema::hasColumn('domains', 'setup_completed_at')) {
                $domainPayload['setup_completed_at'] = now();
            }

            Domain::updateOrCreate($lookup, $domainPayload);
        }
    }
}
