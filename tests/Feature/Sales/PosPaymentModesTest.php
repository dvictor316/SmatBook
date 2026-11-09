<?php

namespace Tests\Feature\Sales;

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
