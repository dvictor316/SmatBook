<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\ActivityLog;
use App\Support\ActiveBranchResolver;
use App\Support\DemoSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemoProvisioningService
{
    public function __construct(
        private readonly DemoSettings $demoSettings,
        private readonly ActiveBranchResolver $activeBranchResolver
    ) {
    }

    /**
     * Provision a demo company and user for an approved demo request.
     * Seeds the company with realistic fake accounting data.
     *
     * @return array{company: Company, user: User, plain_password: string}
     */
    public function provision(DemoRequest $demoRequest): array
    {
        return DB::transaction(function () use ($demoRequest) {
            $plainPassword = Str::random(12);
            $slug = $this->generateUniqueDemoSlug($demoRequest->company_name);
            $loginEmail = $this->resolveDemoLoginEmail($demoRequest);
            $demoExpiresAt = now()->addHours($this->demoSettings->lifetimeHours());

            // 1. Create the demo company
            $company = Company::create($this->onlyExistingColumns('companies', [
                'name'            => $demoRequest->company_name . ' (Demo)',
                'company_name'    => $demoRequest->company_name . ' (Demo)',
                'email'           => $demoRequest->email,
                'phone'           => $demoRequest->phone,
                'country'         => $demoRequest->country ?? 'NG',
                'currency_code'   => 'NGN',
                'currency_symbol' => '₦',
                'status'          => 'demo',
                'is_demo'         => true,
                'demo_expires_at' => $demoExpiresAt,
                'domain_prefix'   => $slug,
                'domain'          => $slug,
                'subdomain'       => $slug,
                'plan'            => 'Enterprise Demo',
                'industry'        => $demoRequest->business_type ?? 'General',
            ]));

            // 2. Create the demo user (owner of the demo company)
            $user = User::create($this->onlyExistingColumns('users', [
                'name'              => $demoRequest->full_name,
                'email'             => $loginEmail,
                'password'          => Hash::make($plainPassword),
                'role'              => 'admin',
                'company_id'        => $company->id,
                'status'            => 'active',
                'is_verified'       => true,
                'verified_at'       => now(),
                'email_verified_at' => now(),
            ]));

            // Link company owner
            $company->update($this->onlyExistingColumns('companies', ['user_id' => $user->id, 'owner_id' => $user->id]));

            // 3. Make the workspace fully usable inside the normal tenant app.
            $this->ensureDemoSubscription($company, $user, $slug, $demoExpiresAt);
            $this->ensureDemoBranch($company);

            // 4. Seed demo data (products, customers, transactions)
            $this->seedDemoData($company, $user);

            // 5. Audit log
            ActivityLog::record('Demo', 'provisioned', "Demo account provisioned for {$demoRequest->email}", [
                'company_id' => $company->id,
                'user_id'    => $user->id,
                'properties' => ['demo_request_id' => $demoRequest->id],
            ]);

            return [
                'company'        => $company,
                'user'           => $user,
                'login_email'    => $loginEmail,
                'plain_password' => $plainPassword,
            ];
        });
    }

    public function resetDemoWorkspace(Company $company, ?User $actor = null): void
    {
        if (! $company->isDemo()) {
            return;
        }

        DB::transaction(function () use ($company, $actor) {
            $companyId = (int) $company->id;
            $user = User::query()->where('company_id', $companyId)->orderBy('id')->first();

            $this->purgeDemoTenantData($companyId);
            $this->purgeCompanyScopedSettings($companyId);
            $this->ensureDemoBranch($company);

            if ($user) {
                $this->seedDemoData($company->fresh(), $user);
            }

            $company->forceFill([
                'demo_expires_at' => now()->addHours($this->demoSettings->lifetimeHours()),
                'status' => 'demo',
            ])->save();

            if ($user) {
                $this->ensureDemoSubscription($company->fresh(), $user, (string) ($company->domain_prefix ?? $company->subdomain ?? 'demo'), $company->demo_expires_at);
            }

            ActivityLog::record('Demo', 'reset', "Demo workspace reset for company #{$companyId}", [
                'company_id' => $companyId,
                'user_id' => $actor?->id,
            ]);
        });
    }

    public function expireDemo(DemoRequest $demoRequest): void
    {
        if ($demoRequest->demo_company_id && $company = Company::find($demoRequest->demo_company_id)) {
            $company->update($this->onlyExistingColumns('companies', [
                'status' => 'expired',
                'demo_expires_at' => now()->subMinute(),
            ]));
        }

        $this->deactivateExpiredDemo($demoRequest);
    }

    public function extendDemo(DemoRequest $demoRequest, int $hours): void
    {
        $hours = max(1, min(168, $hours));
        $expiresAt = now()->addHours($hours);

        if ($demoRequest->demo_company_id) {
            Company::where('id', $demoRequest->demo_company_id)->update($this->onlyExistingColumns('companies', [
                'status' => 'demo',
                'demo_expires_at' => $expiresAt,
            ]));
        }

        if ($demoRequest->demo_user_id) {
            User::where('id', $demoRequest->demo_user_id)->update($this->onlyExistingColumns('users', [
                'status' => 'active',
                'allow_login' => true,
            ]));
        }

        $demoRequest->update([
            'status' => 'approved',
            'expires_at' => $expiresAt,
        ]);
    }

    private function generateUniqueDemoSlug(string $companyName): string
    {
        $base = 'demo-' . Str::slug($companyName);
        $slug = $base . '-' . Str::lower(Str::random(5));

        while ($this->slugExists($slug)) {
            $slug = $base . '-' . Str::lower(Str::random(5));
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        if (! Schema::hasTable('companies')) {
            return false;
        }

        $slugColumns = array_values(array_filter(
            ['domain_prefix', 'domain', 'subdomain'],
            fn (string $column) => Schema::hasColumn('companies', $column)
        ));

        if ($slugColumns === []) {
            return false;
        }

        $query = Company::query();

        return $query->where(function ($builder) use ($slug, $slugColumns) {
            foreach ($slugColumns as $index => $column) {
                if ($index === 0) {
                    $builder->where($column, $slug);
                } else {
                    $builder->orWhere($column, $slug);
                }
            }
        })->exists();
    }

    private function resolveDemoLoginEmail(DemoRequest $demoRequest): string
    {
        $originalEmail = trim(strtolower((string) $demoRequest->email));
        if ($originalEmail === '') {
            return 'demo-' . $demoRequest->id . '@smartprobook.local';
        }

        $existingUser = $this->findUserByEmail($originalEmail);
        if (! $existingUser) {
            return $originalEmail;
        }

        $localPart = Str::before($originalEmail, '@');
        $domainPart = Str::after($originalEmail, '@');
        $domainPart = $domainPart !== '' ? $domainPart : 'smartprobook.local';

        $candidate = "{$localPart}+demo-{$demoRequest->id}@{$domainPart}";
        $attempt = 1;

        while ($this->findUserByEmail($candidate)) {
            $candidate = "{$localPart}+demo-{$demoRequest->id}-{$attempt}@{$domainPart}";
            $attempt++;
        }

        return $candidate;
    }

    private function findUserByEmail(string $email): ?User
    {
        $query = User::query();

        if ($this->modelUsesSoftDeletes(new User())) {
            $query->withTrashed();
        }

        return $query->where('email', $email)->first();
    }

    private function modelUsesSoftDeletes(object $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * Seed realistic demo data so the demo environment feels populated.
     */
    private function seedDemoData(Company $company, User $user): void
    {
        $companyId = $company->id;
        $now       = now();

        // Categories
        $categories = [];
        foreach (['Electronics', 'Clothing', 'Food & Beverage', 'Stationery'] as $catName) {
            $categories[] = DB::table('categories')->insertGetId($this->onlyExistingColumns('categories', [
                'name'       => $catName,
                'company_id' => $companyId,
                'user_id'    => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Products
        $productSeeds = [
            ['Laptop Computer',    150000, 120000, 25, $categories[0]],
            ['Wireless Mouse',      8500,    5000, 60, $categories[0]],
            ['Office Chair',       45000,   30000, 15, $categories[2] ?? $categories[0]],
            ['A4 Paper (Ream)',      2500,    1500, 200, $categories[3] ?? $categories[0]],
            ['Branded T-Shirt',     5000,    2500, 100, $categories[1]],
        ];

        $productIds = [];
        foreach ($productSeeds as [$name, $price, $cost, $qty, $catId]) {
            $productIds[] = DB::table('products')->insertGetId($this->onlyExistingColumns('products', [
                'user_id'        => $user->id,
                'name'           => $name,
                'sku'            => 'DEMO-' . strtoupper(Str::random(8)),
                'price'          => $price,
                'purchase_price' => $cost,
                'stock_quantity' => $qty,
                'stock'          => $qty,
                'category_id'    => $catId,
                'company_id'     => $companyId,
                'base_unit_name' => 'pcs',
                'unit_type'      => 'unit',
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]));
        }

        // Customers
        $customerIds = [];
        $customerSeeds = [
            ['Amaka Obi',    'amaka@demo.com',   '08012345678'],
            ['Emeka Chukwu', 'emeka@demo.com',   '08023456789'],
            ['Fatima Bello',  'fatima@demo.com', '08034567890'],
        ];
        foreach ($customerSeeds as [$name, $email, $phone]) {
            $customerIds[] = DB::table('customers')->insertGetId($this->onlyExistingColumns('customers', [
                'name'          => $name,
                'customer_name' => $name,
                'billing_name'  => $name,
                'shipping_name' => $name,
                'email'         => $email,
                'phone'         => $phone,
                'status'        => 'active',
                'company_id'    => $companyId,
                'user_id'       => $user->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]));
        }

        // Demo sales (last 7 days)
        for ($i = 0; $i < 5; $i++) {
            $productId    = $productIds[array_rand($productIds)];
            $product      = DB::table('products')->find($productId);
            $qty          = rand(1, 3);
            $unitPrice    = $product->price ?? 10000;
            $totalAmount  = $unitPrice * $qty;
            $saleDate     = now()->subDays(rand(0, 6));
            $customerId   = $customerIds[array_rand($customerIds)];
            $customerName = $this->customerDisplayName($customerId);
            $reference = 'DEMO-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)) . '-' . ($i + 1);

            $saleId = DB::table('sales')->insertGetId($this->onlyExistingColumns('sales', [
                'order_number' => $reference,
                'invoice_no'    => 'INV-' . $reference,
                'receipt_no'    => 'RCT-' . $reference,
                'total'         => $totalAmount,
                'total_amount'  => $totalAmount,
                'subtotal'      => $totalAmount,
                'paid'          => $totalAmount,
                'amount_paid'   => $totalAmount,
                'balance'       => 0,
                'status'        => 'completed',
                'payment_status'=> 'paid',
                'payment_method'=> 'cash',
                'currency'      => 'NGN',
                'terminal_id'   => 'DEMO',
                'company_id'    => $companyId,
                'user_id'       => $user->id,
                'customer_id'   => $customerId,
                'customer_name' => $customerName,
                'order_date'    => $saleDate,
                'created_at'    => $saleDate,
                'updated_at'    => $saleDate,
            ]));

            DB::table('sale_items')->insert($this->onlyExistingColumns('sale_items', [
                'sale_id'     => $saleId,
                'product_id'  => $productId,
                'product_name'=> $product->name,
                'quantity'    => $qty,
                'qty'         => $qty,
                'price'       => $unitPrice,
                'unit_price'  => $unitPrice,
                'total'       => $totalAmount,
                'total_price' => $totalAmount,
                'company_id'  => $companyId,
                'user_id'     => $user->id,
                'created_at'  => $saleDate,
                'updated_at'  => $saleDate,
            ]));
        }

        // Demo expenses (last 7 days)
        foreach (['Office Supplies', 'Internet Bill', 'Transport'] as $i => $expenseName) {
            $expenseDate = now()->subDays($i + 1);
            $expenseAmount = rand(5000, 50000);
            $expenseReference = 'EXP-DEMO-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)) . '-' . ($i + 1);

            DB::table('expenses')->insert($this->onlyExistingColumns('expenses', [
                'expense_id'     => $expenseReference,
                'reference'      => $expenseReference,
                'company_name'   => $company->company_name ?? $company->name ?? 'Demo Company',
                'email'          => $company->email ?? $user->email,
                'description'    => $expenseName . ' (Demo)',
                'notes'          => $expenseName . ' demo operating expense',
                'amount'         => $expenseAmount,
                'category'       => 'Operating',
                'payment_mode'   => 'cash',
                'payment_status' => 'paid',
                'status'         => 'Paid',
                'company_id'     => $companyId,
                'user_id'        => $user->id,
                'created_by'     => $user->id,
                'created_at'     => $expenseDate,
                'updated_at'     => $expenseDate,
            ]));
        }
    }

    /**
     * Deactivate an expired demo: mark user inactive, mark company expired.
     */
    public function deactivateExpiredDemo(DemoRequest $demoRequest): void
    {
        if ($demoRequest->demo_user_id) {
            User::where('id', $demoRequest->demo_user_id)
                ->update($this->onlyExistingColumns('users', [
                    'status' => 'suspended',
                    'allow_login' => false,
                ]));
        }

        if ($demoRequest->demo_company_id) {
            Company::where('id', $demoRequest->demo_company_id)
                ->update($this->onlyExistingColumns('companies', ['status' => 'expired']));
        }

        $demoRequest->update(['status' => 'expired']);

        ActivityLog::record('Demo', 'expired', "Demo account expired for {$demoRequest->email}", [
            'properties' => ['demo_request_id' => $demoRequest->id],
        ]);
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($_value, string $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function customerDisplayName(int $customerId): string
    {
        if (!Schema::hasTable('customers')) {
            return 'Demo Customer';
        }

        foreach (['customer_name', 'name', 'billing_name', 'shipping_name', 'email', 'phone'] as $column) {
            if (!Schema::hasColumn('customers', $column)) {
                continue;
            }

            $value = DB::table('customers')->where('id', $customerId)->value($column);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return 'Demo Customer';
    }

    private function ensureDemoSubscription(Company $company, User $user, string $slug, $expiresAt): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $enterprisePlanId = null;
        if (Schema::hasTable('plans')) {
            $enterprisePlanId = Plan::query()
                ->where(function ($query) {
                    $query->whereRaw('LOWER(name) like ?', ['%enterprise%'])
                        ->orWhereRaw('LOWER(name) like ?', ['%professional%'])
                        ->orWhereRaw('LOWER(name) like ?', ['%pro%']);
                })
                ->value('id');
        }

        Subscription::updateOrCreate(
            ['company_id' => $company->id],
            $this->onlyExistingColumns('subscriptions', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'plan_id' => $enterprisePlanId,
                'plan' => 'Enterprise Demo',
                'plan_name' => 'Enterprise Demo',
                'subscriber_name' => $company->company_name ?? $company->name,
                'domain_prefix' => $slug,
                'employee_size' => '1-10',
                'amount' => 0,
                'billing_cycle' => 'Demo',
                'user_limit' => 50,
                'start_date' => now(),
                'end_date' => $expiresAt,
                'status' => 'Active',
                'payment_status' => 'free',
                'payment_gateway' => 'demo',
                'payment_reference' => 'demo-' . $company->id,
                'transaction_reference' => 'demo-' . $company->id,
                'activated_at' => now(),
                'initialized_at' => now(),
                'paid_at' => now(),
                'payment_date' => now(),
            ])
        );
    }

    private function ensureDemoBranch(Company $company): void
    {
        $owner = User::find($company->user_id) ?? User::where('company_id', $company->id)->orderBy('id')->first();
        $this->activeBranchResolver->seedDefaultBranch($owner, 'Demo HQ');
    }

    private function purgeDemoTenantData(int $companyId): void
    {
        if ($companyId <= 0) {
            return;
        }

        $excludedTables = [
            'companies',
            'users',
            'subscriptions',
            'settings',
            'demo_requests',
            'plans',
            'packages',
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'failed_jobs',
            'sessions',
            'jobs',
            'job_batches',
            'cache',
            'cache_locks',
        ];

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            foreach (Schema::getTableListing() as $table) {
                if (in_array($table, $excludedTables, true) || ! Schema::hasColumn($table, 'company_id')) {
                    continue;
                }

                DB::table($table)->where('company_id', $companyId)->delete();
            }
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    private function purgeCompanyScopedSettings(int $companyId): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Setting::query()
            ->where('key', 'like', '%_company_' . $companyId)
            ->delete();
    }
}
