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
