<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\EmailAuditLog;
use App\Models\Bank;
use App\Models\Account;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
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

    private function resolveCurrentSubscription(): ?Subscription
    {
        if (!Auth::check() || !Schema::hasTable('subscriptions')) {
            return null;
        }

        return Subscription::resolveCurrentForUser(Auth::user())
            ?? Subscription::where('user_id', Auth::id())->latest()->first();
    }

    private function resolveBranchLimitContext(): array
    {
        $subscription = $this->resolveCurrentSubscription();
        $planLabel = trim((string) session('user_plan'));

        if ($planLabel === '' && $subscription) {
            $planLabel = $subscription->planLabel();
        }

        if ($planLabel === '') {
            $planLabel = 'Basic';
        }

        $branchLimit = $subscription?->resolvedBranchLimit() ?? Plan::defaultBranchLimitForName($planLabel);

        return [
            'subscription' => $subscription,
            'planLabel' => $planLabel,
            'branchLimit' => $branchLimit,
        ];
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
        $accountTypes = Account::typeOptions();
        $subtypeOptionsByType = Account::subtypeOptionsByType();

        if (Schema::hasTable('accounts')) {
            $accounts = Account::query()
                ->when(Schema::hasTable('transactions'), fn ($query) => $query->withCount('transactions'))
                ->orderByRaw("FIELD(type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense')")
                ->orderBy('code')
                ->get();

            // Compute balances from transactions in a single aggregate query so
            // the COA stats always agree with the Balance Sheet (which also computes
            // from transactions). The stored current_balance column can become stale
            // because LedgerService doesn't update it after every posting.
            if (Schema::hasTable('transactions')) {
                $txnTotals = \App\Models\Transaction::query()
                    ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->groupBy('account_id')
                    ->get()
                    ->keyBy('account_id');

                $accounts = $accounts->map(function (Account $account) use ($txnTotals) {
                    $t   = $txnTotals->get($account->id);
                    $dr  = (float) ($t->total_debit  ?? 0);
                    $cr  = (float) ($t->total_credit ?? 0);
                    $ob  = (float) ($account->opening_balance ?? 0);
                    $isDebit = in_array($account->type, [Account::TYPE_ASSET, Account::TYPE_EXPENSE]);
                    // In-memory only — does NOT write to DB
                    $account->current_balance = $isDebit ? ($ob + $dr) - $cr : ($ob + $cr) - $dr;
                    return $account;
                });
            }

            $accountGroups = $accounts->groupBy('type');

            $typeOrder = $accountTypes;

            $accountSummary = collect($typeOrder)->map(function ($type) use ($accountGroups) {
                $group = $accountGroups->get($type, collect());

                return [
                    'type' => $type,
                    'count' => $group->count(),
                    'balance' => (float) $group->sum(fn ($account) => (float) ($account->current_balance ?? $account->opening_balance ?? 0)),
                ];
            });
        }

        return view('Settings.chart-of-accounts', compact(
            'settings',
            'accounts',
            'accountGroups',
            'accountSummary',
            'accountTypes',
            'subtypeOptionsByType'
        ));
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
        $branchContext = $this->resolveBranchLimitContext();
        $branchLimit = $branchContext['branchLimit'];
        $planLabel = $branchContext['planLabel'];
        $branchSlotsRemaining = $branchLimit === null ? null : max($branchLimit - $branches->count(), 0);

        if ($activeBranch && $activeBranchId === '') {
            session([
                'active_branch_id' => $activeBranch['id'],
                'active_branch_name' => $activeBranch['name'],
            ]);
        }

        return view('Settings.branches', compact(
            'settings',
            'branches',
            'activeBranch',
            'branchLimit',
            'planLabel',
            'branchSlotsRemaining'
        ));
    }
    public function bank_reconciliation()
    {
        $settings = $this->getSettings();
        $banks = collect();
        $reconciliations = collect();
        $recentImports = collect();
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
                $account = $this->resolveBankLedgerAccount($bank, $accounts);
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

        if (Schema::hasTable('bank_statement_imports')) {
            $recentImports = BankStatementImport::query()
                ->with('bank')
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('Settings.bank-reconciliation', compact('settings', 'banks', 'reconciliations', 'summary', 'recentImports'));
    }

    public function bankStatementImports(Request $request)
    {
        $settings = $this->getSettings();
        $banks = Schema::hasTable('banks')
            ? Bank::query()->orderBy('name')->get()
            : collect();

        $imports = new LengthAwarePaginator([], 0, 15);
        $selectedImport = null;
        $selectedLines = new LengthAwarePaginator([], 0, 25);
        $selectedAccount = null;
        $selectedBank = null;
        $statusSummary = [
            'total' => 0,
            'matched' => 0,
            'unmatched' => 0,
        ];

        if (Schema::hasTable('bank_statement_imports')) {
            $importsQuery = BankStatementImport::query()
                ->with('bank')
                ->withCount('lines')
                ->latest();

            if ($request->filled('bank_id')) {
                $importsQuery->where('bank_id', (int) $request->input('bank_id'));
            }

            if ($request->filled('date_from')) {
                $importsQuery->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $importsQuery->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $imports = $importsQuery->paginate(12)->withQueryString();
            $selectedImportId = (int) $request->input('import_id', $imports->first()?->id ?? 0);

            if ($selectedImportId > 0) {
                $selectedImport = BankStatementImport::query()
                    ->with('bank')
                    ->find($selectedImportId);
            }
        }

        if ($selectedImport && Schema::hasTable('bank_statement_lines')) {
            $selectedBank = $selectedImport->bank;
            $assetAccounts = Schema::hasTable('accounts')
                ? Account::query()
                    ->where('is_active', true)
                    ->where('type', Account::TYPE_ASSET)
                    ->orderBy('name')
                    ->get()
                : collect();

            $selectedAccount = $selectedBank ? $this->resolveBankLedgerAccount($selectedBank, $assetAccounts) : null;

            $baseLineQuery = BankStatementLine::query()
                ->where('bank_statement_import_id', $selectedImport->id);

            $statusSummary = [
                'total' => (clone $baseLineQuery)->count(),
                'matched' => (clone $baseLineQuery)->where('status', 'matched')->count(),
                'unmatched' => (clone $baseLineQuery)->where('status', '!=', 'matched')->count(),
            ];

            $lineQuery = BankStatementLine::query()
                ->with(['matchedTransaction.account'])
                ->where('bank_statement_import_id', $selectedImport->id)
                ->orderByDesc('line_date')
                ->orderByDesc('id');

            $status = strtolower(trim((string) $request->input('status', '')));
            if (in_array($status, ['matched', 'unmatched'], true)) {
                if ($status === 'matched') {
                    $lineQuery->where('status', 'matched');
                } else {
                    $lineQuery->where('status', '!=', 'matched');
                }
            }

            $selectedLines = $lineQuery->paginate(25)->withQueryString();

            $transactionPool = collect();
            if ($selectedAccount && Schema::hasTable('transactions')) {
                $lineDates = $selectedLines->getCollection()
                    ->pluck('line_date')
                    ->filter()
                    ->map(fn ($date) => Carbon::parse($date));

                $transactionQuery = Transaction::query()
                    ->with('account')
                    ->where('account_id', $selectedAccount->id)
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id');

                if ($lineDates->isNotEmpty()) {
                    $transactionQuery->whereBetween('transaction_date', [
                        $lineDates->min()->copy()->subDays(7)->toDateString(),
                        $lineDates->max()->copy()->addDays(7)->toDateString(),
                    ]);
                }

                $transactionPool = $transactionQuery->limit(400)->get();
            }

            $selectedLines->setCollection(
                $selectedLines->getCollection()->map(function (BankStatementLine $line) use ($transactionPool) {
                    $line->setRelation('suggestedTransactions', $this->suggestTransactionsForStatementLine($line, $transactionPool));
                    return $line;
                })
            );
        }

        return view('Settings.bank-statement-imports', compact(
            'settings',
            'banks',
            'imports',
            'selectedImport',
            'selectedLines',
            'selectedAccount',
            'selectedBank',
            'statusSummary'
        ));
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

    public function openingBalanceBackfill(Request $request)
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));
        if (!in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true)) {
            return redirect()->back()->with('error', 'Unauthorized: only super admin can run opening-balance backfill.');
        }

        try {
            Artisan::call('accounts:backfill-opening-balance');

            $output = trim((string) Artisan::output());
            $message = $output !== '' ? $output : 'Opening balance backfill completed.';

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Opening balance backfill failed: ' . $e->getMessage());
        }
    }

    public function storeChartAccount(Request $request)
    {
        if (!Schema::hasTable('accounts')) {
            return redirect()->back()->with('error', 'Accounts table is not available in this installation.');
        }

        $companyId = (int) (Auth::user()->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) Auth::id();
        $branchId  = (string) session('active_branch_id', '');
        $branchName = (string) session('active_branch_name', '');

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('accounts', 'code')
                    ->where('company_id', $companyId ?: null)
                    ->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:191',
            'type' => ['required', Rule::in(Account::typeOptions())],
            'sub_type' => 'nullable|string|max:191',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['sub_type'] = trim((string) ($validated['sub_type'] ?? ''));
        if ($validated['sub_type'] === '') {
            $validated['sub_type'] = null;
        }

        $validated['code'] = trim((string) ($validated['code'] ?? ''));
        if ($validated['code'] === '') {
            $validated['code'] = $this->generateChartAccountCode($validated['type'], $companyId);
        }

        $allowedSubtypes = Account::subtypeOptionsFor($validated['type']);
        if ($validated['sub_type'] !== null && !in_array($validated['sub_type'], $allowedSubtypes, true)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'sub_type' => 'Please select a valid sub type for the chosen account type.',
                ]);
        }

        Account::create([
            'code'            => $validated['code'],
            'name'            => $validated['name'],
            'type'            => $validated['type'],
            'sub_type'        => $validated['sub_type'] ?? null,
            'opening_balance' => (float) ($validated['opening_balance'] ?? 0),
            'current_balance' => (float) ($validated['opening_balance'] ?? 0),
            'description'     => $validated['description'] ?? null,
            'is_active'       => (bool) ($validated['is_active'] ?? true),
            'company_id'      => $companyId ?: null,
            'user_id'         => $userId ?: null,
            'branch_id'       => $branchId !== '' ? $branchId : null,
            'branch_name'     => $branchName !== '' ? $branchName : null,
        ]);

        return redirect()->route('chart-of-accounts')->with('success', 'Account added to chart of accounts.');
    }

    public function storeBankStatementImport(Request $request)
    {
        if (!Schema::hasTable('bank_statement_imports') || !Schema::hasTable('bank_statement_lines')) {
            return redirect()->back()->with('error', 'Bank statement import tables are not available yet. Run the latest migrations first.');
        }

        $companyId = (int) (Auth::user()->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) Auth::id();
        $activeBranchId = (string) session('active_branch_id', '');
        $activeBranchName = (string) session('active_branch_name', '');

        $validated = $request->validate([
            'bank_id' => ['required', 'integer'],
            'statement_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'currency' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bank = Bank::query()->findOrFail((int) $validated['bank_id']);

        if ($companyId > 0 && (int) ($bank->company_id ?? 0) > 0 && (int) $bank->company_id !== $companyId) {
            abort(403, 'You are not allowed to import statements for this bank account.');
        }

        $bankBranchId = trim((string) ($bank->branch_id ?? ''));
        $bankBranchName = trim((string) ($bank->branch_name ?? $bank->branch ?? ''));

        if ($activeBranchId !== '' && $bankBranchId !== '' && $bankBranchId !== $activeBranchId) {
            return redirect()->back()->with('error', 'The selected bank account belongs to another branch.');
        }

        $branchId = $bankBranchId !== '' ? $bankBranchId : $activeBranchId;
        $branchName = $bankBranchName !== '' ? $bankBranchName : $activeBranchName;

        $parsed = $this->parseBankStatementCsv($request->file('statement_file')->getRealPath());
        if (empty($parsed['lines'])) {
            return redirect()->back()->with('error', 'No valid statement lines were found in the uploaded CSV.');
        }

        $storedPath = $request->file('statement_file')->store('bank-statements');

        $importId = DB::transaction(function () use ($companyId, $userId, $branchId, $branchName, $validated, $storedPath, $bank, $parsed) {
            $import = BankStatementImport::create([
                'company_id' => $companyId ?: null,
                'user_id' => $userId ?: null,
                'branch_id' => $branchId !== '' ? $branchId : null,
                'branch_name' => $branchName !== '' ? $branchName : null,
                'bank_id' => $bank->id,
                'uploaded_by' => $userId ?: null,
                'source_file_name' => (string) $validated['statement_file']->getClientOriginalName(),
                'stored_file_path' => $storedPath,
                'currency' => strtoupper(trim((string) ($validated['currency'] ?? ''))) ?: null,
                'statement_date_from' => $parsed['date_from'],
                'statement_date_to' => $parsed['date_to'],
                'line_count' => count($parsed['lines']),
                'opening_balance' => $parsed['opening_balance'],
                'closing_balance' => $parsed['closing_balance'],
                'status' => 'imported',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($parsed['lines'] as $line) {
                BankStatementLine::create([
                    'company_id' => $companyId ?: null,
                    'user_id' => $userId ?: null,
                    'branch_id' => $branchId !== '' ? $branchId : null,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                    'bank_statement_import_id' => $import->id,
                    'bank_id' => $bank->id,
                    'line_date' => $line['line_date'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'amount' => $line['amount'],
                    'balance' => $line['balance'],
                    'status' => 'unmatched',
                    'raw_row' => $line['raw_row'],
                ]);
            }

            return $import->id;
        });

        return redirect()->route('bank-statement-imports', ['import_id' => $importId])->with('success', 'Bank statement imported successfully. Review and match the imported lines.');
    }

    public function matchBankStatementLine(Request $request, BankStatementLine $line)
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'integer'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $line->loadMissing('bank', 'import');
        $transaction = Transaction::query()->findOrFail((int) $validated['transaction_id']);

        if ($line->company_id && (int) $line->company_id !== (int) ($transaction->company_id ?? 0)) {
            return redirect()->back()->with('error', 'The selected transaction belongs to another tenant.');
        }

        if ($line->branch_id && trim((string) $line->branch_id) !== trim((string) ($transaction->branch_id ?? ''))) {
            return redirect()->back()->with('error', 'The selected transaction belongs to another branch.');
        }

        $mappedAccount = $line->bank ? $this->resolveBankLedgerAccount($line->bank) : null;
        if ($mappedAccount && (int) $transaction->account_id !== (int) $mappedAccount->id) {
            return redirect()->back()->with('error', 'The selected transaction is not posted to the mapped bank ledger account.');
        }

        $line->update([
            'matched_transaction_id' => $transaction->id,
            'status' => 'matched',
            'matched_at' => now(),
            'matched_by' => $request->user()?->id,
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Statement line matched successfully.');
    }

    public function unmatchBankStatementLine(BankStatementLine $line)
    {
        $line->update([
            'matched_transaction_id' => null,
            'status' => 'unmatched',
            'matched_at' => null,
            'matched_by' => null,
        ]);

        return redirect()->back()->with('success', 'Statement line unmatched successfully.');
    }

    private function parseBankStatementCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the uploaded statement file.');
        }

        $header = null;
        $columnMap = [];
        $lines = [];
        $dates = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($value) => trim((string) $value), $row);
                $columnMap = $this->mapStatementColumns($header);
                continue;
            }

            $rawRow = [];
            foreach ($header as $index => $columnName) {
                $rawRow[$columnName !== '' ? $columnName : 'column_' . $index] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $lineDate = $this->parseStatementDate($this->extractMappedValue($row, $columnMap, 'date'));
            $description = trim((string) $this->extractMappedValue($row, $columnMap, 'description'));
            $reference = trim((string) $this->extractMappedValue($row, $columnMap, 'reference'));
            $debit = $this->parseStatementAmount($this->extractMappedValue($row, $columnMap, 'debit'));
            $credit = $this->parseStatementAmount($this->extractMappedValue($row, $columnMap, 'credit'));
            $amount = $this->parseStatementAmount($this->extractMappedValue($row, $columnMap, 'amount'));
            $balance = $this->parseStatementAmount($this->extractMappedValue($row, $columnMap, 'balance'));

            if ($amount === null) {
                if ($credit !== null && $debit !== null) {
                    $amount = $credit - $debit;
                } elseif ($credit !== null) {
                    $amount = abs($credit);
                } elseif ($debit !== null) {
                    $amount = -abs($debit);
                } else {
                    $amount = 0.0;
                }
            }

            if ($lineDate === null && $description === '' && $reference === '' && abs((float) $amount) < 0.00001 && $balance === null) {
                continue;
            }

            if ($lineDate !== null) {
                $dates[] = $lineDate;
            }

            $lines[] = [
                'line_date' => $lineDate,
                'description' => $description !== '' ? $description : null,
                'reference' => $reference !== '' ? $reference : null,
                'debit' => $debit,
                'credit' => $credit,
                'amount' => round((float) $amount, 2),
                'balance' => $balance,
                'raw_row' => $rawRow,
            ];
        }

        fclose($handle);

        $openingBalance = !empty($lines) ? ($lines[0]['balance'] !== null ? round((float) $lines[0]['balance'] - (float) $lines[0]['amount'], 2) : null) : null;
        $closingBalance = !empty($lines) ? $lines[array_key_last($lines)]['balance'] : null;

        return [
            'lines' => $lines,
            'date_from' => !empty($dates) ? min($dates) : null,
            'date_to' => !empty($dates) ? max($dates) : null,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
        ];
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function mapStatementColumns(array $header): array
    {
        $aliases = [
            'date' => ['date', 'transaction date', 'value date', 'posting date'],
            'description' => ['description', 'details', 'narration', 'transaction details', 'remark', 'remarks'],
            'reference' => ['reference', 'ref', 'transaction ref', 'transaction id', 'document no', 'document'],
            'debit' => ['debit', 'withdrawal', 'money out', 'debits'],
            'credit' => ['credit', 'deposit', 'money in', 'credits'],
            'amount' => ['amount', 'transaction amount'],
            'balance' => ['balance', 'running balance', 'closing balance'],
        ];

        $normalizedHeader = array_map(function ($value) {
            return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
        }, $header);

        $map = [];
        foreach ($aliases as $key => $possibleLabels) {
            foreach ($normalizedHeader as $index => $columnName) {
                if (in_array($columnName, $possibleLabels, true)) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function extractMappedValue(array $row, array $map, string $key): ?string
    {
        if (!array_key_exists($key, $map)) {
            return null;
        }

        $value = $row[$map[$key]] ?? null;

        return $value === null ? null : trim((string) $value);
    }

    private function parseStatementAmount(?string $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace([',', ' '], '', $value);
        $negative = false;

        if (str_starts_with($normalized, '(') && str_ends_with($normalized, ')')) {
            $negative = true;
            $normalized = trim($normalized, '()');
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);
        if ($normalized === '' || $normalized === '-' || !is_numeric($normalized)) {
            return null;
        }

        $amount = (float) $normalized;

        return round($negative ? -abs($amount) : $amount, 2);
    }

    private function parseStatementDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'd M Y', 'd M, Y', 'M d, Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveBankLedgerAccount(Bank $bank, $accounts = null): ?Account
    {
        $accounts = $accounts instanceof \Illuminate\Support\Collection
            ? $accounts
            : (Schema::hasTable('accounts')
                ? Account::query()
                    ->where('is_active', true)
                    ->where('type', Account::TYPE_ASSET)
                    ->orderBy('name')
                    ->get()
                : collect());

        $account = $accounts->first(function (Account $account) use ($bank) {
            $bankName = strtolower(trim((string) $bank->name));
            $accountName = strtolower(trim((string) $account->name));

            return $bankName !== '' && (
                $accountName === $bankName
                || str_contains($accountName, $bankName)
                || str_contains($bankName, $accountName)
            );
        });

        if ($account) {
            return $account;
        }

        return $accounts->first(function (Account $account) {
            $name = strtolower((string) $account->name);
            return str_contains($name, 'bank') || str_contains($name, 'cash');
        });
    }

    private function syncBankLedgerAccount(Bank $bank, ?string $previousBankName = null, ?float $previousBankBalance = null): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $bankName = trim((string) ($bank->name ?? ''));
        if ($bankName === '') {
            return;
        }

        $companyId = (int) ($bank->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($bank->user_id ?? auth()->id() ?? 0);
        $branchId = trim((string) ($bank->branch_id ?? session('active_branch_id', '')));
        $branchName = trim((string) ($bank->branch_name ?? session('active_branch_name', '')));
        $newOpening = (float) ($bank->balance ?? 0);
        $oldOpening = $previousBankBalance !== null ? (float) $previousBankBalance : $newOpening;

        $account = Account::withoutGlobalScopes()
            ->when($companyId > 0 && Schema::hasColumn('accounts', 'company_id'), fn ($query) => $query->where('company_id', $companyId))
            ->where('name', $bankName)
            ->where('type', Account::TYPE_ASSET)
            ->first();

        if (!$account && $previousBankName !== null && trim($previousBankName) !== '') {
            $account = Account::withoutGlobalScopes()
                ->when($companyId > 0 && Schema::hasColumn('accounts', 'company_id'), fn ($query) => $query->where('company_id', $companyId))
                ->where('name', trim($previousBankName))
                ->where('type', Account::TYPE_ASSET)
                ->first();
        }

        if (!$account) {
            $this->createBankLedgerAccount($bankName, $companyId, $userId, $branchId, $branchName, $newOpening);
            return;
        }

        $currentBalance = (float) ($account->current_balance ?? 0);
        $payload = [
            'name' => $bankName,
            'opening_balance' => $newOpening,
            'current_balance' => $currentBalance - $oldOpening + $newOpening,
        ];

        if (Schema::hasColumn('accounts', 'sub_type') && empty($account->sub_type)) {
            $payload['sub_type'] = Account::SUBTYPE_CURRENT_ASSET;
        }
        if (Schema::hasColumn('accounts', 'branch_id') && $branchId !== '' && empty($account->branch_id)) {
            $payload['branch_id'] = $branchId;
        }
        if (Schema::hasColumn('accounts', 'branch_name') && $branchName !== '' && empty($account->branch_name)) {
            $payload['branch_name'] = $branchName;
        }
        if (Schema::hasColumn('accounts', 'is_active')) {
            $payload['is_active'] = true;
        }

        $account->update($payload);
    }

    private function createBankLedgerAccount(
        string $bankName,
        int $companyId,
        int $userId,
        string $branchId,
        string $branchName,
        float $openingBalance
    ): Account {
        $payload = [
            'code' => $this->generateChartAccountCode(Account::TYPE_ASSET, $companyId),
            'name' => $bankName,
            'type' => Account::TYPE_ASSET,
            'sub_type' => Account::SUBTYPE_CURRENT_ASSET,
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'description' => 'Auto-created bank ledger account for bank settings sync.',
            'is_active' => true,
        ];

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $payload['company_id'] = $companyId;
        }
        if ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $payload['user_id'] = $userId;
        }
        if ($branchId !== '' && Schema::hasColumn('accounts', 'branch_id')) {
            $payload['branch_id'] = $branchId;
        }
        if ($branchName !== '' && Schema::hasColumn('accounts', 'branch_name')) {
            $payload['branch_name'] = $branchName;
        }

        return Account::create($payload);
    }

    private function suggestTransactionsForStatementLine(BankStatementLine $line, $transactions)
    {
        $lineAmount = round((float) ($line->amount ?? 0), 2);
        $lineDate = $line->line_date ? Carbon::parse($line->line_date) : null;

        return $transactions
            ->map(function (Transaction $transaction) use ($lineAmount, $lineDate) {
                $transactionImpact = round((float) $transaction->debit - (float) $transaction->credit, 2);
                $sameAmount = abs($transactionImpact - $lineAmount) < 0.01;
                $sameAbsoluteAmount = abs(abs($transactionImpact) - abs($lineAmount)) < 0.01;
                $daysAway = $lineDate ? abs(Carbon::parse($transaction->transaction_date)->diffInDays($lineDate)) : 999;
                $score = ($sameAmount ? 100 : 0) + ($sameAbsoluteAmount ? 35 : 0) + max(0, 15 - min($daysAway, 15));

                return [
                    'transaction' => $transaction,
                    'score' => $score,
                    'impact' => $transactionImpact,
                    'days_away' => $daysAway,
                ];
            })
            ->filter(fn ($candidate) => $candidate['score'] > 0)
            ->sortByDesc('score')
            ->take(3)
            ->values();
    }

    public function updateChartAccount(Request $request, $id)
    {
        if (!Schema::hasTable('accounts')) {
            return redirect()->back()->with('error', 'Accounts table is not available in this installation.');
        }

        $companyId = (int) (Auth::user()->company_id ?? session('current_tenant_id') ?? 0);

        $account = Account::withoutGlobalScopes()
            ->when($companyId > 0, fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string|max:191',
            'sub_type'        => 'nullable|string|max:191',
            'opening_balance' => 'nullable|numeric',
            'description'     => 'nullable|string|max:1000',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['sub_type'] = trim((string) ($validated['sub_type'] ?? ''));
        if ($validated['sub_type'] === '') {
            $validated['sub_type'] = null;
        }

        // Recompute current_balance: new opening_balance + existing transaction movement
        $newOpening = (float) ($validated['opening_balance'] ?? 0);
        $oldOpening = (float) $account->opening_balance;

        $account->update([
            'name'            => $validated['name'],
            'sub_type'        => $validated['sub_type'],
            'opening_balance' => $newOpening,
            'current_balance' => $account->current_balance - $oldOpening + $newOpening,
            'description'     => $validated['description'] ?? null,
            'is_active'       => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('chart-of-accounts')->with('success', 'Account updated successfully.');
    }

    public function deactivateChartAccount($id)
    {
        if (!Schema::hasTable('accounts')) {
            return redirect()->back()->with('error', 'Accounts table is not available in this installation.');
        }

        $companyId = (int) (Auth::user()->company_id ?? session('current_tenant_id') ?? 0);

        $account = Account::withoutGlobalScopes()
            ->when($companyId > 0, fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $account->is_active = false;
        $account->save();

        return redirect()->route('chart-of-accounts')->with('success', "Account \"{$account->name}\" has been deactivated.");
    }

    public function destroyChartAccount($id)
    {
        if (!Schema::hasTable('accounts')) {
            return redirect()->back()->with('error', 'Accounts table is not available in this installation.');
        }

        $companyId = (int) (Auth::user()->company_id ?? session('current_tenant_id') ?? 0);

        $account = Account::withoutGlobalScopes()
            ->when($companyId > 0, fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        // Block deletion if account has any ledger transactions
        $txnCount = $account->transactions()->count();
        if ($txnCount > 0) {
            return redirect()->route('chart-of-accounts')
                ->with('error', "Cannot delete \"{$account->name}\" — it has {$txnCount} transaction(s) posted against it. Deactivate it instead.");
        }

        $account->delete();

        return redirect()->route('chart-of-accounts')->with('success', "Account \"{$account->name}\" deleted successfully.");
    }

    private function generateChartAccountCode(string $type, int $companyId = 0): string
    {
        $prefix = match ($type) {
            Account::TYPE_ASSET => 'AST',
            Account::TYPE_LIABILITY => 'LIB',
            Account::TYPE_EQUITY => 'EQT',
            Account::TYPE_REVENUE => 'REV',
            Account::TYPE_EXPENSE => 'EXP',
            default => 'ACC',
        };

        do {
            $code = $prefix . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

            $exists = Account::withoutGlobalScopes()
                ->where('code', $code)
                ->when($companyId > 0 && Schema::hasColumn('accounts', 'company_id'), function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->exists();
        } while ($exists);

        return $code;
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
        $branchContext = $this->resolveBranchLimitContext();
        $branchLimit = $branchContext['branchLimit'];
        $planLabel = ucfirst(trim((string) $branchContext['planLabel']));

        if ($branchLimit !== null && $branches->count() >= $branchLimit) {
            $branchLabel = $branchLimit === 1 ? 'branch' : 'branches';
            return redirect()->back()->withInput()->with(
                'error',
                $planLabel . ' plan allows up to ' . $branchLimit . ' ' . $branchLabel . '. Upgrade the plan to add more branches.'
            );
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

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        DB::transaction(function () use ($validated, $lines, $reference, $request, $companyId, $branchId, $branchName) {
            $transactionColumns = Schema::getColumnListing('transactions');

            foreach ($lines as $line) {
                $payload = [
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
                    'company_id' => $companyId ?: null,
                    'branch_id' => $branchId !== '' ? $branchId : null,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                ];

                Transaction::create(array_intersect_key($payload, array_flip($transactionColumns)));
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

        // Use a tenant-specific code so multiple tenants don't collide on the global unique constraint
        $code = $companyId > 0
            ? 'EQT-RECON-SUSPENSE-' . $companyId
            : 'EQT-RECON-SUSPENSE';

        // IMPORTANT: bypass TenantScoped global scope.
        // TenantScoped appends WHERE company_id = ? AND branch_id = ? to every query.
        // The suspense account may have been created without a matching branch_id (NULL),
        // so the scoped SELECT would find nothing → attempt INSERT → 1062 duplicate on code.
        // withoutGlobalScopes() ensures we search by code alone, reliably finding the existing row.
        $existing = Account::withoutGlobalScopes()->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $data = [
            'code' => $code,
            'name' => 'Bank Reconciliation Suspense',
            'type' => Account::TYPE_EQUITY,
            'sub_type' => 'Reconciliation Reserve',
            'description' => 'Temporary balancing account used for bank reconciliation adjustments.',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ];

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $data['company_id'] = $companyId;
        }

        return Account::forceCreate($data);
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

        $bank = Bank::create($payload);
        $this->syncBankLedgerAccount($bank, null, (float) ($payload['balance'] ?? 0));

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

        $oldBankName = (string) ($bank->name ?? '');
        $oldBankBalance = (float) ($bank->balance ?? 0);
        $bank->update($payload);
        $this->syncBankLedgerAccount($bank->fresh(), $oldBankName, $oldBankBalance);

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
