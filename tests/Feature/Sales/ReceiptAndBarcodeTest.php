<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceiptAndBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_prints_show_previous_debt_and_combined_customer_balance(): void
    {
        $company = Company::create(['name' => 'Balance Invoice Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $customer = Customer::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_name' => 'Balance Customer',
            'balance' => 1000000,
        ]);
        $sale = Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->customer_name,
            'invoice_no' => 'BALANCE-INVOICE-500K',
            'subtotal' => 500000,
            'total' => 500000,
            'paid' => 0,
            'amount_paid' => 0,
            'balance' => 500000,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('sales.invoice.print', $sale->id))
            ->assertOk()
            ->assertSee('Previous Customer Balance')
            ->assertSee('1,000,000')
            ->assertSee('Customer Balance Due')
            ->assertSee('1,500,000');

        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'format' => 'thermal', 'paper' => 80]))
            ->assertOk()
            ->assertSee('Previous Balance')
            ->assertSee('Current Invoice Balance')
            ->assertSee('Total Customer Balance')
            ->assertSee('1,500,000');

        $this->get(route('invoice-details.print', ['id' => $sale->id]))
            ->assertOk()
            ->assertSee('Previous Customer Balance')
            ->assertSee('Customer Balance Due')
            ->assertSee('1,500,000');
    }

    public function test_payment_receipt_shows_customer_balance_after_payment_across_invoices(): void
    {
        $company = Company::create(['name' => 'Balance Receipt Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $customer = Customer::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_name' => 'Receipt Balance Customer',
            'balance' => 200000,
        ]);
        $openingSale = Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->customer_name,
            'invoice_no' => 'OPENING-BAL-' . $customer->id,
            'subtotal' => 1000000,
            'total' => 1000000,
            'paid' => 800000,
            'amount_paid' => 800000,
            'balance' => 200000,
            'payment_status' => 'partial',
        ]);
        Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->customer_name,
            'invoice_no' => 'CURRENT-INVOICE-500K',
            'subtotal' => 500000,
            'total' => 500000,
            'paid' => 0,
            'amount_paid' => 0,
            'balance' => 500000,
            'payment_status' => 'unpaid',
        ]);
        $payment = Payment::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'customer_id' => $customer->id,
            'sale_id' => $openingSale->id,
            'amount' => 800000,
            'method' => 'Cash',
            'status' => 'Completed',
        ]);
        $this->assertNotNull($payment->id);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('payments.receipt', $payment->id))
            ->assertOk()
            ->assertSee('Customer Balance After Payment')
            ->assertSee('700,000');
    }

    public function test_company_profile_address_is_used_for_standard_and_thermal_sales_receipts(): void
    {
        $company = Company::create([
            'name' => 'Receipt Company',
            'address' => 'Katsina Street',
            'industry' => 'retail',
            'plan' => 'Enterprise',
        ]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        Setting::create(['key' => 'company_address', 'value' => 'Katsina Street']);
        $sale = Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'invoice_no' => 'ADDRESS-TEST-1',
            'customer_name' => 'Receipt Customer',
            'subtotal' => 1000,
            'total' => 1000,
            'paid' => 1000,
            'amount_paid' => 1000,
            'balance' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Receipt SKU Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'Receipt Item',
            'sku' => 'RECEIPT-SKU-001',
            'price' => 1000,
            'purchase_price' => 500,
            'stock' => 1,
            'stock_quantity' => 1,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'product_name' => 'Receipt Item',
            'qty' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'total_price' => 1000,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->post(route('settings.update'), [
                'address_line_1' => '14 Market Road',
                'address_line_2' => 'Suite 3',
                'company_city' => 'Lagos',
                'company_state' => 'Lagos State',
                'company_pincode' => '100001',
                'company_country' => 'Nigeria',
                'receipt_footer' => 'Thank you from Receipt Company!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'address_line_1_company_'.$company->id,
            'value' => '14 Market Road',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'receipt_footer_company_'.$company->id,
            'value' => 'Thank you from Receipt Company!',
        ]);
        $this->get(route('invoice-settings'))
            ->assertOk()
            ->assertSee('Receipt Footer Message')
            ->assertSee('Thank you from Receipt Company!');

        $this->get(route('sales.invoice.print', $sale->id))
            ->assertOk()
            ->assertSee('14 Market Road')
            ->assertSee('Lagos State')
            ->assertDontSee('Katsina Street');

        $thermalReceipt = $this->get(route('sales.invoice.print', [
            'id' => $sale->id,
            'format' => 'thermal',
            'paper' => 80,
        ]));
        $thermalReceipt->assertOk()
            ->assertSee('14 Market Road')
            ->assertSee('Thank you from Receipt Company!')
            ->assertSee('Arial, Helvetica, sans-serif')
            ->assertDontSee('Katsina Street')
            ->assertDontSee('RECEIPT-SKU-001')
            ->assertDontSee('SKU:')
            ->assertDontSee('size: 80mm auto');
    }

    public function test_pos_barcode_input_commits_the_scanned_code_on_enter(): void
    {
        $company = Company::create(['name' => 'POS Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('sales.showPos'));
        $response->assertOk()
            ->assertSee('Scan barcode, then press Enter')
            ->assertSee('event.key !== \'Enter\'', false)
            ->assertSee('commitVanillaBarcodeScan(barcodeInput.value);', false);
    }
}
