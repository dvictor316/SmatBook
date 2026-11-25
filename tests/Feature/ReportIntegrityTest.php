<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Account;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LedgerService;
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

    public function test_sales_by_product_lists_all_tenant_products_and_keeps_same_named_products_separate(): void
    {
        [$user, $company] = $this->businessUser();
        $otherCompany = Company::withoutGlobalScope('tenant')->create(['name' => 'Other Report Company']);
        $productIds = [];
        foreach (['Shared Name', 'Shared Name', 'Another Product', 'No Sales Product'] as $index => $name) {
            $productIds[] = DB::table('products')->insertGetId([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'name' => $name,
                'sku' => 'SALES-BY-PRODUCT-'.$index,
                'price' => 100,
                'purchase_price' => 50,
                'stock' => 5,
                'stock_quantity' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('products')->insert([
            'company_id' => $otherCompany->id,
            'user_id' => $user->id,
            'name' => 'Other Tenant Product',
            'sku' => 'SALES-BY-PRODUCT-OTHER',
            'price' => 100,
            'purchase_price' => 50,
            'stock' => 5,
            'stock_quantity' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sale = $this->sale($user, $company, 'INV-SALES-BY-PRODUCT', 500, 'completed');
        DB::table('sale_items')->insert([
            ['sale_id' => $sale->id, 'product_id' => $productIds[0], 'qty' => 2, 'quantity' => 2, 'unit_price' => 100, 'total_price' => 200, 'created_at' => now(), 'updated_at' => now()],
            ['sale_id' => $sale->id, 'product_id' => $productIds[1], 'qty' => 3, 'quantity' => 3, 'unit_price' => 100, 'total_price' => 300, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession($this->branchSession($company))
            ->get(route('reports.sales-by-product', [
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $rows = $response->viewData('rows');
        $this->assertTrue($rows->count() === 4
                && (float) $rows->firstWhere('product_id', $productIds[0])->qty_sold === 2.0
                && (float) $rows->firstWhere('product_id', $productIds[1])->qty_sold === 3.0
                && (float) $rows->firstWhere('product_id', $productIds[2])->qty_sold === 0.0
                && (float) $rows->firstWhere('product_id', $productIds[3])->revenue === 0.0,
            json_encode($rows->toArray(), JSON_PRETTY_PRINT));

        $sale->forceFill(['customer_name' => 'Mararaba Test Buyer'])->save();
        $filtered = $this->get(route('reports.sales-by-product', [
            'sales_rep' => $user->name,
            'customer' => 'test buyer',
            'product' => 'PRODUCT-1',
        ]))->assertOk()->viewData('rows');
        $this->assertCount(1, $filtered);
        $this->assertEquals($productIds[1], $filtered->first()->product_id);
        $this->assertEquals(300, $filtered->first()->revenue);

        $secondRep = User::factory()->create(['company_id' => $company->id, 'name' => 'Second Sales Rep']);
        $secondSale = $this->sale($secondRep, $company, 'INV-SECOND-REP-PRODUCT', 75, 'completed');
        $secondSale->forceFill(['customer_name' => 'Second Buyer'])->save();
        DB::table('sale_items')->insert([
            'sale_id' => $secondSale->id, 'product_id' => $productIds[1], 'qty' => 1, 'quantity' => 1,
            'unit_price' => 75, 'total_price' => 75,
        ]);
        $byRep = $this->get(route('reports.sales-by-product', ['group_by' => 'sales_rep', 'product' => 'PRODUCT-1']))
            ->assertOk()->viewData('rows');
        $this->assertEquals(300, $byRep->firstWhere('report_person', $user->name)->revenue);
        $this->assertEquals(75, $byRep->firstWhere('report_person', $secondRep->name)->revenue);

        $byCustomer = $this->get(route('reports.sales-by-product', ['group_by' => 'customer', 'product' => 'PRODUCT-1']))
            ->assertOk()->viewData('rows');
        $this->assertEquals(300, $byCustomer->firstWhere('report_person', 'Mararaba Test Buyer')->revenue);
        $this->assertEquals(75, $byCustomer->firstWhere('report_person', 'Second Buyer')->revenue);
    }

    public function test_customer_and_product_sales_reports_show_date_filtered_gross_profit(): void
    {
        [$user, $company] = $this->businessUser();
        $productId = DB::table('products')->insertGetId([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => 'Profit Report Product',
            'sku' => 'PROFIT-REPORT-1',
            'price' => 200,
            'purchase_price' => 70,
            'stock' => 10,
            'stock_quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sale = $this->sale($user, $company, 'INV-PROFIT-REPORT', 550, 'completed');
        DB::table('sales')->where('id', $sale->id)->update([
            'customer_name' => 'Profit Customer',
            'order_date' => now()->toDateString(),
        ]);
        DB::table('sale_items')->insert([
            [
                'sale_id' => $sale->id,
                'product_id' => $productId,
                'qty' => 2,
                'quantity' => 2,
                'unit_price' => 200,
                'subtotal' => 400,
                'total_price' => 400,
                'unit_cost' => 125,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sale_id' => $sale->id,
                'product_id' => $productId,
                'qty' => 1,
                'quantity' => 1,
                'unit_price' => 100,
                'subtotal' => 100,
                'total_price' => 100,
                'unit_cost' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $filters = [
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ];
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));

        $customerResponse = $this->get(route('reports.sales-by-customer', $filters));
        $customerResponse->assertOk();
        $customerRow = $customerResponse->viewData('rows')->firstWhere('customer', 'Profit Customer');
        $this->assertNotNull($customerRow);
        $this->assertSame(550.0, (float) $customerRow->total_amount);
        $this->assertSame(180.0, (float) $customerRow->gross_profit);
        $this->assertSame(1, $customerRow->estimated_cost_lines);

        $productResponse = $this->get(route('reports.sales-by-product', $filters));
        $productResponse->assertOk();
        $productRow = $productResponse->viewData('rows')->firstWhere('product_id', $productId);
        $this->assertNotNull($productRow);
        $this->assertSame(3.0, (float) $productRow->qty_sold);
        $this->assertSame(500.0, (float) $productRow->revenue);
        $this->assertSame(320.0, (float) $productRow->cost);
        $this->assertSame(180.0, (float) $productRow->gross_profit);
        $this->assertSame(1, $productRow->estimated_cost_lines);
    }

    public function test_sales_return_restores_posted_inventory_cost_and_keeps_its_journal_after_archiving_the_product(): void
    {
        [$user, $company] = $this->businessUser();
        $productId = DB::table('products')->insertGetId([
            'company_id' => $company->id, 'user_id' => $user->id, 'branch_id' => 'hq',
            'branch_name' => 'Headquarters', 'name' => 'Returned product', 'sku' => 'RETURN-COST',
            'price' => 500, 'purchase_price' => 250, 'stock' => 1, 'stock_quantity' => 1,
        ]);
        $sale = $this->sale($user, $company, 'INV-RETURN-COGS', 500, 'completed');
        DB::table('sale_items')->insert([
            'sale_id' => $sale->id, 'product_id' => $productId, 'qty' => 1, 'quantity' => 1,
            'unit_price' => 500, 'total_price' => 500, 'unit_cost' => 250,
        ]);
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        LedgerService::postSale($sale);

        $this->post(route('reports.credit-notes-store'), [
            'invoice_id' => $sale->id, 'credit_date' => now()->toDateString(), 'items' => [$productId => ['qty' => 1]],
        ])->assertSessionHas('success');

        $creditNote = DB::table('credit_notes')->where('sale_id', $sale->id)->first();
        $journal = DB::table('transactions')->where('reference', 'CN-'.$creditNote->id.'-COGS');
        $this->assertEquals(250, $journal->sum('debit'));
        $this->assertEquals(250, $journal->sum('credit'));

        Product::withoutGlobalScopes()->findOrFail($productId)->delete();
        $this->assertSame(2, $journal->count());
    }

    public function test_bank_zero_recovery_previews_and_restores_only_the_audited_offset(): void
    {
        [$user, $company] = $this->businessUser();
        $bank = $this->account($company, 'Moniepoint MFB', 'Asset', 'Bank');
        $offset = $this->account($company, 'Bank Balance Clearing', 'Equity', 'Reconciliation Reserve');
        $reference = "BANKZERO-{$bank->id}-20261019115859";
        $args = ['company' => $company->id, 'reference' => $reference];
        DB::table('transactions')->insert([
            'company_id' => $company->id, 'account_id' => $bank->id, 'transaction_date' => '2026-10-19',
            'transaction_type' => Transaction::TYPE_JOURNAL, 'reference' => $reference,
            'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'debit' => 0, 'credit' => 2000000,
            'description' => 'Bank balance zeroing journal for Moniepoint MFB. Reason: Test',
            'user_id' => $user->id, 'created_at' => now(),
        ]);
        $this->artisan('ledger:repair-bank-zero', $args + ['--apply' => true])->assertFailed();
        \App\Models\ActivityLog::record('BankBalanceManagement', 'bank_balance_zero_journal', 'Bank balance management action executed for Moniepoint MFB. Reason: Test', [
            'user_id' => $user->id, 'company_id' => $company->id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters',
            'properties' => ['reference' => $reference, 'account_id' => $bank->id, 'offset_account_id' => $offset->id,
                'offset_account_name' => $offset->name, 'old_balance' => 2000000],
        ]);
        $this->artisan('ledger:repair-bank-zero', $args)->expectsOutputToContain('Preview only.')->assertSuccessful();
        $this->assertSame(1, DB::table('transactions')->where('reference', $reference)->count());
        $this->artisan('ledger:repair-bank-zero', $args + ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseHas('transactions', ['reference' => $reference, 'account_id' => $offset->id, 'debit' => 2000000, 'credit' => 0]);
        $this->artisan('ledger:repair-bank-zero', $args + ['--apply' => true])
            ->expectsOutput('Both posted journal legs are present and balanced.')->assertSuccessful();
        $this->assertSame(2, DB::table('transactions')->where('reference', $reference)->count());
    }

    public function test_bank_settings_posts_an_opening_and_only_the_verified_legacy_opening_enters_both_statements(): void
    {
        [$user, $company] = $this->businessUser();
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->post(route('settings.bank-account.store'), [
            'bank_name' => 'Opening Bank', 'account_number' => '1234025000', 'opening_balance' => '400000.00',
        ])->assertSessionHas('success');
        $this->get(route('bank-account'))->assertOk()
            ->assertSee('modal-dialog-scrollable modal-md bank-account-dialog', false)
            ->assertSee('class="modal-content bank-account-form"', false)
            ->assertSee('aria-label="Bank details fields"', false);
        $bank = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Opening Bank')->sole();
        $opening = DB::table('transactions')->where('reference', 'OB-ACCT-'.$bank->id);
        $this->assertEquals(400000, $opening->sum('debit'));
        $this->assertEquals(400000, $opening->sum('credit'));
        $diagnostic = app(\App\Services\BankBalanceManagementService::class)->buildDiagnostic($bank);
        $this->assertEquals(400000, $diagnostic['derived_ledger_balance']);

        $legacy = $this->account($company, 'Access Bank', 'Asset', 'Bank');
        $legacy->forceFill(['code' => 'AST-99750', 'opening_balance' => 1000000, 'branch_id' => 'hq', 'branch_name' => 'Headquarters'])->save();
        $other = Company::withoutGlobalScopes()->create(['name' => 'Other Opening Business', 'plan' => 'Enterprise']);
        $otherAccount = $this->account($other, 'Other Legacy Bank', 'Asset', 'Bank');
        $otherAccount->forceFill(['opening_balance' => 2000000])->save();
        $this->get(route('balance-sheet'))->assertOk()
            ->assertViewHas('statementDifference', 0.0)->assertViewHas('totalAssets', 400000.0)
            ->assertSee('Opening balances awaiting review')->assertSee('AST-99750');
        $this->get(route('trial-balance'))->assertOk()
            ->assertViewHas('totalDebits', 400000.0)->assertViewHas('totalCredits', 400000.0)
            ->assertSee('Opening balances awaiting review')->assertSee('AST-99750');

        $command = ['--company' => $company->id, '--account' => $legacy->id, '--date' => now()->toDateString()];
        $this->artisan('ledger:audit-openings', ['--all-companies' => true])->assertSuccessful();
        $this->assertDatabaseMissing('transactions', ['reference' => 'OB-ACCT-'.$legacy->id]);
        $this->artisan('ledger:audit-openings', $command + ['--amount' => '900000', '--apply' => true])->assertFailed();
        $this->artisan('ledger:audit-openings', $command + ['--amount' => '1000000', '--apply' => true])->assertSuccessful();
        $this->artisan('ledger:audit-openings', $command + ['--amount' => '1000000', '--apply' => true])->assertFailed();
        $this->assertDatabaseMissing('transactions', ['reference' => 'OB-ACCT-'.$otherAccount->id]);
        $this->get(route('balance-sheet'))->assertOk()->assertViewHas('statementDifference', 0.0)
            ->assertViewHas('totalAssets', 1400000.0)->assertDontSee('Opening balances awaiting review');
        $this->get(route('trial-balance'))->assertOk()
            ->assertViewHas('totalDebits', 1400000.0)->assertViewHas('totalCredits', 1400000.0)
            ->assertDontSee('Opening balances awaiting review');
    }

    public function test_discarding_an_incorrect_saved_opening_clears_the_notice_without_changing_posted_balances(): void
    {
        [$user, $company] = $this->businessUser();
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $bank = $this->account($company, 'Access Bank', 'Asset', 'Bank');
        $bank->forceFill(['code' => 'AST-99750', 'opening_balance' => 1000000, 'current_balance' => 1015000])->save();
        $equity = $this->account($company, 'Capital', 'Equity', 'Capital');
        $this->ledgerEntry($company, $bank, 15000, 0);
        $this->ledgerEntry($company, $equity, 0, 15000);
        $before = DB::table('transactions')->orderBy('id')->get()->toArray();
        $arguments = ['--company' => $company->id, '--account' => $bank->id, '--discard' => true, '--reason' => 'Confirmed incorrect test opening'];

        $this->artisan('ledger:audit-openings', $arguments + ['--amount' => '999999'])->assertFailed();
        $this->assertEquals(1000000, $bank->fresh()->opening_balance);
        $this->artisan('ledger:audit-openings', $arguments + ['--amount' => '1000000'])->assertSuccessful();
        $this->assertDatabaseHas('accounts', ['id' => $bank->id, 'opening_balance' => 0, 'current_balance' => 15000]);
        $this->assertEquals($before, DB::table('transactions')->orderBy('id')->get()->toArray());
        $this->get(route('balance-sheet'))->assertOk()->assertViewHas('totalAssets', 15000.0)
            ->assertDontSee('Opening balances awaiting review');
        $this->get(route('trial-balance'))->assertOk()->assertViewHas('totalDebits', 15000.0)
            ->assertViewHas('totalCredits', 15000.0)->assertDontSee('Opening balances awaiting review');
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
                'deposit_account_id' => $transferAccount->id,
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
        $this->assertSame($cardAccount->id, (int) $sale->payment_details['card_account_id']);

        $cashResponse = $this->postJson(route('sales.store'), [
            'payment_method' => 'Cash',
            'total' => 1000,
            'paid' => 1000,
            'deposit_account_id' => $transferAccount->id,
            'items' => [[
                'id' => $productId, 'qty' => 1, 'price' => 1000,
                'unitType' => 'unit', 'stockUnits' => 1,
            ]],
        ]);
        $this->assertSame(200, $cashResponse->status(), $cashResponse->getContent());
        $cashSale = Sale::query()->findOrFail($cashResponse->json('sale_id'));
        $this->assertSame($cashAccount->id, (int) $cashSale->payment_details['payment_account_id']);
        $this->assertSame($cashAccount->id, (int) Payment::query()->where('sale_id', $cashSale->id)->firstOrFail()->account_id);

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
                ->assertOk()->assertSee($case['label'])->assertDontSee($case['account']->name);
            $this->get(route('sales.invoice.print', ['id' => $channelSale->id, 'format' => 'thermal', 'paper' => 58]))
                ->assertOk()->assertSee(strtoupper($case['label']))->assertDontSee($case['account']->name);
        }

        $this->postJson(route('sales.store'), [
            'payment_method' => 'Split',
            'total' => 1000,
            'paid' => 1000,
            'items' => [[
                'id' => $productId, 'qty' => 1, 'price' => 1000,
                'unitType' => 'unit', 'stockUnits' => 1,
            ]],
            'split_details' => [
                'cash' => 0, 'transfer' => 1000, 'transfer_account_id' => $cashAccount->id, 'card' => 0,
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('split_details.transfer_account_id');

        $cashAccount->update(['is_active' => false]);
        $this->postJson(route('sales.store'), [
            'payment_method' => 'Cash',
            'total' => 1000,
            'paid' => 1000,
            'items' => [[
                'id' => $productId, 'qty' => 1, 'price' => 1000,
                'unitType' => 'unit', 'stockUnits' => 1,
            ]],
        ])->assertUnprocessable()->assertJsonPath('success', false)
            ->assertSee('No active cash account is configured');
    }

    public function test_pos_uses_the_configured_carton_price_instead_of_multiplying_the_kg_price(): void
    {
        [$user, $company] = $this->businessUser();
        $cashAccount = $this->account($company, 'POS Carton Cash', 'Asset', 'Cash');
        $cashAccount->forceFill(['branch_id' => 'hq', 'branch_name' => 'Headquarters'])->saveQuietly();
        $product = Product::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'branch_id' => 'hq',
        'branch_name' => 'Headquarters',
        'name' => 'Rice by weight',
        'sku' => 'POS-KG-CARTON-PRICE',
        'base_unit_name' => 'kg',
        'unit_type' => 'carton',
        'units_per_carton' => 25,
        'stock' => 50,
        'price' => 100,
        'purchase_price' => 60,
        ]);
        ProductUnit::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'product_id' => $product->id,
        'unit_name' => 'kg',
        'unit_symbol' => 'kg',
        'conversion_factor' => 1,
        'is_base_unit' => true,
        'is_default_sales_unit' => true,
        'selling_price' => 100,
        'status' => 'active',
        ]);
        ProductUnit::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'product_id' => $product->id,
        'unit_name' => 'carton',
        'unit_symbol' => 'ctn',
        'conversion_factor' => 25,
        'is_base_unit' => false,
        'is_default_sales_unit' => false,
        'selling_price' => 1800,
        'status' => 'active',
        ]);

        $response = $this->withoutReportAccessMiddleware()
        ->actingAs($user)
        ->withSession($this->branchSession($company))
        ->postJson(route('sales.store'), [
            'payment_method' => 'Cash',
            'total' => 1800,
            'paid' => 1800,
            'deposit_account_id' => $cashAccount->id,
            'items' => [[
                'id' => $product->id,
                'qty' => 1,
                'price' => 0,
                'unitType' => 'carton',
                'stockUnits' => 25,
                'priceLevel' => 'retail',
            ]],
        ]);

        $this->assertSame(200, $response->status(), $response->getContent());
        $sale = Sale::query()->findOrFail($response->json('sale_id'));
        $this->assertSame(1800.0, (float) $sale->items()->sole()->unit_price);
        $this->assertSame(1800.0, (float) $sale->total);
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

        $account->delete();
        $this->ledgerEntry($company, $account, 125, 0);
        DB::table('transactions')->where('account_id', $account->id)->where('debit', 125)->update(['branch_id' => 'different']);
        $response = $this->withoutReportAccessMiddleware()
            ->actingAs($user)
            ->withSession(array_replace($this->branchSession($company), ['active_branch_scope' => 'all']))
            ->get(route('general-ledger', ['branch_scope' => 'branch', 'search' => 'Cash at Bank']));

        $response->assertOk()
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 1)
            ->assertViewHas('totals', fn (array $totals) => $totals['debit'] === 75.0 && $totals['credit'] === 0.0);
    }

    public function test_account_drill_down_and_read_only_audit_show_the_source_of_posted_balances(): void
    {
        [$user, $company] = $this->businessUser();
        $bank = $this->account($company, 'Moniepoint MFB', Account::TYPE_ASSET, 'Bank');
        $inventory = $this->account($company, 'Inventory', Account::TYPE_ASSET, 'Inventory');
        $unposted = $this->account($company, 'Unposted Access Bank', Account::TYPE_ASSET, 'Bank');
        [, $otherCompany] = $this->businessUser();
        $otherCompany->update(['name' => 'Other Audit Business']);
        $otherBank = $this->account($otherCompany, 'Other Bank', Account::TYPE_ASSET, 'Bank');
        $unposted->update(['opening_balance' => 1000000]);
        $date = now()->subMonths(2)->toDateString();
        $source = [
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'transaction_date' => $date,
            'reference' => 'PUR-AUDIT-660',
            'transaction_type' => 'Journal Entry',
            'related_type' => \App\Models\Purchase::class,
            'related_id' => 700,
        ];
        DB::table('transactions')->insert([
            $source + ['account_id' => $bank->id, 'debit' => 0, 'credit' => 660000],
            $source + ['account_id' => $inventory->id, 'debit' => 660000, 'credit' => 0],
            array_replace($source, ['branch_id' => 'other', 'branch_name' => 'Other', 'reference' => 'OTHER-BRANCH'])
                + ['account_id' => $bank->id, 'debit' => 10, 'credit' => 0],
            array_replace($source, ['company_id' => $otherCompany->id, 'reference' => 'OTHER-BUSINESS'])
                + ['account_id' => $otherBank->id, 'debit' => 10, 'credit' => 0],
            array_replace($source, ['company_id' => null, 'reference' => 'UNASSIGNED'])
                + ['account_id' => $bank->id, 'debit' => 10, 'credit' => 0],
        ]);

        $this->artisan('ledger:audit-reports', ['--company' => $company->id])
            ->expectsOutputToContain('OTHER-BRANCH')
            ->expectsOutputToContain('Unposted Access Bank')
            ->expectsOutputToContain('-660,000.00')
            ->assertSuccessful();
        $this->artisan('ledger:audit-reports', ['--all-companies' => true])
            ->expectsOutputToContain('Other Audit Business')
            ->expectsOutputToContain('1 posted row(s) have no matching business ID')
            ->assertSuccessful();
        $this->assertDatabaseCount('transactions', 5);

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->get(route('balance-sheet', ['branch_scope' => 'branch']))
            ->assertOk()->assertSee('Overdraft')->assertSee('from_inception=1');
        $this->get(route('trial-balance', ['branch_scope' => 'branch']))
            ->assertOk()->assertSee('from_inception=1');
        $this->get(route('general-ledger', ['account_id' => $bank->id, 'branch_scope' => 'branch', 'from_inception' => 1]))
            ->assertOk()
            ->assertSee('View source postings')
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 1 && $entries->first()->reference === 'PUR-AUDIT-660');
        $this->get(route('general-ledger', ['source_type' => \App\Models\Purchase::class, 'source_id' => 700, 'branch_scope' => 'branch', 'from_inception' => 1]))
            ->assertOk()
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 2);
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
            'reports.stock-movements',
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
            'reports.expected-stock-profit',
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
            $this->assertSame(200, $response->getStatusCode(), "{$routeName} did not render.");
        }
    }

    public function test_purchase_reports_count_supplier_documents_in_the_selected_branch(): void
    {
        [$user, $company] = $this->businessUser();
        $other = Company::withoutGlobalScopes()->create(['name' => 'Other purchase workspace']);
        $date = now();
        foreach ([
            [$company->id, 'HQ-RECEIPT', 'hq', 'Headquarters', 125],
            [$company->id, 'OTHER-RECEIPT', 'other', 'Headquarters', 250],
            [$company->id, 'LEGACY-RECEIPT', null, 'Other Branch', 525],
            [$company->id, 'AUTO-STK-100', 'hq', 'Headquarters', 9000],
            [$company->id, 'HIST-INV-100', 'hq', 'Headquarters', 10000],
            [$other->id, 'OTHER-TENANT-RECEIPT', 'hq', 'Headquarters', 800],
        ] as [$companyId, $reference, $branchId, $branchName, $amount]) {
            DB::table('purchases')->insert([
                'company_id' => $companyId, 'branch_id' => $branchId, 'branch_name' => $branchName,
                'purchase_no' => $reference, 'total_amount' => $amount, 'status' => 'received', 'created_at' => $date,
            ]);
        }

        $this->withoutReportAccessMiddleware()->actingAs($user)
            ->withSession(array_replace($this->branchSession($company), ['active_branch_scope' => 'all']));
        $this->get(route('reports.purchase', ['branch_scope' => 'branch']))
            ->assertOk()->assertViewHas('totalSum', 125.0);
        $this->get(route('reports.purchase-summary'))
            ->assertOk()->assertViewHas('totalAmount', 125.0)->assertViewHas('totalCount', 1);
        $this->get(route('reports.purchase-by-supplier'))
            ->assertOk()->assertViewHas('grandTotal', 125.0);
        $this->get(route('reports.purchase-summary', ['branch_scope' => 'all']))
            ->assertOk()->assertViewHas('totalAmount', 900.0)->assertViewHas('totalCount', 3);
    }

    public function test_income_includes_the_first_day_and_expense_totals_cover_every_filtered_page(): void
    {
        [$user, $company] = $this->businessUser();
        $sale = $this->sale($user, $company, 'SAME-DAY-INCOME', 125, 'completed');
        $date = $sale->order_date;
        foreach ([25, 50] as $amount) {
            Expense::create([
                'company_id' => $company->id, 'created_by' => $user->id,
                'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'expense_id' => "FILTERED-EXP-{$amount}",
                'company_name' => 'Report Vendor', 'category' => 'Utilities', 'amount' => $amount,
                'status' => 'Paid', 'payment_status' => 'paid',
            ]);
        }
        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->get(route('reports.income', ['from_date' => $date, 'to_date' => $date]))
            ->assertOk()->assertViewHas('tIn', 125)->assertViewHas('tOut', 75);
        $this->get(route('reports.expense', ['per_page' => 1, 'page' => 2, 'search' => 'Utilities']))
            ->assertOk()->assertViewHas('expenses', fn ($rows) => $rows->count() === 1 && $rows->total() === 2)
            ->assertViewHas('totalExpense', 75.0);
    }

    public function test_basic_plan_can_find_and_open_the_profit_loss_report(): void
    {
        [$user, $company] = $this->businessUser();
        $this->withoutMiddleware([
            SubscriptionActive::class,
            RequireActiveBranch::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ])->actingAs($user)->withSession($this->branchSession($company));

        $this->get(route('reports.hub'))
            ->assertOk()
            ->assertSee('Profit &amp; Loss', false);

        $this->get(route('reports.profit-loss'))->assertOk();
    }

    public function test_starter_plan_can_find_and_open_the_profit_loss_report(): void
    {
        [$user, $company] = $this->businessUser();
        $company->forceFill(['plan' => 'Starter'])->save();
        $this->withoutMiddleware([
            SubscriptionActive::class,
            RequireActiveBranch::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ])->actingAs($user)->withSession($this->branchSession($company));

        $this->get(route('reports.hub'))
            ->assertOk()
            ->assertSee('Profit &amp; Loss', false);

        $this->get(route('reports.profit-loss'))->assertOk();
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
        $accounts['cash']->update(['opening_balance' => 1000000]);
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

    public function test_cash_flow_and_chart_show_cash_accounts_and_use_the_same_ledger_balances(): void
    {
        [$user, $company] = $this->businessUser();
        $cash = $this->account($company, 'Alh Cash Account', 'Asset', 'Current Asset');
        $cash->update(['opening_balance' => 30]);
        $bank = $this->account($company, 'Operating Account', 'Asset', 'Bank');
        $bank->update(['opening_balance' => 50, 'branch_id' => 'hq', 'branch_name' => 'Headquarters']);
        $inventory = $this->account($company, 'Inventory', 'Asset', '');
        $revenue = $this->account($company, 'Sales Revenue', 'Revenue', 'Sales Revenue');
        $revenue->update(['code' => 'AUTO-REV-SALES-12345']);
        $otherBranch = $this->account($company, 'Other Branch Cash', 'Asset', 'Cash');
        $otherBranch->update(['branch_id' => 'other', 'branch_name' => 'Other']);
        LedgerService::postAccountOpeningBalance($bank);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $bank->id,
            'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'debit' => 50,
        ]);
        $this->ledgerEntry($company, $cash, 10, 0);
        $this->ledgerEntry($company, $cash, 0, 15);
        $this->ledgerEntry($company, $inventory, 900, 0);
        $this->ledgerEntry($company, $revenue, 0, 1000);
        $this->ledgerEntry($company, $otherBranch, 700, 0);
        DB::table('transactions')->where('account_id', $otherBranch->id)
            ->update(['branch_id' => 'other', 'branch_name' => 'Other']);
        DB::table('transactions')->where('account_id', $cash->id)->where('credit', 15)
            ->update(['branch_id' => 'other', 'branch_name' => 'Other']);
        $this->ledgerEntry($company, $cash, 0, 45);

        $this->withoutMiddleware([
            SubscriptionActive::class,
            RequireActiveBranch::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ])->actingAs($user)->withSession($this->branchSession($company));
        config(['tenancy.enforce_scopes_in_console' => true]);

        $this->get(route('reports.hub'))->assertOk()->assertSee('Cash Flow');
        $this->get(route('chart-of-accounts'))->assertOk()
            ->assertSee('Cash on Hand')
            ->assertSee('Revenue Earned')
            ->assertSee('Automatically created to record sales earnings')
            ->assertViewHas('cashAccountSummary', fn ($summary) => $summary->get('cash')['balance'] === -5.0);

        $this->get(route('reports.cash-flow', ['account' => 'cash']))->assertOk()
            ->assertViewHas('openingBalance', 0.0)
            ->assertViewHas('totalInflow', 10.0)
            ->assertViewHas('totalOutflow', 45.0)
            ->assertViewHas('closingBalance', -35.0)
            ->assertSee('Opening balances awaiting review')
            ->assertDontSee('Other Branch Cash');

        $this->get(route('reports.cash-flow'))->assertOk()
            ->assertViewHas('accounts', fn ($accounts) => $accounts->contains('id', $bank->id))
            ->assertViewHas('totalInflow', 60.0)
            ->assertViewHas('closingBalance', 15.0);
        $this->withoutMiddleware(CheckPlanAccess::class)->get(route('reports.chart-of-accounts'))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['cash_balance'] === -35.0)
            ->assertSee('Opening balances awaiting review');
        $this->get(route('reports.cash-flow', ['account' => $otherBranch->id]))->assertNotFound();
        $this->withSession($this->branchSession($company))->get(route('reports.cash-flow', [
            'account' => $cash->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]))->assertOk()->assertViewHas('closingBalance', -35.0);

        $csv = $this->get(route('reports.cash-flow.export', ['account' => $cash->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Alh Cash Account', $csv);
        $this->assertStringContainsString('"Closing Balance",-35', $csv);
        $this->assertStringContainsString('Unposted opening for review', $csv);
        $this->assertStringNotContainsString('Operating Account', $csv);
    }

    public function test_chart_report_counts_posted_openings_once_and_matches_cash_flow_at_period_end(): void
    {
        [$user, $company] = $this->businessUser();
        $cash = $this->account($company, 'Cash Drawer', 'Asset', 'Cash');
        $cash->update(['opening_balance' => 50, 'branch_id' => 'hq', 'branch_name' => 'Headquarters']);
        LedgerService::postAccountOpeningBalance($cash);
        DB::table('transactions')->where('account_id', $cash->id)->update([
            'transaction_date' => now()->subDay()->toDateString(),
        ]);
        $this->ledgerEntry($company, $cash, 0, 15);
        $revenue = $this->account($company, 'Sales Revenue', 'Revenue', 'Sales Revenue');
        $this->ledgerEntry($company, $revenue, 0, 600);

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->get(route('reports.chart-of-accounts'))->assertOk()
            ->assertViewHas('summary', fn ($summary) => (float) $summary['cash_balance'] === 35.0);
        $dates = ['start_date' => now()->toDateString(), 'end_date' => now()->toDateString()];
        $this->get(route('reports.chart-of-accounts', $dates))->assertOk()
            ->assertViewHas('accounts', function ($accounts) use ($cash) {
                $row = $accounts->firstWhere('id', $cash->id);

                return (float) $row->opening_balance === 50.0
                    && (float) $row->current_balance === 35.0
                    && $row->transactions_count === 1;
            });
        $this->get(route('reports.cash-flow', $dates + ['account' => $cash->id]))->assertOk()
            ->assertViewHas('openingBalance', 50.0)->assertViewHas('closingBalance', 35.0);
    }

    public function test_bank_settings_keep_a_same_named_cash_till_and_its_ledger_separate(): void
    {
        [$user, $company] = $this->businessUser();
        $cash = $this->account($company, 'Cash', 'Asset', 'Cash');
        $cash->update(['opening_balance' => 20, 'current_balance' => 20, 'branch_id' => 'hq', 'branch_name' => 'Headquarters']);

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company));
        $this->post(route('settings.bank-account.store'), [
            'bank_name' => 'Cash',
            'account_number' => '1000001234',
            'opening_balance' => 100,
        ])->assertRedirect(route('bank-account'));

        $bank = Bank::where('company_id', $company->id)->where('name', 'Cash')->sole();
        $bankLedger = Account::where('company_id', $company->id)->where('sub_type', 'Bank')->where('name', 'Cash')->sole();
        $this->assertEquals(20, $cash->fresh()->opening_balance);
        $this->assertEquals(100, $bankLedger->opening_balance);
        $this->assertNull(app(\App\Services\BankBalanceManagementService::class)->findLinkedBank($cash));
        $this->ledgerEntry($company, $bankLedger, 25, 0);

        $this->withSession($this->branchSession($company))
            ->delete(route('settings.bank-account.destroy', $bank))->assertRedirect(route('bank-account'));
        $this->assertFalse($bankLedger->fresh()->is_active);
        $this->assertTrue($cash->fresh()->is_active);
        $this->assertEquals(125, $bankLedger->fresh()->calculateBalance());
    }

    public function test_paying_a_sale_uses_its_cash_or_selected_bank_while_unpaid_sales_only_add_revenue(): void
    {
        [$user, $company] = $this->businessUser();
        $mainBank = $this->account($company, 'Cash at Bank', 'Asset', 'Bank');
        $cash = $this->account($company, 'Till', 'Asset', 'Cash');
        $bank = $this->account($company, 'Collection Account', 'Asset', 'Bank');

        $cashSale = $this->sale($user, $company, 'CASH-RECEIPT-1', 100, 'completed');
        $cashSale->forceFill(['payment_method' => 'cash', 'paid' => 100, 'amount_paid' => 100])->saveQuietly();
        $bankSale = $this->sale($user, $company, 'BANK-RECEIPT-1', 200, 'completed');
        $bankSale->forceFill([
            'payment_method' => 'transfer',
            'payment_details' => ['transfer_account_id' => $bank->id],
            'paid' => 200,
            'amount_paid' => 200,
        ])->saveQuietly();
        $unpaidSale = $this->sale($user, $company, 'UNPAID-SALE-1', 50, 'completed');

        LedgerService::postSale($cashSale);
        LedgerService::postSale($bankSale);
        LedgerService::postSale($bankSale);
        LedgerService::postSale($unpaidSale);

        $this->assertEquals(100, $cash->calculateBalance());
        $this->assertEquals(200, $bank->calculateBalance());
        $this->assertEquals(0, $mainBank->calculateBalance());
        $revenue = Account::where('company_id', $company->id)->where('name', 'Sales Revenue')->sole();
        $this->assertEquals(350, $revenue->calculateBalance());
    }

    public function test_reposting_a_sale_preserves_receipts_already_posted_from_follow_up_payments(): void
    {
        [$user, $company] = $this->businessUser();
        $cash = $this->account($company, 'Till', 'Asset', 'Cash');
        $bank = $this->account($company, 'Collection Account', 'Asset', 'Bank');
        $sale = $this->sale($user, $company, 'PARTIAL-CASH-RECEIPT', 100, 'completed');
        $sale->forceFill(['payment_method' => 'cash', 'paid' => 30, 'amount_paid' => 30])->saveQuietly();
        LedgerService::postSale($sale);
        $payment = Payment::create([
            'sale_id' => $sale->id, 'company_id' => $company->id, 'created_by' => $user->id,
            'branch_id' => 'hq', 'branch_name' => 'Headquarters',
            'method' => 'Bank Transfer', 'account_id' => $bank->id, 'amount' => 40, 'status' => 'Pending',
        ]);
        LedgerService::postSalePayment($sale, $payment);
        $sale->forceFill(['paid' => 70, 'amount_paid' => 70])->saveQuietly();
        LedgerService::postSale($sale);

        $this->assertEquals(30, $cash->calculateBalance());
        $this->assertEquals(40, $bank->calculateBalance());
        $this->assertEquals(30, Account::where('company_id', $company->id)->where('name', 'Accounts Receivable')->sole()->calculateBalance());

        $this->withoutReportAccessMiddleware()->actingAs($user)->withSession($this->branchSession($company))
            ->get(route('reports.sales-summary'))->assertOk()->assertViewHas('totalPaid', 70.0);
    }

    public function test_expense_posting_honors_the_selected_asset_and_legacy_credits_can_be_repaired(): void
    {
        [$user, $company] = $this->businessUser();
        $cash = $this->account($company, 'Cash Account', 'Asset', 'Cash');
        $bank = $this->account($company, 'Operating Account', 'Asset', 'Bank');
        $this->account($company, 'Office Supplies', 'Expense', 'Operating Expense');

        $expense = Expense::create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'expense_id' => 'EXP-CASH-REPORT',
            'company_name' => 'Office Supplier',
            'category' => 'Office Supplies',
            'amount' => 125,
            'status' => 'Paid',
            'payment_status' => 'paid',
            'payment_mode' => $bank->name,
            'payment_account_id' => $bank->id,
        ]);

        LedgerService::postExpense($expense);
        $credit = Transaction::query()->where('related_type', Expense::class)
            ->where('related_id', $expense->id)->where('credit', 125)->sole();
        $this->assertEquals($bank->id, $credit->account_id);

        $expense->update(['payment_account_id' => null]);
        $credit->update(['account_id' => $cash->id]);

        $this->artisan('ledger:repair-expense-payment-accounts', ['company_id' => (string) $company->id])
            ->expectsOutput('Found 1 expense credits eligible for correction.')->assertSuccessful();
        $this->assertEquals($cash->id, $credit->fresh()->account_id);

        $apply = ['company_id' => (string) $company->id, '--apply' => true];
        $this->artisan('ledger:repair-expense-payment-accounts', $apply)
            ->expectsOutput('Corrected 1 expense credits.')->assertSuccessful();
        $this->assertEquals($bank->id, $credit->fresh()->account_id);
        $this->assertEquals($bank->id, $expense->fresh()->payment_account_id);
        $this->artisan('ledger:repair-expense-payment-accounts', $apply)
            ->expectsOutput('Corrected 0 expense credits.')->assertSuccessful();
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
