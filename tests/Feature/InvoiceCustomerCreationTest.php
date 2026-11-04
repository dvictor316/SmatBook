<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCustomerCreationTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'Invoice Customer Business', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters',
        ]);

        return [$company, $user];
    }

    public function test_customer_creation_returns_selection_details_and_keeps_invoice_draft_data(): void
    {
        [$company, $user] = $this->workspace();
        $draft = ['items' => [['product_id' => 10, 'qty' => 3, 'rate' => 125000]], 'invoice_date' => '19-10-2026'];
        $response = $this->withSession(['quotation_prefill' => $draft])->postJson(route('customers.store'), [
            'customer_name' => 'New Invoice Customer', 'email' => 'customer@example.test', 'phone' => '08012345678',
            'billing_address_line1' => '12 Market Road',
        ]);
        $customer = Customer::where('company_id', $company->id)->sole();
        $response->assertCreated()->assertJsonPath('success', true)
            ->assertJsonPath('customer_id', $customer->id)->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('customer.name', 'New Invoice Customer')->assertJsonPath('customer.outstanding_balance', 0)
            ->assertSessionHas('quotation_prefill', $draft);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'company_id' => $company->id,
            'user_id' => $user->id, 'branch_id' => 'hq', 'billing_address_line1' => '12 Market Road']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_validation_and_duplicate_names_return_errors_without_creating_another_customer(): void
    {
        [$company, $user] = $this->workspace();
        $this->postJson(route('customers.store'), ['customer_name' => '', 'email' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['customer_name', 'email']);
        $this->postJson(route('customers.store'), ['customer_name' => 'Existing Customer'])->assertCreated();
        $this->postJson(route('customers.store'), ['customer_name' => ' existing CUSTOMER '])
            ->assertUnprocessable()->assertJsonValidationErrors('customer_name');
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_invoice_page_uses_an_independent_customer_modal_instead_of_leaving_the_invoice(): void
    {
        $this->workspace();
        $this->withoutExceptionHandling();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $response = $this->get(route('add-invoice'));
        $response->assertOk()->assertSee('data-bs-target="#invoice-customer-modal"', false)
            ->assertSee('Save & Select Customer', false)->assertSee('assets/js/invoice-customer.js');
        $document = new \DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//form[@id="invoice-customer-form"]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="invoice-form"]//a[contains(@href, "add-customer")]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="invoice-form"]//form[@id="invoice-customer-form"]')->length);
    }

    public function test_invoice_form_includes_opening_balance_and_current_invoice_in_total_due(): void
    {
        [$company, $user] = $this->workspace();
        $customer = Customer::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_name' => 'Outstanding Invoice Customer',
            'balance' => 1200,
        ]);
        Sale::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->customer_name,
            'invoice_no' => 'PREVIOUS-UNPAID-001',
            'subtotal' => 3000,
            'total' => 3000,
            'paid' => 0,
            'amount_paid' => 0,
            'balance' => 3000,
            'payment_status' => 'unpaid',
        ]);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->withoutExceptionHandling()->get(route('add-invoice', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('data-outstanding-balance="4200"', false)
            ->assertSee(
                'id="display-previous-customer-balance">₦' . \App\Support\CompactNumber::format(4200, 2) . '</span>',
                false
            )
            ->assertSee('Previous Customer Balance', false)
            ->assertSee('Total Balance Due', false)
            ->assertSee('Previous balance plus this invoice', false)
            ->assertSee('Save &amp; Print', false)
            ->assertSee('name="action" id="invoice-submit-action" value="save"', false)
            ->assertSee('data-invoice-action="print"', false);
    }
}
