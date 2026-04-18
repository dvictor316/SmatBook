<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\EmailAuditLog;
use App\Models\Bank;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private function deleteManagedSettingFile(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        if (str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(ltrim(substr($path, 8), '/'));
            return;
        }

        if (File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    /**
     * Helper to fetch all key-value pairs
     */
    private function getSettings()
    {
        return new \ArrayObject(
            Setting::pluck('value', 'key')->all(),
            \ArrayObject::ARRAY_AS_PROPS
        );
    }

    private function getJsonSettingArray(string $key, array $fallback = []): array
    {
        $raw = Setting::where('key', $key)->value('value');
        if (empty($raw)) {
            return $fallback;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $fallback;
    }

    private function setJsonSettingArray(string $key, array $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => json_encode(array_values($value))]);
    }

    private function companyScopedSettingKey(string $baseKey): string
    {
        $companyId = (int) (Auth::user()?->company_id ?? optional(Auth::user()?->company)->id ?? 0);

        return $companyId > 0 ? "{$baseKey}_company_{$companyId}" : $baseKey;
    }

    private function getCompanyScopedJsonSettingArray(string $baseKey, array $fallback = []): array
    {
        return $this->getJsonSettingArray($this->companyScopedSettingKey($baseKey), $fallback);
    }

    private function setCompanyScopedJsonSettingArray(string $baseKey, array $value): void
    {
        $this->setJsonSettingArray($this->companyScopedSettingKey($baseKey), $value);
    }

    /**
     * Display Main Settings
     * Matches: resources/views/Settings/setting.blade.php
     */
    public function index()
    {
        $settings = $this->getSettings();
        $emailLogs = collect();

        if (Schema::hasTable('email_audit_logs')) {
            $query = EmailAuditLog::query();
            $tenantId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
            $branchId = session('active_branch_id');

            if ($tenantId > 0) {
                if (Schema::hasColumn('email_audit_logs', 'company_id')) {
                    // Preferred: filter by company_id column (after migration)
                    $query->where('company_id', $tenantId);
                } else {
                    // Fallback: filter by recipient matching this company's users
                    $companyEmails = \App\Models\User::where('company_id', $tenantId)
                        ->pluck('email')
                        ->all();
                    if (empty($companyEmails)) {
                        $emailLogs = collect();
                        goto renderSettings;
                    }
                    $query->whereIn('recipient', $companyEmails);
                }
            } else {
                // No tenant identified — show nothing
                $emailLogs = collect();
                goto renderSettings;
            }

            if ($branchId && Schema::hasColumn('email_audit_logs', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $emailLogs = $query->latest()->limit(15)->get();
        }
        renderSettings:

        return view('Settings.settings', compact('settings', 'emailLogs'));
    }

    /**
     * Unified Update Method
     */
    public function update(Request $request)
    {
        // 1. Validation
        $request->validate([
            'site_logo'    => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'favicon'      => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:1024',
            'company_icon' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:1024',
            'invoice_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'digital_signature' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'seo_meta_image' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'email'        => 'nullable|email',
            'company_email' => 'nullable|email',
            'mail_from_address' => 'nullable|email',
        ]);

        // 2. Dynamic File Upload Handler
        $fileFields = ['site_logo', 'favicon', 'company_icon', 'invoice_logo', 'digital_signature', 'seo_meta_image'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file
                $oldFile = Setting::where('key', $field)->first();
                if ($oldFile && $oldFile->value) {
                    $this->deleteManagedSettingFile($oldFile->value);
                }

                // Save new file
                $file = $request->file($field);
                $name = $field . '_' . time() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('settings', $file, $name);
                
                Setting::updateOrCreate(['key' => $field], ['value' => 'storage/settings/' . $name]);
            }
        }

        // 2b. Normalize toggle/checkbox fields so unchecked states persist as "0".
        $booleanFields = [
            'mail_php_enabled',
            'mail_smtp_enabled',
            'payment_stripe_enabled',
            'payment_paystack_enabled',
            'payment_flutterwave_enabled',
            'payment_paypal_enabled',
            'payment_razorpay_enabled',
            'saas_email_verification',
            'saas_auto_approve_domain',
            'two_factor_sms_enabled',
            'tax_rate_1_enabled',
            'tax_rate_2_enabled',
            'tax_rate_3_enabled',
            'tax_rate_4_enabled',
            'tax_rate_5_enabled',
        ];

        foreach ($booleanFields as $field) {
            if (!$request->has($field)) {
                Setting::updateOrCreate(['key' => $field], ['value' => '0']);
            }
        }

        // 3. Sensitive fields are encrypted and never echoed back in plain text.
        $sensitiveFields = [
            'stripe_key',
            'stripe_secret',
            'paystack_key',
            'paystack_secret',
            'flutterwave_key',
            'flutterwave_secret',
            'paypal_secret',
            'razorpay_secret',
            'mail_smtp_password',
        ];

        foreach ($sensitiveFields as $sensitiveField) {
            if ($request->has($sensitiveField)) {
                Setting::putSensitive($sensitiveField, (string) $request->input($sensitiveField, ''));
            }
        }

        // 4. Dynamic Text Field Handler
        $inputs = $request->except(array_merge(['_token'], $fileFields));
        foreach ($sensitiveFields as $sensitiveField) {
            unset($inputs[$sensitiveField]);
        }

        foreach ($inputs as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    // --- Sub-page View Methods ---
    // Paths updated to match resources/views/Settings/ folder

    public function bank_account()
    {
        $bankAccounts = collect();
        if (Schema::hasTable('banks')) {
            $bankAccounts = Bank::query()->latest()->get();
        }

        return view('Settings.bank-account', [
            'settings' => $this->getSettings(),
            'bankAccounts' => $bankAccounts,
        ]);
    }
    public function company_settings() { return view('Settings.company-settings', ['settings' => $this->getSettings()]); }
    public function email_settings()   { return view('Settings.email-settings', ['settings' => $this->getSettings()]); }
    public function invoice_settings() { return view('Settings.invoice-settings', ['settings' => $this->getSettings()]); }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['mail_test_address' => 'required|email']);
        $recipient = $request->input('mail_test_address');

        try {
            \App\Support\AppMailer::sendView('emails.system-event', [
                'title'   => 'Test Email',
                'intro'   => 'Your email configuration is working correctly.',
                'details' => [
                    'Sent To'  => $recipient,
                    'Sent At'  => now()->toDateTimeString(),
                    'Mailer'   => \App\Support\AppMailer::preferredMailer(),
                ],
            ], function ($message) use ($recipient) {
                $message->from(\App\Models\Setting::mailFromAddress(), \App\Models\Setting::mailFromName())
                    ->to($recipient)
                    ->subject('Test Email — ' . config('app.name'));
            });

            return response()->json(['success' => true, 'message' => "Test email sent to {$recipient} successfully."]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }
    public function payment_settings()
    {
        $settings = $this->getSettings();
        $secretFlags = [
            'has_stripe_key' => (string) Setting::get('stripe_key', '') !== '',
            'has_stripe_secret' => (string) Setting::get('stripe_secret', '') !== '',
            'has_paystack_key' => (string) Setting::get('paystack_key', '') !== '',
            'has_paystack_secret' => (string) Setting::get('paystack_secret', '') !== '',
            'has_flutterwave_key' => (string) Setting::get('flutterwave_key', '') !== '',
            'has_flutterwave_secret' => (string) Setting::get('flutterwave_secret', '') !== '',
            'has_paypal_secret' => (string) Setting::get('paypal_secret', '') !== '',
            'has_razorpay_secret' => (string) Setting::get('razorpay_secret', '') !== '',
        ];

        $envStripeSecret = trim((string) config('services.stripe.secret'));
        $dbStripeSecret = trim((string) Setting::getSensitive('stripe_secret', ''));

        $stripeSecret = $this->isUsableStripeSecret($envStripeSecret)
            ? $envStripeSecret
            : $dbStripeSecret;

        $stripeSource = $this->isUsableStripeSecret($envStripeSecret)
            ? '.env'
            : ($this->isUsableStripeSecret($dbStripeSecret) ? 'settings' : 'none');

        $stripeStatus = [
            'enabled' => !empty($settings['payment_stripe_enabled']),
            'configured' => $this->isUsableStripeSecret($stripeSecret),
            'source' => $stripeSource,
            'mode' => str_starts_with($stripeSecret, 'sk_live_') ? 'live' : (str_starts_with($stripeSecret, 'sk_test_') ? 'test' : 'unknown'),
        ];

        $envPaystackSecret = trim((string) config('services.paystack.secretKey'));
        $dbPaystackSecret = trim((string) Setting::getSensitive('paystack_secret', ''));
        $paystackSecret = $this->isUsableGenericKey($envPaystackSecret, 'sk_')
            ? $envPaystackSecret
            : $dbPaystackSecret;
        $paystackSource = $this->isUsableGenericKey($envPaystackSecret, 'sk_')
            ? '.env'
            : ($this->isUsableGenericKey($dbPaystackSecret, 'sk_') ? 'settings' : 'none');
        $paystackStatus = [
            'enabled' => !empty($settings['payment_paystack_enabled']),
            'configured' => $this->isUsableGenericKey($paystackSecret, 'sk_'),
            'source' => $paystackSource,
            'mode' => str_contains($paystackSecret, '_live_') ? 'live' : (str_contains($paystackSecret, '_test_') ? 'test' : 'unknown'),
        ];

        $envFlutterwaveSecret = trim((string) config('services.flutterwave.secret_key'));
        $dbFlutterwaveSecret = trim((string) Setting::getSensitive('flutterwave_secret', ''));
        $flutterwaveSecret = $this->isUsableGenericKey($envFlutterwaveSecret, 'FLWSECK_')
            ? $envFlutterwaveSecret
            : $dbFlutterwaveSecret;
        $flutterwaveSource = $this->isUsableGenericKey($envFlutterwaveSecret, 'FLWSECK_')
            ? '.env'
            : ($this->isUsableGenericKey($dbFlutterwaveSecret, 'FLWSECK_') ? 'settings' : 'none');
        $flutterwaveStatus = [
            'enabled' => !empty($settings['payment_flutterwave_enabled']),
            'configured' => $this->isUsableGenericKey($flutterwaveSecret, 'FLWSECK_'),
            'source' => $flutterwaveSource,
            'mode' => str_contains($flutterwaveSecret, '_LIVE-') ? 'live' : (str_contains($flutterwaveSecret, '_TEST-') ? 'test' : 'unknown'),
        ];

        return view('Settings.payment-settings', compact('settings', 'secretFlags', 'stripeStatus', 'paystackStatus', 'flutterwaveStatus'));
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

    private function isUsableGenericKey(string $value, string $prefix): bool
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
    public function plan_billing()
    {
        $user = Auth::user();
        $currentSubscription = null;
        $billingHistory = collect();

        if (Schema::hasTable('subscriptions')) {
            $currentSubscription = Subscription::query()
                ->where(function ($q) use ($user) {
                    if (!empty($user?->company_id) && Schema::hasColumn('subscriptions', 'company_id')) {
                        $q->where('company_id', $user->company_id);
                    }
                    $q->orWhere('user_id', $user?->id);
                })
                ->orderByDesc('id')
                ->first();

            $billingHistory = Subscription::query()
                ->where(function ($q) use ($user) {
                    if (!empty($user?->company_id) && Schema::hasColumn('subscriptions', 'company_id')) {
                        $q->where('company_id', $user->company_id);
                    }
                    $q->orWhere('user_id', $user?->id);
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        return view('Settings.plan-billing', [
            'settings' => $this->getSettings(),
            'currentSubscription' => $currentSubscription,
            'billingHistory' => $billingHistory,
        ]);
    }

    public function preferences()     { return view('Settings.preferences', ['settings' => $this->getSettings()]); }
    public function chart_of_accounts()
    {
        $settings = $this->getSettings();
        $accounts = collect();
        $accountGroups = collect();
        $accountSummary = collect();

        if (Schema::hasTable('accounts')) {
            $accounts = Account::query()
                ->when(Schema::hasTable('transactions'), fn ($query) => $query->withCount('transactions'))
                ->orderByRaw("FIELD(type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense')")
                ->orderBy('code')
                ->get()
                ->map(function (Account $account) {
                    if (is_null($account->current_balance) && Schema::hasTable('transactions')) {
                        $account->current_balance = $account->calculateBalance();
                    }

                    return $account;
                });

            $accountGroups = $accounts->groupBy('type');

            $typeOrder = [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE,
            ];

            $accountSummary = collect($typeOrder)->map(function ($type) use ($accountGroups) {
                $group = $accountGroups->get($type, collect());

                return [
                    'type' => $type,
                    'count' => $group->count(),
                    'balance' => (float) $group->sum(fn ($account) => (float) ($account->current_balance ?? $account->opening_balance ?? 0)),
                ];
            });
        }

        return view('Settings.chart-of-accounts', compact('settings', 'accounts', 'accountGroups', 'accountSummary'));
    }
    public function branches()
    {
        $settings = $this->getSettings();
        $branches = collect($this->getCompanyScopedJsonSettingArray('branches_json'))
            ->map(function ($branch) {
                return [
                    'id' => (string) ($branch['id'] ?? ''),
                    'name' => (string) ($branch['name'] ?? ''),
                    'code' => (string) ($branch['code'] ?? ''),
                    'manager' => (string) ($branch['manager'] ?? ''),
                    'phone' => (string) ($branch['phone'] ?? ''),
                    'address' => (string) ($branch['address'] ?? ''),
                    'is_active' => (bool) ($branch['is_active'] ?? true),
                ];
            })
            ->filter(fn ($branch) => $branch['id'] !== '' && $branch['name'] !== '')
            ->values();

        $activeBranchId = (string) session('active_branch_id', '');
        $activeBranch = $branches->firstWhere('id', $activeBranchId) ?: $branches->first();

        if ($activeBranch && $activeBranchId === '') {
            session([
                'active_branch_id' => $activeBranch['id'],
                'active_branch_name' => $activeBranch['name'],
            ]);
        }

        return view('Settings.branches', compact('settings', 'branches', 'activeBranch'));
    }
    public function bank_reconciliation()
    {
        $settings = $this->getSettings();
        $banks = collect();
        $reconciliations = collect();
        $summary = [
            'bank_count' => 0,
            'matched_count' => 0,
            'mismatch_count' => 0,
            'difference_total' => 0.0,
        ];

        if (Schema::hasTable('banks')) {
            $banks = Bank::query()->orderBy('name')->get();
        }

        $accounts = collect();
        if (Schema::hasTable('accounts')) {
            $accounts = Account::query()
                ->where('is_active', true)
                ->where('type', Account::TYPE_ASSET)
                ->orderBy('name')
                ->get();
        }

        if ($banks->isNotEmpty()) {
            $reconciliations = $banks->map(function (Bank $bank) use ($accounts) {
                $account = $accounts->first(function (Account $account) use ($bank) {
                    $bankName = strtolower(trim((string) $bank->name));
                    $accountName = strtolower(trim((string) $account->name));

                    return $bankName !== '' && (
                        $accountName === $bankName
                        || str_contains($accountName, $bankName)
                        || str_contains($bankName, $accountName)
                    );
                });

                if (!$account) {
                    $account = $accounts->first(function (Account $account) {
                        $name = strtolower((string) $account->name);
                        return str_contains($name, 'bank') || str_contains($name, 'cash');
                    });
                }

                $bookBalance = $account
                    ? (float) ($account->current_balance ?? $account->calculateBalance())
                    : 0.0;
                $bankBalance = (float) ($bank->balance ?? 0);
                $difference = round($bankBalance - $bookBalance, 2);

                $lastTransaction = null;
                if ($account && Schema::hasTable('transactions')) {
                    $lastTransaction = Transaction::query()
                        ->where('account_id', $account->id)
                        ->latest('transaction_date')
                        ->value('transaction_date');
                }

                return [
                    'bank' => $bank,
                    'account' => $account,
                    'bank_balance' => $bankBalance,
                    'book_balance' => $bookBalance,
                    'difference' => $difference,
                    'is_matched' => $account !== null,
                    'is_balanced' => abs($difference) < 0.01,
                    'last_transaction_date' => $lastTransaction,
                ];
            });

            $summary = [
                'bank_count' => $reconciliations->count(),
                'matched_count' => $reconciliations->where('is_matched', true)->count(),
                'mismatch_count' => $reconciliations->reject(fn ($item) => $item['is_balanced'])->count(),
                'difference_total' => (float) $reconciliations->sum('difference'),
            ];
        }

        return view('Settings.bank-reconciliation', compact('settings', 'banks', 'reconciliations', 'summary'));
    }
    public function manual_journal()
    {
        $settings = $this->getSettings();
        $accounts = collect();
        $recentJournalGroups = collect();

        if (Schema::hasTable('accounts')) {
            $accounts = Account::query()
                ->where('is_active', true)
                ->orderByRaw("FIELD(type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense')")
                ->orderBy('code')
                ->get();
        }

        if (Schema::hasTable('transactions')) {
            $recentJournalGroups = Transaction::query()
                ->with('account')
                ->where('transaction_type', Transaction::TYPE_JOURNAL)
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->limit(60)
                ->get()
                ->groupBy(fn ($entry) => $entry->reference ?: 'JRNL-' . $entry->id)
                ->take(12);
        }

        return view('Settings.manual-journal', compact('settings', 'accounts', 'recentJournalGroups'));
    }
    public function tax_rates()
    {
        $defaultTaxes = [
            ['Id' => 1, 'Name' => 'VAT', 'TaxRate' => '7.5%', 'Status' => 'Enabled', 'StatusId' => 'tax_rate_1'],
            ['Id' => 2, 'Name' => 'Sales Tax', 'TaxRate' => '5%', 'Status' => 'Enabled', 'StatusId' => 'tax_rate_2'],
            ['Id' => 3, 'Name' => 'Service Tax', 'TaxRate' => '10%', 'Status' => 'Enabled', 'StatusId' => 'tax_rate_3'],
        ];

        $taxes = $this->getJsonSettingArray('tax_rates_json', $defaultTaxes);

        return view('Settings.tax-rates', [
            'settings' => $this->getSettings(),
            'taxes' => $taxes,
        ]);
    }
    public function template_invoice() { return view('Settings.template-invoice', ['settings' => $this->getSettings()]); }
    public function two_factor()      { return view('Settings.two-factor', ['settings' => $this->getSettings()]); }
    public function custom_filed()
    {
        $customFields = collect($this->getJsonSettingArray('custom_fields_json'));

        return view('Settings.custom-filed', [
            'settings' => $this->getSettings(),
            'customFields' => $customFields,
        ]);
    }
    public function emailtemplate()
    {
        $defaultTemplates = [
            ['id' => 1, 'title' => 'Email Verification', 'subject' => 'Verify your email address', 'content' => 'Welcome! Please verify your email address.'],
            ['id' => 2, 'title' => 'Welcome Email', 'subject' => 'Welcome to our platform', 'content' => 'Your account has been created successfully.'],
        ];
        $emailTemplates = $this->getJsonSettingArray('email_templates_json', $defaultTemplates);

        return view('Settings.email-template', [
            'settings' => $this->getSettings(),
            'emailTemplates' => $emailTemplates,
        ]);
    }
    public function seosettings()     { return view('Settings.seo-settings', ['settings' => $this->getSettings()]); }
    public function saassettings()    { return view('Settings.saas-settings', ['settings' => $this->getSettings()]); }

    public function ledger_backfill(Request $request)
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));
        if (!in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true)) {
            return redirect()->back()->with('error', 'Unauthorized: only super admin can run ledger backfill.');
        }

        try {
            $chunk = (int) $request->input('chunk', 100);
            $chunk = max(50, min(1000, $chunk));

            Artisan::call('ledger:backfill-operations', [
                '--chunk' => $chunk,
            ]);

            $output = trim((string) Artisan::output());
            $message = $output !== '' ? $output : 'Ledger backfill completed.';

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Ledger backfill failed: ' . $e->getMessage());
        }
    }

    public function storeChartAccount(Request $request)
    {
        if (!Schema::hasTable('accounts')) {
            return redirect()->back()->with('error', 'Accounts table is not available in this installation.');
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts', 'code'),
            ],
            'name' => 'required|string|max:191',
            'type' => ['required', Rule::in([
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE,
            ])],
            'sub_type' => 'nullable|string|max:191',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        Account::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'sub_type' => $validated['sub_type'] ?? null,
            'opening_balance' => (float) ($validated['opening_balance'] ?? 0),
            'current_balance' => (float) ($validated['opening_balance'] ?? 0),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('chart-of-accounts')->with('success', 'Account added to chart of accounts.');
    }

    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:50',
            'manager' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $branches = collect($this->getCompanyScopedJsonSettingArray('branches_json'));

        $currentPlan = strtolower((string) session('user_plan'));
        if ($currentPlan === '' && Auth::check() && Schema::hasTable('subscriptions')) {
            $subscription = Subscription::resolveCurrentForUser(Auth::user())
                ?? Subscription::where('user_id', Auth::id())->latest()->first();
            $currentPlan = strtolower((string) ($subscription?->plan ?? $subscription?->plan_name ?? ''));
        }

        if (str_contains($currentPlan, 'basic') && $branches->count() >= 1) {
            return redirect()->back()->withInput()->with('error', 'Basic plan supports a single branch only.');
        }

        $name = trim((string) $validated['name']);
        $duplicateByName = $branches->first(function ($branch) use ($name) {
            return (string) ($branch['name'] ?? '') === $name;
        });
        if ($duplicateByName) {
            return redirect()->back()->withInput()->with('error', 'A branch with this name already exists.');
        }

        $code = strtoupper(trim((string) ($validated['code'] ?? '')));
        if ($code === '') {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 4));
            $code = $code !== '' ? $code . '-' . now()->format('Hi') : 'BR-' . now()->format('Hi');
        }

        $branches->push([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'code' => $code,
            'manager' => $validated['manager'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'address' => $validated['address'] ?? '',
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->setCompanyScopedJsonSettingArray('branches_json', $branches->all());

        return redirect()->route('branches.index')->with('success', 'Branch added successfully.');
    }

    public function updateBranch(Request $request, string $branchId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:50',
            'manager' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $branchesCollection = collect($this->getCompanyScopedJsonSettingArray('branches_json'));
        $name = trim((string) $validated['name']);
        $duplicateByName = $branchesCollection->first(function ($branch) use ($branchId, $name) {
            if ((string) ($branch['id'] ?? '') === $branchId) {
                return false;
            }
            return (string) ($branch['name'] ?? '') === $name;
        });
        if ($duplicateByName) {
            return redirect()->back()->withInput()->with('error', 'A branch with this name already exists.');
        }

        $branches = $branchesCollection->map(function ($branch) use ($branchId, $validated, $name) {
            if ((string) ($branch['id'] ?? '') !== $branchId) {
                return $branch;
            }

            $branch['name'] = $name;
            $branch['code'] = strtoupper(trim((string) ($validated['code'] ?? $branch['code'] ?? '')));
            $branch['manager'] = $validated['manager'] ?? '';
            $branch['phone'] = $validated['phone'] ?? '';
            $branch['address'] = $validated['address'] ?? '';
            $branch['is_active'] = (bool) ($validated['is_active'] ?? true);

            return $branch;
        })->values();

        $this->setCompanyScopedJsonSettingArray('branches_json', $branches->all());

        if (session('active_branch_id') === $branchId) {
            $active = $branches->firstWhere('id', $branchId);
            session(['active_branch_name' => $active['name'] ?? '']);
        }

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroyBranch(string $branchId)
    {
        $branches = collect($this->getCompanyScopedJsonSettingArray('branches_json'))
            ->reject(fn ($branch) => (string) ($branch['id'] ?? '') === $branchId)
            ->values();

        $this->setCompanyScopedJsonSettingArray('branches_json', $branches->all());

        if (session('active_branch_id') === $branchId) {
            $nextBranch = $branches->first();
            session([
                'active_branch_id' => $nextBranch['id'] ?? null,
                'active_branch_name' => $nextBranch['name'] ?? null,
            ]);
        }

        return redirect()->route('branches.index')->with('success', 'Branch removed successfully.');
    }

    public function activateBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|string',
            'redirect_to' => 'nullable|string',
        ]);

        $branches = collect($this->getCompanyScopedJsonSettingArray('branches_json'));
        $branch = $branches->firstWhere('id', $validated['branch_id']);

        if (!$branch) {
            return redirect()->back()->with('error', 'Selected branch could not be found.');
        }

        session([
            'active_branch_id' => $branch['id'],
            'active_branch_name' => $branch['name'],
        ]);

        $redirectUrl = $request->input('redirect_to') ?: url()->previous();
        if ($redirectUrl && str_starts_with($redirectUrl, url('/'))) {
            // Check if the URL is for a resource that might not exist in the new branch
            if (str_contains($redirectUrl, '/customers/')) {
                return redirect()->route('customers.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/sales/') || str_contains($redirectUrl, '/invoices/')) {
                return redirect()->route('sales.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/suppliers/')) {
                return redirect()->route('suppliers.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/purchases/')) {
                return redirect()->route('purchases.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/products/') || str_contains($redirectUrl, '/inventory/')) {
                return redirect()->route('inventory.Products')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/expenses/')) {
                return redirect()->route('expenses.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/estimates/')) {
                return redirect()->route('estimates.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/payments/')) {
                return redirect()->route('payments.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            } elseif (str_contains($redirectUrl, '/categories/')) {
                return redirect()->route('categories.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
            }
            return redirect()->to($redirectUrl)->with('success', 'Active branch changed to ' . $branch['name'] . '.');
        }

        // Fallback to branches index if redirect_to is not safe
        return redirect()->route('branches.index')->with('success', 'Active branch changed to ' . $branch['name'] . '.');
    }

    public function storeManualJournal(Request $request)
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return redirect()->back()->with('error', 'Manual journal requires accounts and transactions tables.');
        }

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:255',
        ]);

        $lines = collect($validated['lines'])
            ->map(function ($line) {
                return [
                    'account_id' => (int) ($line['account_id'] ?? 0),
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'memo' => trim((string) ($line['memo'] ?? '')),
                ];
            })
            ->filter(fn ($line) => $line['account_id'] && ($line['debit'] > 0 || $line['credit'] > 0))
            ->values();

        if ($lines->count() < 2) {
            return redirect()->back()->withInput()->with('error', 'Add at least two journal lines with values.');
        }

        $invalidLine = $lines->first(fn ($line) => $line['debit'] > 0 && $line['credit'] > 0);
        if ($invalidLine) {
            return redirect()->back()->withInput()->with('error', 'Each journal line must be either debit or credit, not both.');
        }

        $totalDebit = round((float) $lines->sum('debit'), 2);
        $totalCredit = round((float) $lines->sum('credit'), 2);

        if ($totalDebit <= 0 || $totalCredit <= 0) {
            return redirect()->back()->withInput()->with('error', 'Journal entry must contain both debit and credit lines.');
        }

        if (abs($totalDebit - $totalCredit) > 0.009) {
            return redirect()->back()->withInput()->with('error', 'Debits and credits must balance before posting.');
        }

        $reference = trim((string) ($validated['reference'] ?? ''));
        if ($reference === '') {
            $reference = 'JRNL-' . now()->format('Ymd-His');
        }

        DB::transaction(function () use ($validated, $lines, $reference, $request) {
            foreach ($lines as $line) {
                Transaction::create([
                    'account_id' => $line['account_id'],
                    'transaction_date' => $validated['transaction_date'],
                    'reference' => $reference,
                    'description' => $line['memo'] ?: ($validated['description'] ?? 'Manual journal entry'),
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'balance' => 0,
                    'transaction_type' => Transaction::TYPE_JOURNAL,
                    'related_id' => null,
                    'related_type' => null,
                    'user_id' => $request->user()?->id,
                ]);
            }
        });

        return redirect()->route('manual-journal')->with('success', 'Manual journal entry posted successfully.');
    }

    public function storeBankReconciliationAdjustment(Request $request)
    {
        if (!Schema::hasTable('banks') || !Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return redirect()->back()->with('error', 'Bank reconciliation requires banks, accounts, and transactions tables.');
        }

        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'account_id' => 'required|exists:accounts,id',
            'difference' => 'required|numeric',
            'transaction_date' => 'required|date',
            'memo' => 'nullable|string|max:255',
        ]);

        $difference = round((float) $validated['difference'], 2);
        if (abs($difference) < 0.01) {
            return redirect()->back()->with('success', 'This bank is already fully reconciled.');
        }

        $bank = Bank::query()->findOrFail($validated['bank_id']);
        $bankAccount = Account::query()->findOrFail($validated['account_id']);
        $suspenseAccount = $this->resolveReconciliationSuspenseAccount();

        $reference = 'BREC-' . now()->format('Ymd-His') . '-' . $bank->id;
        $memo = trim((string) ($validated['memo'] ?? ''));
        $description = $memo !== ''
            ? $memo
            : 'Bank reconciliation adjustment for ' . ($bank->name ?: 'Bank Account');

        DB::transaction(function () use ($validated, $difference, $bankAccount, $suspenseAccount, $reference, $description, $request) {
            $debitToBank = $difference > 0 ? abs($difference) : 0;
            $creditToBank = $difference < 0 ? abs($difference) : 0;

            Transaction::create([
                'account_id' => $bankAccount->id,
                'transaction_date' => $validated['transaction_date'],
                'reference' => $reference,
                'description' => $description,
                'debit' => $debitToBank,
                'credit' => $creditToBank,
                'balance' => 0,
                'transaction_type' => Transaction::TYPE_ADJUSTMENT,
                'related_id' => null,
                'related_type' => null,
                'user_id' => $request->user()?->id,
            ]);

            Transaction::create([
                'account_id' => $suspenseAccount->id,
                'transaction_date' => $validated['transaction_date'],
                'reference' => $reference,
                'description' => $description,
                'debit' => $creditToBank,
                'credit' => $debitToBank,
                'balance' => 0,
                'transaction_type' => Transaction::TYPE_ADJUSTMENT,
                'related_id' => null,
                'related_type' => null,
                'user_id' => $request->user()?->id,
            ]);
        });

        return redirect()->route('bank-reconciliation')->with('success', 'Reconciliation adjustment posted successfully.');
    }

    private function resolveReconciliationSuspenseAccount(): Account
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);

        // Use a tenant-specific code so multiple tenants don't collide on the unique constraint
        $code = $companyId > 0
            ? 'EQT-RECON-SUSPENSE-' . $companyId
            : 'EQT-RECON-SUSPENSE';

        $attributes = ['code' => $code];
        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $attributes['company_id'] = $companyId;
        }

        return Account::query()->firstOrCreate(
            $attributes,
            [
                'name' => 'Bank Reconciliation Suspense',
                'type' => Account::TYPE_EQUITY,
                'sub_type' => 'Reconciliation Reserve',
                'description' => 'Temporary balancing account used for bank reconciliation adjustments.',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    public function storeBankAccount(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:191',
            'account_number' => 'required|string|max:100|unique:banks,account_number',
            'account_holder_name' => 'nullable|string|max:191',
            'branch' => 'nullable|string|max:191',
            'ifsc_code' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $payload = [
            'name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'branch' => $validated['branch'] ?? null,
            'balance' => (float) ($validated['opening_balance'] ?? 0),
        ];

        if (Schema::hasColumn('banks', 'account_holder_name')) {
            $payload['account_holder_name'] = $validated['account_holder_name'] ?? null;
        }
        if (Schema::hasColumn('banks', 'ifsc_code')) {
            $payload['ifsc_code'] = $validated['ifsc_code'] ?? null;
        }
        if (Schema::hasColumn('banks', 'swift_code')) {
            $payload['swift_code'] = $validated['ifsc_code'] ?? null;
        }
        if (Schema::hasColumn('banks', 'company_id')) {
            $payload['company_id'] = $request->user()?->company_id;
        }
        if (Schema::hasColumn('banks', 'user_id')) {
            $payload['user_id'] = $request->user()?->id;
        }
        if (Schema::hasColumn('banks', 'branch_id')) {
            $payload['branch_id'] = session('active_branch_id');
        }
        if (Schema::hasColumn('banks', 'branch_name')) {
            $payload['branch_name'] = session('active_branch_name');
        }

        Bank::create($payload);

        return redirect()->route('bank-account')->with('success', 'Bank account added successfully.');
    }

    public function updateBankAccount(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:191',
            'account_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('banks', 'account_number')->ignore($bank->id),
            ],
            'account_holder_name' => 'nullable|string|max:191',
            'branch' => 'nullable|string|max:191',
            'ifsc_code' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $payload = [
            'name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'branch' => $validated['branch'] ?? null,
            'balance' => (float) ($validated['opening_balance'] ?? 0),
        ];

        if (Schema::hasColumn('banks', 'account_holder_name')) {
            $payload['account_holder_name'] = $validated['account_holder_name'] ?? null;
        }
        if (Schema::hasColumn('banks', 'ifsc_code')) {
            $payload['ifsc_code'] = $validated['ifsc_code'] ?? null;
        }
        if (Schema::hasColumn('banks', 'swift_code')) {
            $payload['swift_code'] = $validated['ifsc_code'] ?? null;
        }

        $bank->update($payload);

        return redirect()->route('bank-account')->with('success', 'Bank account updated successfully.');
    }

    public function destroyBankAccount(Bank $bank)
    {
        $bank->delete();

        return redirect()->route('bank-account')->with('success', 'Bank account deleted successfully.');
    }

    public function storeTaxRate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'rate' => 'required|string|max:50',
        ]);

        $taxes = $this->getJsonSettingArray('tax_rates_json');
        $maxId = collect($taxes)->max(fn ($item) => (int) ($item['Id'] ?? 0));
        $nextId = $maxId + 1;

        $taxes[] = [
            'Id' => $nextId,
            'Name' => $validated['name'],
            'TaxRate' => $validated['rate'],
            'Status' => 'Enabled',
            'StatusId' => 'tax_rate_' . $nextId,
        ];

        $this->setJsonSettingArray('tax_rates_json', $taxes);

        return redirect()->route('tax-rates')->with('success', 'Tax rate added successfully.');
    }

    public function updateTaxRate(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'rate' => 'required|string|max:50',
        ]);

        $taxes = $this->getJsonSettingArray('tax_rates_json');
        $taxes = collect($taxes)->map(function ($item) use ($id, $validated) {
            if ((int) ($item['Id'] ?? 0) === $id) {
                $item['Name'] = $validated['name'];
                $item['TaxRate'] = $validated['rate'];
            }
            return $item;
        })->values()->all();

        $this->setJsonSettingArray('tax_rates_json', $taxes);

        return redirect()->route('tax-rates')->with('success', 'Tax rate updated successfully.');
    }

    public function destroyTaxRate(int $id)
    {
        $taxes = $this->getJsonSettingArray('tax_rates_json');
        $taxes = collect($taxes)
            ->reject(fn ($item) => (int) ($item['Id'] ?? 0) === $id)
            ->values()
            ->all();

        $this->setJsonSettingArray('tax_rates_json', $taxes);

        return redirect()->route('tax-rates')->with('success', 'Tax rate deleted successfully.');
    }

    public function storeCustomField(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:100',
            'label' => 'required|string|max:191',
            'type' => 'required|string|max:50',
            'default_value' => 'nullable|string|max:500',
            'required' => 'nullable|boolean',
        ]);

        $fields = $this->getJsonSettingArray('custom_fields_json');
        $maxId = collect($fields)->max(fn ($item) => (int) ($item['id'] ?? $item['Id'] ?? 0));
        $nextId = $maxId + 1;

        $fields[] = [
            'id' => $nextId,
            'module' => $validated['module'],
            'label' => $validated['label'],
            'type' => $validated['type'],
            'default_value' => $validated['default_value'] ?? '',
            'required' => (int) ($validated['required'] ?? 0),
        ];

        $this->setJsonSettingArray('custom_fields_json', $fields);

        return redirect()->route('custom-filed')->with('success', 'Custom field added successfully.');
    }

    public function updateCustomField(Request $request, int $id)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:100',
            'label' => 'required|string|max:191',
            'type' => 'required|string|max:50',
            'default_value' => 'nullable|string|max:500',
            'required' => 'nullable|boolean',
        ]);

        $fields = $this->getJsonSettingArray('custom_fields_json');
        $fields = collect($fields)->map(function ($item) use ($id, $validated) {
            $itemId = (int) ($item['id'] ?? $item['Id'] ?? 0);
            if ($itemId === $id) {
                $item['id'] = $id;
                $item['module'] = $validated['module'];
                $item['label'] = $validated['label'];
                $item['type'] = $validated['type'];
                $item['default_value'] = $validated['default_value'] ?? '';
                $item['required'] = (int) ($validated['required'] ?? 0);
            }
            return $item;
        })->values()->all();

        $this->setJsonSettingArray('custom_fields_json', $fields);

        return redirect()->route('custom-filed')->with('success', 'Custom field updated successfully.');
    }

    public function destroyCustomField(int $id)
    {
        $fields = $this->getJsonSettingArray('custom_fields_json');
        $fields = collect($fields)
            ->reject(fn ($item) => (int) ($item['id'] ?? $item['Id'] ?? 0) === $id)
            ->values()
            ->all();

        $this->setJsonSettingArray('custom_fields_json', $fields);

        return redirect()->route('custom-filed')->with('success', 'Custom field deleted successfully.');
    }

    public function storeEmailTemplate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'content' => 'nullable|string',
        ]);

        $templates = $this->getJsonSettingArray('email_templates_json');
        $maxId = collect($templates)->max(fn ($item) => (int) ($item['id'] ?? 0));
        $nextId = $maxId + 1;

        $templates[] = [
            'id' => $nextId,
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'content' => $validated['content'] ?? '',
        ];

        $this->setJsonSettingArray('email_templates_json', $templates);

        return redirect()->route('email-template')->with('success', 'Email template added successfully.');
    }

    public function updateEmailTemplate(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'content' => 'nullable|string',
        ]);

        $templates = $this->getJsonSettingArray('email_templates_json');
        $templates = collect($templates)->map(function ($item) use ($id, $validated) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $item['title'] = $validated['title'];
                $item['subject'] = $validated['subject'];
                $item['content'] = $validated['content'] ?? '';
            }
            return $item;
        })->values()->all();

        $this->setJsonSettingArray('email_templates_json', $templates);

        return redirect()->route('email-template')->with('success', 'Email template updated successfully.');
    }

    public function destroyEmailTemplate(int $id)
    {
        $templates = $this->getJsonSettingArray('email_templates_json');
        $templates = collect($templates)
            ->reject(fn ($item) => (int) ($item['id'] ?? 0) === $id)
            ->values()
            ->all();

        $this->setJsonSettingArray('email_templates_json', $templates);

        return redirect()->route('email-template')->with('success', 'Email template deleted successfully.');
    }
}
