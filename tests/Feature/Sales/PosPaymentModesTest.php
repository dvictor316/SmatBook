<?php

namespace Tests\Feature\Sales;

use App\Models\Account;
use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use App\Support\SalesPaymentModeFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosPaymentModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_summary_combines_retired_current_and_unassigned_cash_accounts(): void
    {
        [$company, $user] = $this->createCompanyAndSales();
        $branch = ['branch_id' => 'hq', 'branch_name' => 'Headquarters'];
        Sale::where('company_id', $company->id)->update($branch);
        $this->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => $branch['branch_id'],
            'active_branch_name' => $branch['branch_name'],
        ]);
        $oldCash = Account::create(['company_id' => $company->id, 'name' => 'Cash', 'code' => 'OLD-CASH',
            'type' => 'Asset', 'sub_type' => 'Bank', 'is_active' => true]);
        $currentCash = Account::create(['company_id' => $company->id, 'name' => 'Cash', 'code' => 'POS-CASH',
            'type' => 'Asset', 'sub_type' => 'Cash', 'is_active' => true]);
        $bank = Account::create(['company_id' => $company->id, 'name' => 'Operating Bank', 'code' => 'OPERATING-BANK',
            'type' => 'Asset', 'sub_type' => 'Bank', 'is_active' => true]);

        foreach (['OLD' => $oldCash->id, 'CURRENT' => $currentCash->id, 'UNASSIGNED' => null] as $label => $cashId) {
            $direct = $label === 'CURRENT';
            $total = $direct ? 100 : 1000;
            Sale::create($branch + [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'terminal_id' => 'POS-1',
                'invoice_no' => 'CASH-'.$label,
                'subtotal' => $total,
                'total' => $total,
                'paid' => $total,
                'amount_paid' => $total,
                'payment_method' => $direct ? 'cash' : 'split',
                'payment_status' => 'paid',
                'payment_details' => [
                    'source' => 'pos',
                    'payment_account_id' => $cashId,
                    'transfer_account_id' => $bank->id,
                    'transfer_account_name' => $bank->name,
                    'card_account_id' => $bank->id,
                    'card_account_name' => $bank->name,
                    'split' => $direct ? [] : ['cash' => 100, 'transfer' => 300, 'card' => 600],
                ],
            ]);
        }
        $oldCash->delete();

        $this->get(route('pos.sales'))->assertOk()
            ->assertViewHas('paymentBreakdown', fn ($summary) => $summary['cash'] === ['label' => 'Cash', 'amount' => 300.0, 'count' => 3]
                && count(array_filter($summary, fn ($row) => $row['label'] === 'Cash')) === 1
                && $summary['account:'.$bank->id]['count'] === 2
                && $summary['account:'.$bank->id]['amount'] === 1800.0);

        $this->get(route('pos.sales', ['payment_method' => 'cash']))->assertOk()
            ->assertViewHas('totalSalesCount', 3)->assertSee('CASH-OLD')->assertSee('CASH-CURRENT')->assertSee('CASH-UNASSIGNED')
            ->assertDontSee('POS-TRANSFER-1')->assertDontSee('POS-CARD-1');
    }

    public function test_pos_sales_register_filters_split_payments_by_card_amount(): void
    {
        [$company, $user] = $this->createCompanyAndSales();

        $cardSales = DB::table('sales')->where('company_id', $company->id);
        SalesPaymentModeFilter::apply($cardSales, 'card');
        $this->assertSame(['POS-CARD-1'], $cardSales->pluck('invoice_no')->all());

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('pos.sales', ['payment_method' => 'card']))
            ->assertOk()
            ->assertSee('POS-CARD-1')
            ->assertSee('POS / Card')
            ->assertDontSee('POS-TRANSFER-1');
    }

    public function test_pos_sales_register_filters_split_payments_by_transfer_amount(): void
    {
        [$company, $user] = $this->createCompanyAndSales();
        $this->get(route('pos.sales', ['payment_method' => 'transfer']))
            ->assertOk()
            ->assertSee('POS-TRANSFER-1')
            ->assertSee('Bank Transfer')
            ->assertDontSee('POS-CARD-1');
    }

    public function test_pos_sales_register_filters_and_labels_online_web_sales(): void
    {
        [$company, $user] = $this->createCompanyAndSales();

        Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'terminal_id' => 'WEB',
            'source_type' => 'online_store_order',
            'invoice_no' => 'WEB-ONLINE-1',
            'customer_name' => 'Online Customer',
            'subtotal' => 1500,
            'total' => 1500,
            'paid' => 1500,
            'amount_paid' => 1500,
            'balance' => 0,
            'payment_method' => 'transfer',
            'payment_status' => 'paid',
            'payment_details' => ['source' => 'web'],
        ]);
        Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'terminal_id' => 'WEB',
            'invoice_no' => 'WEB-LEGACY-1',
            'customer_name' => 'Legacy Online Customer',
            'subtotal' => 500,
            'total' => 500,
            'paid' => 500,
            'amount_paid' => 500,
            'balance' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $onlineSales = DB::table('sales')->where('company_id', $company->id);
        SalesPaymentModeFilter::apply($onlineSales, 'web');
        $this->assertEqualsCanonicalizing(
            ['WEB-ONLINE-1', 'WEB-LEGACY-1'],
            $onlineSales->pluck('invoice_no')->all()
        );

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('pos.sales', ['payment_method' => 'web']))
            ->assertOk()
            ->assertSee('WEB-ONLINE-1')
            ->assertSee('WEB-LEGACY-1')
            ->assertSee('Online / Web · Bank Transfer')
            ->assertSee('<option value="web" selected', false)
            ->assertDontSee('POS-TRANSFER-1')
            ->assertDontSee('POS-CARD-1');
    }

    public function test_sales_report_exposes_the_payment_mode_filter(): void
    {
        $this->createCompanyAndSales(false);

        $this->get(route('reports.sales', ['payment_method' => 'transfer']))
            ->assertOk()
            ->assertSee('name="payment_method"', false)
            ->assertSee('<option value="transfer" selected', false);

        $this->get(route('reports.sales', ['payment_method' => 'web']))
            ->assertOk()
            ->assertSee('<option value="web" selected', false);
    }

    private function createCompanyAndSales(bool $disableMiddleware = true): array
    {
        $company = Company::create(['name' => 'Payment Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->createSplitSale($company->id, $user->id, 'POS-TRANSFER-1', [
            'cash' => 0,
            'transfer' => 1000,
            'card' => 0,
        ]);
        $this->createSplitSale($company->id, $user->id, 'POS-CARD-1', [
            'cash' => 0,
            'transfer' => 0,
            'card' => 1000,
        ]);

        $this->actingAs($user);
        if ($disableMiddleware) {
            $this->withoutMiddleware();
        }

        return [$company, $user];
    }

    private function createSplitSale(int $companyId, int $userId, string $invoiceNo, array $channels): void
    {
        Sale::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'terminal_id' => 'POS-1',
            'invoice_no' => $invoiceNo,
            'customer_name' => 'Walk-in Customer',
            'subtotal' => 1000,
            'total' => 1000,
            'paid' => 1000,
            'amount_paid' => 1000,
            'balance' => 0,
            'payment_method' => 'split',
            'payment_status' => 'paid',
            'payment_details' => [
                'source' => 'pos',
                'split' => $channels,
            ],
        ]);
    }
}
