<?php

namespace Tests\Feature\Sales;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_and_checkout_screens_share_the_existing_payment_controls(): void
    {
        $company = Company::create(['name' => 'Workspace Retail', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);
        $walletCustomer = new Customer();
        $walletCustomer->forceFill(['company_id' => $company->id, 'user_id' => $user->id, 'customer_name' => 'Wallet Customer', 'wallet_balance' => 750, 'balance' => 0])->save();
        Account::create([
            'company_id' => $company->id, 'name' => 'Cash Drawer', 'code' => 'CASH',
            'type' => Account::TYPE_ASSET, 'sub_type' => 'Cash', 'is_active' => true,
        ]);
        foreach (['Peak Milk 400g', 'Groundnut Oil 1.5L', 'Chocolate Cookies', 'Breakfast Cereal', 'Orange Juice', 'Premium Rice'] as $index => $name) {
            Product::create([
                'company_id' => $company->id, 'user_id' => $user->id,
                'branch_id' => 'hq', 'branch_name' => 'Headquarters',
                'name' => $name, 'sku' => 'ITEM-'.$index, 'barcode' => '00012345678'.$index,
                'stock' => 50, 'price' => 2000 + $index * 500,
                'retail_price' => 2000 + $index * 500, 'purchase_price' => 1000,
            ]);
        }
        $response = $this->get(route('sales.showPos'))->assertOk();
        $html = $response->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        foreach (['amount-paid', 'process-btn', 'customer-select', 'product-select', 'grand-total'] as $id) {
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length, $id.' must remain unique');
        }
        $this->assertSame(1, $xpath->query('//*[@id="pos-checkout-screen"]//*[@id="process-btn"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="pos-sell-screen"]//*[@id="amount-paid"]')->length);
        $this->assertSame(6, $xpath->query('//*[@id="product-grid"]/*[@role="button"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="pos-add-customer"]')->length);
        if ($preview = getenv('POS_PREVIEW_PATH')) {
            file_put_contents($preview, $html);
        }
    }
    public function test_cashier_can_add_a_customer_and_is_directed_to_admin_for_missing_cash_account(): void
    {
        $company = Company::create(['name' => 'Cashier Customer Retail', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'cashier',
            'permissions_override' => ['sales.sales.create', 'customers.customers.view']]);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters',
        ]);
        $response = $this->get(route('sales.showPos'))->assertOk()
            ->assertSee('Contact your administrator to create a cash account for this branch.')
            ->assertSee('id="pos-add-customer"', false)
            ->assertSee('showDenyButton: true', false);
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//*[@id="pos-cash-account-wrap"]//a')->length);
        if ($preview = getenv('POS_NO_CASH_PREVIEW_PATH')) file_put_contents($preview, $response->getContent());
        $result = $this->postJson(route('customers.store'), ['customer_name' => 'Counter Customer', 'phone' => '08012345678'])
            ->assertCreated()->assertJsonPath('customer.name', 'Counter Customer');
        $this->assertDatabaseHas('customers', ['id' => $result->json('customer.id'), 'company_id' => $company->id, 'branch_id' => 'hq']);
        $this->assertDatabaseCount('sales', 0);
        $user->update(['permissions_override' => ['customers.customers.view']]);
        $this->get(route('sales.showPos'))->assertOk()->assertDontSee('id="pos-add-customer"', false)
            ->assertSee('showDenyButton: false', false);
    }

}
