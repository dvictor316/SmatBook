<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Support\SalesPaymentModeFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->assertSame('Credit', $sale->payment_report_label);
        $this->get(route('sales.invoice.print', $sale->id))->assertOk()->assertSee('Credit');
        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'format' => 'thermal']))->assertOk()->assertSee('CREDIT');
        $this->assertDatabaseCount('payments', 0);
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
