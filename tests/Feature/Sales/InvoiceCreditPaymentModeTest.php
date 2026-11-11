<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\SalesPaymentModeFilter;
use App\Support\StockValuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceCreditPaymentModeTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'Credit Invoice Business', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $customer = Customer::create(['company_id' => $company->id, 'user_id' => $user->id, 'customer_name' => 'Credit Customer', 'balance' => 0]);
        $this->actingAs($user)->withoutMiddleware()->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'branch', 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters']);
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));

        return [$company, $user, $customer];
    }

    private function invoicePayload(Customer $customer, int $productId): array
    {
        return ['customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'total_amount' => 1000,
            'action' => 'send', 'status' => 'Unpaid', 'items' => [['product_id' => $productId, 'name' => 'Invoice Product', 'qty' => 1, 'rate' => 1000]]];
    }

    private function product(Company $company, User $user): int
    {
        return DB::table('products')->insertGetId(['company_id' => $company->id, 'user_id' => $user->id,
            'name' => 'Invoice Product', 'sku' => 'CREDIT-PRODUCT', 'price' => 1000, 'purchase_price' => 500,
            'stock' => 10, 'stock_quantity' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function sale(Company $company, User $user, Customer $customer, string $method, float $paid = 0): Sale
    {
        return Sale::create(['company_id' => $company->id, 'user_id' => $user->id, 'customer_id' => $customer->id,
            'invoice_no' => 'MODE-'.fake()->unique()->numerify('######'), 'customer_name' => $customer->customer_name,
            'subtotal' => 1000, 'total' => 1000, 'paid' => $paid, 'amount_paid' => $paid, 'balance' => 1000 - $paid,
            'payment_method' => $method, 'payment_status' => $paid > 0 ? 'paid' : 'unpaid']);
    }

    public function test_unpaid_invoice_is_saved_as_credit_and_prints_credit_on_standard_and_thermal_receipts(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $payload = $this->invoicePayload($customer, $this->product($company, $user));
        $response = $this->post(route('invoices.store'), $payload);
        $this->assertNull(session('error'), (string) session('error'));
        $response->assertSessionHasNoErrors()->assertSessionHas('success');
        $sale = Sale::where('company_id', $company->id)->sole();
        $this->assertSame('credit', $sale->payment_method);
        $this->assertSame(0.0, (float) $sale->amount_paid);
        $this->assertSame(1000.0, (float) $sale->balance);
        $this->assertSame(500.0, (float) $sale->items()->sole()->unit_cost);
        $this->assertSame('Credit', $sale->payment_report_label);
        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertSee(route('invoice-details-admin.print', ['id' => $sale->id, 'autoprint' => 1]), false)
            ->assertDontSee('target="_blank" rel="noopener"', false);
        $this->get(route('sales.invoice.print', $sale->id))->assertOk()->assertSee('Credit');
        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'format' => 'thermal']))->assertOk()->assertSee('CREDIT');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invoice_at_the_customer_credit_limit_is_allowed(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $customer->update(['credit_limit' => 1000]);
        $payload = $this->invoicePayload($customer, $this->product($company, $user));

        $this->post(route('invoices.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('credit_limit_warning');

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(1000.0, (float) Sale::query()->sole()->balance);
    }

    public function test_invoice_that_would_exceed_customer_credit_limit_is_blocked(): void
    {
        [$company, $user, $customer] = $this->workspace();
        Notification::fake();
        $customer->update(['credit_limit' => 1000]);
        $this->sale($company, $user, $customer, 'credit');
        $payload = $this->invoicePayload($customer, $this->product($company, $user));
        $payload['total_amount'] = 1;
        $payload['items'][0]['rate'] = 1;

        $this->post(route('invoices.store'), $payload)
            ->assertSessionHas('credit_limit_warning', fn (array $warning) => $warning['blocked'] === true
                && (float) $warning['projected_outstanding'] === 1001.0);

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_invoice_can_be_saved_and_redirected_to_print_view(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $payload = $this->invoicePayload($customer, $this->product($company, $user));
        $payload['action'] = 'print';

        $response = $this->post(route('invoices.store'), $payload);
        $sale = Sale::where('company_id', $company->id)->sole();

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.invoice.print', ['id' => $sale->id, 'autoprint' => 1]));
        $this->assertSame(1000.0, (float) $sale->balance);
        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'autoprint' => 1]))
            ->assertOk()
            ->assertSee('const autoPrintReceipt = true;', false)
            ->assertSee('function closePrintPage()', false)
            ->assertSee('window.location.replace(printReturnUrl)', false)
            ->assertSee('Customer Balance Due');
    }

    public function test_invoice_print_posting_resolves_a_branch_when_all_branches_are_selected(): void
    {
        [$company, $user, $customer] = $this->workspace();
        session()->forget(['active_branch_id', 'active_branch_name']);
        session(['active_branch_scope' => 'all']);

        $payload = $this->invoicePayload($customer, $this->product($company, $user));
        $payload['action'] = 'print';
        $response = $this->post(route('invoices.store'), $payload);
        $this->assertNull(session('error'), (string) session('error'));
        $sale = Sale::where('company_id', $company->id)->sole();

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.invoice.print', ['id' => $sale->id, 'autoprint' => 1]));
        $this->assertNotEmpty($sale->branch_id);
    }

    public function test_invoice_sells_standalone_carton_and_values_remaining_stock_at_carton_cost(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $productId = $this->product($company, $user);
        DB::table('products')->where('id', $productId)->update([
            'base_unit_name' => 'pcs',
            'unit_type' => 'carton',
            'units_per_carton' => 0,
            'stock' => 4,
            'stock_quantity' => 4,
        ]);
        DB::table('settings')->insert([
            'key' => 'branches_json_company_'.$company->id,
            'value' => json_encode([['id' => 'hq', 'name' => 'Headquarters']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_branch_stocks')->insert([
            'company_id' => $company->id,
            'product_id' => $productId,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'quantity' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_units')->insert([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'product_id' => $productId,
            'unit_name' => 'pcs',
            'unit_symbol' => 'pcs',
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'is_purchase_unit' => true,
            'is_default_sales_unit' => true,
            'is_sales_unit' => true,
            'purchase_price' => 500,
            'selling_price' => 1000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::findOrFail($productId);
        $this->assertSame('carton', $product->salesUnitConfiguration()['options'][0]['name']);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 4]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $productId, 'branch_id' => 'hq', 'quantity' => 4]);
        $invoicePage = $this->withViewErrors([])->get(route('sales.create'))->assertOk();
        $this->assertStringContainsString(
            '"name":"carton"',
            html_entity_decode($invoicePage->getContent(), ENT_QUOTES | ENT_HTML5)
        );

        $payload = $this->invoicePayload($customer, $productId);
        $payload['items'][0]['unit_type'] = 'carton';
        $response = $this->post(route('invoices.store'), $payload);
        $this->assertNull(session('error'), (string) session('error'));
        $response->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $productId, 'branch_id' => 'hq', 'quantity' => 3]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 3, 'stock_quantity' => 3]);
        $valuation = app(StockValuation::class)->summary(
            app(StockValuation::class)->rowsQuery($company->id, $user->id, [
                'scope' => 'branch',
                'id' => 'hq',
                'name' => 'Headquarters',
            ])
        );
        $this->assertSame(1500.0, $valuation['value']);
    }

    public function test_existing_unpaid_manual_send_and_cash_rows_display_as_credit_without_rewriting_financial_data(): void
    {
        [$company, $user, $customer] = $this->workspace();
        foreach (['manual', 'send', 'cash', 'credit'] as $method) {
            $sale = $this->sale($company, $user, $customer, $method);
            $this->assertSame('Credit', $sale->payment_mode_label);
            $this->assertSame('Credit', $sale->payment_report_label);
            $this->assertSame($method, $sale->fresh()->payment_method);
        }
        $paidCash = $this->sale($company, $user, $customer, 'cash', 1000);
        $this->assertSame('Cash', $paidCash->payment_mode_label);
        $unknownPaid = $this->sale($company, $user, $customer, 'manual', 1000);
        $this->assertSame('Payment not specified', $unknownPaid->payment_mode_label);
    }

    public function test_cash_filters_exclude_unpaid_legacy_cash_invoices_and_credit_filter_finds_them(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $credit = $this->sale($company, $user, $customer, 'cash');
        $cash = $this->sale($company, $user, $customer, 'cash', 1000);
        $cashQuery = Sale::query();
        SalesPaymentModeFilter::apply($cashQuery, 'cash');
        $this->assertSame([$cash->id], $cashQuery->pluck('id')->all());
        $creditQuery = Sale::query();
        SalesPaymentModeFilter::apply($creditQuery, 'credit');
        $this->assertSame([$credit->id], $creditQuery->pluck('id')->all());
    }

    public function test_editing_an_invoice_preserves_actual_receipts_and_payment_account_details(): void
    {
        [$company, $user, $customer] = $this->workspace();
        $productId = $this->product($company, $user);
        $payload = $this->invoicePayload($customer, $productId);
        $this->post(route('invoices.store'), $payload)->assertSessionHas('success');
        $sale = Sale::where('company_id', $company->id)->sole();
        $sale->update(['payment_method' => 'transfer', 'amount_paid' => 300, 'paid' => 300, 'balance' => 700,
            'payment_details' => ['transfer_account_id' => 123, 'transfer_account_name' => 'UBA Bank']]);
        Payment::create(['company_id' => $company->id, 'user_id' => $user->id, 'created_by' => $user->id,
            'sale_id' => $sale->id, 'customer_id' => $customer->id, 'amount' => 300, 'method' => 'Bank Transfer', 'status' => 'Completed']);
        $payload['status'] = 'Partially paid';
        $this->put(route('invoices.update', $sale->id), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $sale->refresh();
        $this->assertSame('transfer', $sale->payment_method);
        $this->assertSame(300.0, (float) $sale->amount_paid);
        $this->assertSame(700.0, (float) $sale->balance);
        $this->assertSame('UBA Bank', $sale->payment_details['transfer_account_name']);
        $this->assertSame('Bank Transfer', $sale->load('payments')->payment_mode_label);
    }
}
