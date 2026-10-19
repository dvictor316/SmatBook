<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
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
}
