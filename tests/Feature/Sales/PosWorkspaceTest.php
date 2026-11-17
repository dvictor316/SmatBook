<?php

namespace Tests\Feature\Sales;

use App\Models\Account;
use App\Models\Company;
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
        if ($preview = getenv('POS_PREVIEW_PATH')) {
            file_put_contents($preview, $html);
        }
    }
}
