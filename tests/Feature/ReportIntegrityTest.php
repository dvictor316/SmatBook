<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Account;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_summary_excludes_cancelled_sales_and_uses_applied_payments(): void
    {
        $this->withoutExceptionHandling();
        [$user, $company] = $this->businessUser();

        $sale = $this->sale($user, $company, 'INV-REPORT-1', 100, 'completed');
        Payment::query()->create([
            'sale_id' => $sale->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'amount' => 40,
            'method' => 'Cash',
            'status' => 'Completed',
            'created_by' => $user->id,
        ]);
        Payment::query()->create([
            'sale_id' => $sale->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'amount' => 50,
            'method' => 'Transfer',
            'status' => 'Pending',
            'created_by' => $user->id,
        ]);
        $this->sale($user, $company, 'INV-REPORT-2', 500, 'cancelled');

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession($this->branchSession($company))
            ->get(route('reports.sales-summary', [
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewHas('totalSales', 100.0)
            ->assertViewHas('totalPaid', 40.0)
            ->assertViewHas('totalBalance', 60.0)
            ->assertViewHas('totalCount', 1);
    }

    public function test_empty_branch_payment_summary_does_not_fall_back_to_company_wide_data(): void
    {
        $this->withoutExceptionHandling();
        [$user, $company] = $this->businessUser();
        $sale = $this->sale($user, $company, 'INV-BRANCH-1', 250, 'completed', 'branch-a', 'Branch A');

        Payment::query()->create([
            'sale_id' => $sale->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'branch-a',
            'branch_name' => 'Branch A',
            'amount' => 250,
            'method' => 'Cash',
            'status' => 'Completed',
            'created_by' => $user->id,
        ]);

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession([
                'current_tenant_id' => $company->id,
                'active_branch_id' => 'branch-b',
                'active_branch_name' => 'Branch B',
                'active_branch_scope' => 'branch',
            ])
            ->get(route('reports.payment-summary'));

        $response->assertOk()
            ->assertViewHas('totalRevenue', 0.0)
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 0);
    }

    public function test_dashboard_inventory_assets_match_stock_valuation_report_at_cost(): void
    {
        $this->withoutExceptionHandling();
        DB::connection()->getPdo()->sqliteCreateFunction('MONTHNAME', fn ($date) => date('F', strtotime($date)), 1);
        DB::connection()->getPdo()->sqliteCreateFunction('MONTH', fn ($date) => (int) date('n', strtotime($date)), 1);
        [$user, $company] = $this->businessUser();
        $otherCompany = Company::withoutGlobalScope('tenant')->create(['name' => 'Other Business']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Valuation', 'created_at' => now(), 'updated_at' => now()]);
        $product = DB::table('products')->insertGetId([
            'company_id' => $company->id, 'user_id' => $user->id, 'category_id' => $categoryId,
            'name' => 'Branch Product', 'sku' => 'VALUATION-BRANCH',
            'price' => 200, 'purchase_price' => 100, 'stock' => 10, 'stock_quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_branch_stocks')->insert([
            ['product_id' => $product, 'company_id' => $company->id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'quantity' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $product, 'company_id' => $company->id, 'branch_id' => 'other', 'branch_name' => 'Other Branch', 'quantity' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('products')->insert([
            ['company_id' => $company->id, 'user_id' => $user->id, 'category_id' => $categoryId, 'name' => 'Unseeded Product', 'sku' => 'VALUATION-FALLBACK', 'price' => 90, 'purchase_price' => 50, 'stock' => 2, 'stock_quantity' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company->id, 'user_id' => $user->id, 'category_id' => $categoryId, 'name' => 'No Cost Product', 'sku' => 'VALUATION-NO-COST', 'price' => 400, 'purchase_price' => 0, 'stock' => 1, 'stock_quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $otherCompany->id, 'user_id' => $user->id, 'category_id' => $categoryId, 'name' => 'Other Tenant Product', 'sku' => 'VALUATION-OTHER', 'price' => 900, 'purchase_price' => 500, 'stock' => 4, 'stock_quantity' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $report = $this->get(route('reports.stock-valuation'));
        $report->assertOk()->assertViewHas('totalValue', 800.0)->assertViewHas('totalQty', 10.0);

        $dashboard = $this->get(route('workspace.business.dashboard'));
        $dashboard->assertOk();
        $this->assertSame(800.0, (float) $dashboard->viewData('metrics')['inventoryValue']);
        $this->assertSame(10.0, (float) $dashboard->viewData('metrics')['activeStock']);

        $this->withSession(['active_branch_scope' => 'all']);
        $allBranchesReport = $this->get(route('reports.stock-valuation'));
        $allBranchesReport->assertOk()->assertViewHas('totalValue', 1100.0)->assertViewHas('totalQty', 13.0);

        $allBranchesDashboard = $this->get(route('workspace.business.dashboard'));
        $allBranchesDashboard->assertOk();
        $this->assertSame(1100.0, (float) $allBranchesDashboard->viewData('metrics')['inventoryValue']);
        $this->assertSame(13.0, (float) $allBranchesDashboard->viewData('metrics')['activeStock']);

        DB::table('product_branch_stocks')->where('product_id', $product)->where('branch_id', 'hq')->update(['quantity' => 5]);
        $this->withSession($this->branchSession($company));
        $updatedReport = $this->get(route('reports.stock-valuation'));
        $updatedReport->assertOk()->assertViewHas('totalValue', 600.0)->assertViewHas('totalQty', 8.0);

        $updatedDashboard = $this->get(route('workspace.business.dashboard'));
        $updatedDashboard->assertOk();
        $this->assertSame(600.0, (float) $updatedDashboard->viewData('metrics')['inventoryValue']);
        $this->assertSame(8.0, (float) $updatedDashboard->viewData('metrics')['activeStock']);
    }

    public function test_expected_stock_profit_uses_branch_stock_and_excludes_missing_prices(): void
    {
        [$user, $company] = $this->businessUser();
        $otherCompany = Company::withoutGlobalScope('tenant')->create(['name' => 'Other Business']);
        $product = DB::table('products')->insertGetId([
            'company_id' => $company->id, 'user_id' => $user->id, 'name' => 'Retail Product',
            'sku' => 'EXPECTED-1', 'price' => 200, 'purchase_price' => 100, 'stock' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_branch_stocks')->insert([
            ['product_id' => $product, 'company_id' => $company->id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'quantity' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $product, 'company_id' => $company->id, 'branch_id' => 'other', 'branch_name' => 'Other', 'quantity' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('products')->insert([
            ['company_id' => $company->id, 'user_id' => $user->id, 'name' => 'Missing Cost', 'sku' => 'EXPECTED-2', 'price' => 400, 'purchase_price' => 0, 'stock' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $otherCompany->id, 'user_id' => $user->id, 'name' => 'Other Tenant', 'sku' => 'EXPECTED-3', 'price' => 900, 'purchase_price' => 500, 'stock' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->get(route('reports.expected-stock-profit'))->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['quantity'] === 8.0
                && $summary['cost'] === 700.0 && $summary['revenue'] === 1400.0
                && $summary['profit'] === 700.0 && $summary['incomplete_products'] === 1);

        $this->withSession(['active_branch_scope' => 'all']);
        $this->get(route('reports.expected-stock-profit'))->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['quantity'] === 11.0
                && $summary['cost'] === 1000.0 && $summary['revenue'] === 2000.0
                && $summary['profit'] === 1000.0 && $summary['incomplete_products'] === 1);
    }

    public function test_offline_sale_cannot_sync_to_another_branch_or_business(): void
    {
        [$user, $company] = $this->businessUser();
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));

        $payload = [
            'client_sale_id' => 'offline-context-test',
            'offline_company_id' => $company->id,
            'offline_branch_id' => 'other',
            'offline_branch_name' => 'Other',
        ];
        $this->postJson(route('sales.store'), $payload)->assertStatus(409);
        $this->assertDatabaseMissing('sales', ['client_sale_id' => 'offline-context-test']);

        $payload['offline_branch_id'] = 'hq';
        $payload['offline_company_id'] = $company->id + 1;
        $this->postJson(route('sales.store'), $payload)->assertStatus(409);
        $this->assertDatabaseMissing('sales', ['client_sale_id' => 'offline-context-test']);

        $payload['offline_company_id'] = $company->id;
        $this->postJson(route('sales.store'), $payload)->assertUnprocessable();
    }

    public function test_pos_split_sale_records_each_payment_channel_and_account(): void
    {
        [$user, $company] = $this->businessUser();
        $cashAccount = $this->account($company, 'POS Cash Drawer', 'Asset', 'Cash');
        $transferAccount = $this->account($company, 'POS Transfer Bank', 'Asset', 'Bank');
        $cardAccount = $this->account($company, 'POS Card Settlement', 'Asset', 'Bank');
        foreach ([$cashAccount, $transferAccount, $cardAccount] as $account) {
            $account->forceFill(['branch_id' => 'hq', 'branch_name' => 'Headquarters'])->saveQuietly();
        }
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'POS Payment Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'category_id' => $categoryId,
            'name' => 'POS Split Product',
            'sku' => 'POS-SPLIT-PAYMENT-TEST',
            'price' => 1000,
            'purchase_price' => 500,
            'stock' => 5,
            'stock_quantity' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession($this->branchSession($company))
            ->postJson(route('sales.store'), [
                'payment_method' => 'Split',
                'total' => 1000,
                'paid' => 1000,
                'deposit_account_id' => $cashAccount->id,
                'items' => [[
                    'id' => $productId,
                    'qty' => 1,
                    'price' => 1000,
                    'unitType' => 'unit',
                    'stockUnits' => 1,
                ]],
                'split_details' => [
                    'cash' => 100,
                    'transfer' => 300,
                    'transfer_account_id' => $transferAccount->id,
                    'card' => 600,
                    'card_account_id' => $cardAccount->id,
                ],
            ]);
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJsonPath('success', true);

        $sale = Sale::query()->findOrFail($response->json('sale_id'));
        $payments = Payment::query()
            ->where('sale_id', $sale->id)
            ->get()
            ->keyBy('method');

        $this->assertEquals(100.0, (float) $payments['Cash']->amount);
        $this->assertEquals($cashAccount->id, (int) $payments['Cash']->account_id);
        $this->assertEquals(300.0, (float) $payments['Bank Transfer']->amount);
        $this->assertEquals($transferAccount->id, (int) $payments['Bank Transfer']->account_id);
        $this->assertEquals(600.0, (float) $payments['POS/Card']->amount);
        $this->assertEquals($cardAccount->id, (int) $payments['POS/Card']->account_id);

        foreach ([
            ['channel' => 'transfer', 'method' => 'Bank Transfer', 'account' => $transferAccount, 'label' => 'Bank Transfer'],
            ['channel' => 'card', 'method' => 'POS/Card', 'account' => $cardAccount, 'label' => 'POS/Card'],
        ] as $case) {
            $split = ['cash' => 0, 'transfer' => 0, 'card' => 0];
            $split[$case['channel']] = 1000;
            $split[$case['channel'].'_account_id'] = $case['account']->id;

            $response = $this->postJson(route('sales.store'), [
                'payment_method' => 'Split',
                'total' => 1000,
                'paid' => 1000,
                'items' => [[
                    'id' => $productId, 'qty' => 1, 'price' => 1000,
                    'unitType' => 'unit', 'stockUnits' => 1,
                ]],
                'split_details' => $split,
            ]);
            $this->assertSame(200, $response->status(), $response->getContent());
            $channelSale = Sale::query()->findOrFail($response->json('sale_id'));
            $this->assertSame($case['channel'], $channelSale->payment_method);
            $channelPayments = Payment::query()->where('sale_id', $channelSale->id)->get();
            $this->assertCount(1, $channelPayments);
            $this->assertSame($case['method'], $channelPayments->first()->method);
            $this->assertSame($case['account']->id, (int) $channelPayments->first()->account_id);

            $this->get(route('sales.invoice.print', $channelSale->id))
                ->assertOk()->assertSee($case['label'])->assertSee($case['account']->name);
            $this->get(route('sales.invoice.print', ['id' => $channelSale->id, 'format' => 'thermal', 'paper' => 58]))
                ->assertOk()->assertSee(strtoupper($case['label']))->assertSee($case['account']->name);
        }
    }

    public function test_general_ledger_includes_company_system_entries_with_no_user(): void
    {
        $this->withoutExceptionHandling();
        [$user, $company] = $this->businessUser();
        $account = Account::query()->create([
            'company_id' => $company->id,
            'name' => 'Cash at Bank',
            'code' => '1000',
            'type' => Account::TYPE_ASSET,
            'sub_type' => 'Bank',
            'is_active' => true,
        ]);

        DB::table('transactions')->insert([
            'account_id' => $account->id,
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'transaction_date' => now()->toDateString(),
            'reference' => 'SYS-REPORT-1',
            'description' => 'System posting',
            'debit' => 75,
            'credit' => 0,
            'transaction_type' => 'Adjustment',
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession($this->branchSession($company))
            ->get(route('general-ledger'));

        $response->assertOk()
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 1)
            ->assertViewHas('totals', fn (array $totals) => $totals['debit'] === 75.0 && $totals['credit'] === 0.0);
    }

    public function test_read_only_report_catalogue_renders_without_server_errors(): void
    {
        $this->withoutExceptionHandling();
        [$user, $company] = $this->businessUser();
        $this->withoutReportAccessMiddleware()->actingAs($user);

        $routes = [
            'reports.hub',
            'reports.expense',
            'reports.income',
            'reports.payment',
            'reports.purchase',
            'reports.sales',
            'reports.stock',
            'reports.low-stock',
            'reports.accounts-receivable',
            'reports.quotation',
            'reports.profit-loss',
            'reports.tax-purchase',
            'reports.tax-sales',
            'reports.sales-return',
            'reports.purchase-return',
            'reports.expiry-report',
            'reports.payment-summary',
            'reports.profit-loss-comparison',
            'reports.profit-loss-by-month',
            'reports.profit-loss-detail',
            'reports.ar-ageing-detail',
            'reports.open-invoices',
            'reports.sales-by-customer',
            'reports.sales-by-product',
            'reports.sales-summary',
            'reports.purchase-by-supplier',
            'reports.purchase-summary',
            'reports.expense-by-category',
            'reports.expense-trend',
            'reports.stock-valuation',
            'reports.stock-by-category',
            'reports.tax-summary',
            'reports.cash-flow',
            'balance-sheet',
            'balance-sheet-summary',
            'balance-sheet-comparison',
            'trial-balance',
            'reports.chart-of-accounts',
            'general-ledger',
            'reports.financial-ratios',
        ];

        foreach ($routes as $routeName) {
            $response = $this->withSession($this->branchSession($company))->get(route($routeName));
            $this->assertLessThan(500, $response->getStatusCode(), "{$routeName} returned a server error.");
            $this->assertNotSame(404, $response->getStatusCode(), "{$routeName} was not found.");
        }
    }

    public function test_financial_ratios_are_calculated_from_scoped_ledger_balances(): void
    {
        [$user, $company] = $this->businessUser();
        $accounts = [
            'cash' => $this->account($company, 'Cash at Bank', 'Asset', 'Bank'),
            'inventory' => $this->account($company, 'Inventory', 'Asset', 'Inventory'),
            'receivables' => $this->account($company, 'Trade Receivables', 'Asset', 'Accounts Receivable'),
            'payables' => $this->account($company, 'Trade Payables', 'Liability', 'Accounts Payable'),
            'revenue' => $this->account($company, 'Sales Revenue', 'Revenue', 'Sales Revenue'),
            'cogs' => $this->account($company, 'Cost of Goods Sold', 'Expense', 'Cost of Sales'),
            'opex' => $this->account($company, 'Administrative Expense', 'Expense', 'Operating Expense'),
            'interest' => $this->account($company, 'Interest Expense', 'Expense', 'Finance Cost'),
        ];

        $this->ledgerEntry($company, $accounts['cash'], 200, 0);
        $this->ledgerEntry($company, $accounts['inventory'], 100, 0);
        $this->ledgerEntry($company, $accounts['receivables'], 100, 0);
        $this->ledgerEntry($company, $accounts['payables'], 0, 100);
        $this->ledgerEntry($company, $accounts['revenue'], 0, 400);
        $this->ledgerEntry($company, $accounts['cogs'], 100, 0);
        $this->ledgerEntry($company, $accounts['opex'], 50, 0);
        $this->ledgerEntry($company, $accounts['interest'], 10, 0);

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession($this->branchSession($company))
            ->get(route('reports.financial-ratios', ['period' => 'ytd']));

        $response->assertOk()
            ->assertViewHas('revenue', 400.0)
            ->assertViewHas('netIncome', 240.0)
            ->assertViewHas('ratios', function (array $ratios) {
                return $ratios['current_ratio'] === 4.0
                    && $ratios['quick_ratio'] === 3.0
                    && $ratios['cash_ratio'] === 2.0
                    && $ratios['working_capital'] === 300.0
                    && $ratios['gross_margin'] === 75.0
                    && $ratios['net_margin'] === 60.0
                    && $ratios['debt_to_equity'] === 0.33
                    && $ratios['inventory_turnover'] === 1.0;
            });
    }

    public function test_financial_report_exports_are_generated_successfully(): void
    {
        [$user, $company] = $this->businessUser();
        $this->withoutReportAccessMiddleware()->actingAs($user);
        $session = $this->branchSession($company);

        $cashFlow = $this->withSession($session)->get(route('reports.cash-flow.export'));
        $cashFlow->assertOk();
        $this->assertStringContainsString('Cash Flow Report', $cashFlow->streamedContent());

        $this->withSession($session)->get(route('balance-sheet.export'))->assertDownload();
        $this->withSession($session)->get(route('trial-balance.export'))->assertDownload();
    }

    private function businessUser(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => 'Report Company',
            'status' => 'active',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();

        return [$user, $company];
    }

    private function sale(
        User $user,
        Company $company,
        string $invoice,
        float $total,
        string $orderStatus,
        string $branchId = 'hq',
        string $branchName = 'Headquarters'
    ): Sale {
        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'invoice_no' => $invoice,
            'customer_name' => 'Report Customer',
            'user_id' => $user->id,
            'subtotal' => $total,
            'total' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'payment_status' => 'unpaid',
            'order_date' => now()->toDateString(),
        ]);

        $sale->forceFill(['status' => $orderStatus])->saveQuietly();

        return $sale->fresh();
    }

    private function account(Company $company, string $name, string $type, string $subType): Account
    {
        return Account::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'code' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => $type,
            'sub_type' => $subType,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function ledgerEntry(Company $company, Account $account, float $debit, float $credit): void
    {
        DB::table('transactions')->insert([
            'account_id' => $account->id,
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'transaction_date' => now()->toDateString(),
            'reference' => 'RATIO-'.$account->id,
            'description' => 'Ratio test posting',
            'debit' => $debit,
            'credit' => $credit,
            'transaction_type' => 'Journal Entry',
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function branchSession(Company $company): array
    {
        return [
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
            'active_branch_scope' => 'branch',
        ];
    }

    private function withoutReportAccessMiddleware(): self
    {
        return $this->withoutMiddleware([
            SubscriptionActive::class,
            RequireActiveBranch::class,
            CheckPlanAccess::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ]);
    }
}
