<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Support\FinancialDataReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialResetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function business(string $name, bool $legacy = false): array
    {
        $company = Company::create(['name' => $name, 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $customer = Customer::create(['company_id' => $legacy ? null : $company->id, 'user_id' => $user->id,
            'customer_name' => $name.' Customer', 'balance' => 3000000, 'wallet_balance' => 100]);
        $sale = Sale::create(['company_id' => $legacy ? null : $company->id, 'user_id' => $user->id,
            'customer_id' => $customer->id, 'invoice_no' => 'OPENING-BAL-'.$customer->id,
            'subtotal' => 5000000, 'total' => 5000000, 'paid' => 2000000, 'amount_paid' => 2000000, 'balance' => 3000000]);
        $payment = Payment::create(['company_id' => $legacy ? null : $company->id, 'user_id' => $user->id,
            'created_by' => $user->id, 'customer_id' => $customer->id, 'sale_id' => $sale->id,
            'amount' => 2000000, 'method' => 'Cash', 'status' => 'Completed']);

        return [$company, $user, $customer, $sale, $payment];
    }

    private function withoutAccessMiddleware(): self
    {
        return $this->withoutMiddleware([CheckPlanAccess::class, EnforceDeviceSessionLimit::class,
            ForceLogoutExpiredSession::class, RequireActiveBranch::class, SubscriptionActive::class, VerifyTenantSession::class]);
    }

    public function test_reset_clears_opening_debt_and_legacy_records_without_touching_another_business(): void
    {
        [$company, $user, $customer, $sale, $payment] = $this->business('Reset Target', true);
        [$otherCompany, $otherUser, $otherCustomer, $otherSale, $otherPayment] = $this->business('Preserved');

        app(FinancialDataReset::class)->execute($company->id);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 0, 'wallet_balance' => 0]);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('customers', ['id' => $otherCustomer->id, 'balance' => 3000000, 'wallet_balance' => 100]);
        $this->assertDatabaseHas('sales', ['id' => $otherSale->id]);
        $this->assertDatabaseHas('payments', ['id' => $otherPayment->id]);
        $this->withoutAccessMiddleware()->actingAs($user)->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('reports.accounts-receivable'))->assertOk()->assertViewHas('totalDue', 0.0)
            ->assertViewHas('receivables', fn ($rows) => $rows->isEmpty());
        // Running an already completed reset must not recreate any debt.
        app(FinancialDataReset::class)->execute($company->id);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 0]);
    }

    public function test_reset_removes_customer_linked_legacy_documents_and_zeroes_master_balances(): void
    {
        [$company, $user, $customer] = $this->business('Master Balance Target');
        $invoiceId = DB::table('invoices')->insertGetId(['company_id' => $company->id, 'customer_id' => $customer->id, 'amount' => 5000000, 'due_date' => now()->toDateString()]);
        $receiptId = DB::table('receipts')->insertGetId(['customer_id' => $customer->id, 'amount' => 2000000, 'receipt_date' => now()->toDateString()]);
        $account = \App\Models\Account::create(['company_id' => $company->id, 'name' => 'Opening Receivables',
            'code' => 'AR-RESET', 'type' => 'Asset', 'opening_balance' => 5000000, 'current_balance' => 3000000]);
        $productId = DB::table('products')->insertGetId(['company_id' => $company->id, 'user_id' => $user->id,
            'name' => 'Reset Stock', 'sku' => 'RESET-STOCK', 'price' => 100, 'purchase_price' => 50, 'stock' => 8, 'stock_quantity' => 8]);

        $preview = app(FinancialDataReset::class)->preview($company->id);
        $this->assertSame(1, $preview['invoices']);
        $this->assertSame(1, $preview['receipts']);
        app(FinancialDataReset::class)->execute($company->id);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
        $this->assertDatabaseMissing('receipts', ['id' => $receiptId]);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'opening_balance' => 0, 'current_balance' => 0]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 0, 'stock_quantity' => 0]);
    }

    public function test_web_reset_targets_the_active_business_instead_of_the_super_admins_company(): void
    {
        [$company, , $customer, $sale] = $this->business('Active Business');
        [$otherCompany, , $otherCustomer, $otherSale] = $this->business('Admin Business');
        $admin = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'super_admin', 'password' => Hash::make('reset-test-password')]);
        $this->withoutAccessMiddleware()->actingAs($admin)->withSession(['current_tenant_id' => $company->id])
            ->postJson(route('super_admin.financial.reset.execute'), [
                'confirmation_phrase' => 'RESET FINANCIAL DATA', 'password' => 'reset-test-password',
            ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 0]);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('customers', ['id' => $otherCustomer->id, 'balance' => 3000000]);
        $this->assertDatabaseHas('sales', ['id' => $otherSale->id]);
    }

    public function test_reset_failure_rolls_back_deleted_records_and_balances(): void
    {
        [$company, , $customer, $sale, $payment] = $this->business('Rollback Target');
        DB::unprepared("CREATE TRIGGER prevent_customer_reset BEFORE UPDATE ON customers BEGIN SELECT RAISE(ABORT, 'Simulated balance reset failure'); END");
        try {
            app(FinancialDataReset::class)->execute($company->id);
            $this->fail('A partial reset must fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('Simulated balance reset failure', $exception->getMessage());
        }
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 3000000]);
    }

    public function test_command_line_reset_uses_the_same_cleanup_and_dry_run_preserves_data(): void
    {
        [$company, , $customer, $sale] = $this->business('CLI Target', true);
        $this->artisan('finance:reset', ['--company' => $company->id, '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 3000000]);
        $this->artisan('finance:reset', ['--company' => $company->id, '--force' => true])->assertSuccessful();
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 0]);
    }

    public function test_reset_requires_a_valid_company(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(FinancialDataReset::class)->execute(0);
    }
}
