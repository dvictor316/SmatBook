<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoProvisioningService
{
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
            $slug = 'demo-' . Str::slug($demoRequest->company_name) . '-' . Str::random(5);

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
                'demo_expires_at' => now()->addHours(48),
                'domain_prefix'   => $slug,
                'domain'          => $slug,
                'subdomain'       => $slug,
                'plan'            => 'Demo',
                'industry'        => $demoRequest->business_type ?? 'General',
            ]));

            // 2. Create the demo user (owner of the demo company)
            $user = User::create($this->onlyExistingColumns('users', [
                'name'              => $demoRequest->full_name,
                'email'             => $demoRequest->email,
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

            // 3. Seed demo data (products, customers, transactions)
            $this->seedDemoData($company, $user);

            // 4. Audit log
            ActivityLog::record('Demo', 'provisioned', "Demo account provisioned for {$demoRequest->email}", [
                'company_id' => $company->id,
                'user_id'    => $user->id,
                'properties' => ['demo_request_id' => $demoRequest->id],
            ]);

            return [
                'company'        => $company,
                'user'           => $user,
                'plain_password' => $plainPassword,
            ];
        });
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

            $saleId = DB::table('sales')->insertGetId($this->onlyExistingColumns('sales', [
                'total'         => $totalAmount,
                'total_amount'  => $totalAmount,
                'status'        => 'completed',
                'payment_status'=> 'paid',
                'company_id'    => $companyId,
                'user_id'       => $user->id,
                'customer_id'   => $customerId,
                'customer_name' => $customerName,
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
            DB::table('expenses')->insert($this->onlyExistingColumns('expenses', [
                'description' => $expenseName . ' (Demo)',
                'amount'      => rand(5000, 50000),
                'category'    => 'Operating',
                'company_id'  => $companyId,
                'user_id'     => $user->id,
                'created_by'  => $user->id,
                'created_at'  => now()->subDays($i + 1),
                'updated_at'  => now()->subDays($i + 1),
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
}
