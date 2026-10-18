<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAndBarcodeTest extends TestCase
{
    use RefreshDatabase;

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

        $this->actingAs($user)->withoutMiddleware()
            ->post(route('settings.update'), [
                'address_line_1' => '14 Market Road',
                'address_line_2' => 'Suite 3',
                'company_city' => 'Lagos',
                'company_state' => 'Lagos State',
                'company_pincode' => '100001',
                'company_country' => 'Nigeria',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'address_line_1_company_'.$company->id,
            'value' => '14 Market Road',
        ]);

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
            ->assertSee('Arial, Helvetica, sans-serif')
            ->assertDontSee('Katsina Street')
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
