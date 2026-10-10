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

        $this->seedDemoHotel($company, $user, $now);
        $this->seedDemoLivestock($company, $user, $now);

        // Keep approved demos operational but financially blank:
        // products and customers are available for exploration, while report-
        // driving sales/expense records start empty until the user creates them.
    }

    private function seedDemoHotel(Company $company, User $user, $now): void
    {
        if (! Schema::hasTable('hotel_properties')) {
            return;
        }

        $propertyId = DB::table('hotel_properties')->insertGetId($this->onlyExistingColumns('hotel_properties', [
            'company_id' => $company->id,
            'branch_id' => null,
            'name' => 'SmartProbook Demo Hotel',
            'code' => 'SPB-DEMO-'.$company->id,
            'address' => '12 Marina Business District',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'phone' => $company->phone,
            'email' => $company->email,
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'default_checkin_time' => '14:00:00',
            'default_checkout_time' => '12:00:00',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        if (! Schema::hasTable('hotel_room_types') || ! Schema::hasTable('hotel_rooms')) {
            return;
        }

        $roomTypeId = DB::table('hotel_room_types')->insertGetId($this->onlyExistingColumns('hotel_room_types', [
            'company_id' => $company->id,
            'property_id' => $propertyId,
            'name' => 'Executive King',
            'code' => 'EXEC-KING',
            'description' => 'Executive room prepared for the controlled product demo.',
            'bed_type' => 'King',
            'beds' => 1,
            'max_adults' => 2,
            'max_children' => 1,
            'max_occupancy' => 3,
            'base_rate' => 85000,
            'weekend_rate' => 95000,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        foreach ([
            ['101', '1', 'West', 'available', 'clean'],
            ['102', '1', 'West', 'occupied', 'clean'],
            ['201', '2', 'East', 'available', 'dirty'],
        ] as [$number, $floor, $wing, $status, $housekeeping]) {
            DB::table('hotel_rooms')->insert($this->onlyExistingColumns('hotel_rooms', [
                'company_id' => $company->id,
                'property_id' => $propertyId,
                'room_type_id' => $roomTypeId,
                'room_number' => $number,
                'floor' => $floor,
                'wing' => $wing,
                'operational_status' => $status,
                'housekeeping_status' => $housekeeping,
                'is_active' => true,
                'notes' => 'Controlled demo room',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function seedDemoLivestock(Company $company, User $user, $now): void
    {
        $requiredTables = [
            'livestock_farms', 'livestock_flocks', 'livestock_investments',
            'livestock_opex_entries', 'livestock_revenue_entries', 'livestock_daily_productions',
        ];
        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $farmId = DB::table('livestock_farms')->insertGetId($this->onlyExistingColumns('livestock_farms', [
            'company_id' => $company->id,
            'branch_id' => null,
            'branch_name' => 'Demo HQ',
            'name' => 'Green Acres Layer Farm',
            'code' => 'DEMO-FARM-'.$company->id,
            'farm_type' => 'layers',
            'bird_capacity' => 5000,
            'eggs_per_crate' => 30,
            'location' => 'Ogun State',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        $flockId = DB::table('livestock_flocks')->insertGetId($this->onlyExistingColumns('livestock_flocks', [
            'company_id' => $company->id,
            'farm_id' => $farmId,
            'batch_code' => 'DEMO-LAYERS-'.$company->id,
            'breed' => 'Isa Brown',
            'placement_date' => $now->copy()->subMonths(4)->toDateString(),
            'age_at_placement_weeks' => 16,
            'opening_birds' => 1200,
            'current_birds' => 1193,
            'cost_per_bird' => 5200,
            'supplier' => 'Demo Poultry Supply',
            'status' => 'active',
            'notes' => 'Sample production flock for guided exploration.',
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        foreach ([
            ['capital', 'layer_house', 'Layer house and pen', 18000000, 120],
            ['capital', 'battery_cages', 'Automated battery cages', 12500000, 96],
            ['working_capital', 'point_of_cage_birds', 'Point-of-cage birds', 6240000, 18],
            ['working_capital', 'feeding_14_25_weeks', 'Pre-production feeding', 2850000, 12],
        ] as [$class, $category, $description, $cost, $life]) {
            DB::table('livestock_investments')->insert($this->onlyExistingColumns('livestock_investments', [
                'company_id' => $company->id, 'farm_id' => $farmId, 'flock_id' => $flockId,
                'cost_class' => $class, 'category' => $category, 'description' => $description,
                'cost_date' => $now->copy()->subMonths(5)->toDateString(), 'cost' => $cost,
                'salvage_value' => 0, 'useful_life_months' => $life, 'allocation_method' => 'straight_line',
                'accumulated_allocation' => 0, 'status' => 'active', 'created_by' => $user->id,
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        foreach ([
            ['feeds', 'Layer mash feed', 24, 'bag', 21500, 516000, 'Demo Feed Mill'],
            ['medication', 'Routine medication', 1, 'lot', 85000, 85000, 'Demo Vet Services'],
            ['personnel', 'Farm personnel weekly allocation', 1, 'week', 180000, 180000, 'Payroll'],
        ] as $index => [$category, $description, $quantity, $unit, $unitCost, $amount, $vendor]) {
            DB::table('livestock_opex_entries')->insert($this->onlyExistingColumns('livestock_opex_entries', [
                'company_id' => $company->id, 'farm_id' => $farmId, 'flock_id' => $flockId,
                'expense_date' => $now->copy()->subDays($index * 3)->toDateString(), 'category' => $category,
                'description' => $description, 'quantity' => $quantity, 'unit' => $unit,
                'unit_cost' => $unitCost, 'amount' => $amount, 'frequency' => 'weekly',
                'vendor' => $vendor, 'reference' => 'DEMO-OPEX-'.($index + 1), 'created_by' => $user->id,
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        foreach ([
            ['eggs', 'Sale of graded eggs', 145, 'crate', 6200, 899000, 'Demo Grocers'],
            ['manure_litter', 'Bagged poultry manure', 40, 'bag', 1800, 72000, 'Demo Farms Cooperative'],
        ] as $index => [$source, $description, $quantity, $unit, $unitPrice, $amount, $customer]) {
            DB::table('livestock_revenue_entries')->insert($this->onlyExistingColumns('livestock_revenue_entries', [
                'company_id' => $company->id, 'farm_id' => $farmId, 'flock_id' => $flockId,
                'revenue_date' => $now->copy()->subDays($index * 4)->toDateString(), 'source' => $source,
                'description' => $description, 'quantity' => $quantity, 'unit' => $unit,
                'unit_price' => $unitPrice, 'amount' => $amount, 'customer' => $customer,
                'reference' => 'DEMO-REV-'.($index + 1), 'created_by' => $user->id,
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        for ($day = 6; $day >= 0; $day--) {
            $openingBirds = 1200 - (6 - $day);
            $mortality = $day === 3 ? 1 : 0;
            $closingBirds = $openingBirds - $mortality;
            $crates = 34 + (($day + 1) % 4);
            $looseEggs = ($day * 3) % 30;
            $goodEggs = ($crates * 30) + $looseEggs;
            $productionId = DB::table('livestock_daily_productions')->insertGetId($this->onlyExistingColumns('livestock_daily_productions', [
                'company_id' => $company->id, 'farm_id' => $farmId, 'flock_id' => $flockId,
                'production_date' => $now->copy()->subDays($day)->toDateString(), 'opening_birds' => $openingBirds,
                'mortality' => $mortality, 'culled' => 0, 'closing_birds' => $closingBirds,
                'egg_crates' => $crates, 'loose_eggs' => $looseEggs, 'damaged_eggs' => 4 + ($day % 3),
                'total_good_eggs' => $goodEggs, 'feed_kg' => 138 + ($day % 4), 'water_litres' => 260,
                'hen_day_percent' => round(($goodEggs / $openingBirds) * 100, 2),
                'notes' => 'Controlled demo production record', 'recorded_by' => $user->id,
                'created_at' => $now, 'updated_at' => $now,
            ]));

            if (Schema::hasTable('livestock_inventory_movements')) {
                foreach ([
                    ['feed', 'consumption', -(138 + ($day % 4)), 'kg'],
                    ['eggs', 'production', $goodEggs, 'egg'],
                ] as [$itemType, $movementType, $quantity, $unit]) {
                    DB::table('livestock_inventory_movements')->insert($this->onlyExistingColumns('livestock_inventory_movements', [
                        'company_id' => $company->id, 'farm_id' => $farmId, 'flock_id' => $flockId,
                        'movement_date' => $now->copy()->subDays($day)->toDateString(), 'item_type' => $itemType,
                        'movement_type' => $movementType, 'quantity' => $quantity, 'unit' => $unit,
                        'unit_cost' => 0, 'total_value' => 0, 'source_type' => \App\Models\LivestockDailyProduction::class,
                        'source_id' => $productionId, 'notes' => 'Controlled demo movement', 'created_by' => $user->id,
                        'created_at' => $now, 'updated_at' => $now,
                    ]));
                }
            }
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
