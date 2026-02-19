<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\EmailAuditLog;
use App\Models\Bank;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
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

    /**
     * Display Main Settings
     * Matches: resources/views/Settings/setting.blade.php
     */
    public function index()
    {
        $settings = $this->getSettings();
        $emailLogs = collect();

        if (Schema::hasTable('email_audit_logs')) {
            $emailLogs = EmailAuditLog::query()->latest()->limit(15)->get();
        }

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
                if ($oldFile && $oldFile->value && File::exists(public_path($oldFile->value))) {
                    File::delete(public_path($oldFile->value));
                }

                // Save new file
                $file = $request->file($field);
                $name = $field . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img'), $name);
                
                Setting::updateOrCreate(['key' => $field], ['value' => 'assets/img/' . $name]);
            }
        }

        // 2b. Normalize toggle/checkbox fields so unchecked states persist as "0".
        $booleanFields = [
            'mail_php_enabled',
            'mail_smtp_enabled',
            'payment_stripe_enabled',
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

        // 3. Dynamic Text Field Handler
        $inputs = $request->except(array_merge(['_token'], $fileFields));
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
    public function payment_settings() { return view('Settings.payment-settings', ['settings' => $this->getSettings()]); }
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
