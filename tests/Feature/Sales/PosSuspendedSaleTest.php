<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Account;
use App\Models\PosSuspendedSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosSuspendedSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_suspend_recall_update_and_delete_a_sale_in_the_same_branch(): void
    {
        $company = Company::create(['name' => 'Suspense Retail', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'Coffee',
            'sku' => 'SUSPENSE-COFFEE',
            'stock' => 12,
            'price' => 20,
            'purchase_price' => 10,
        ]);
        DB::table('products')->where('id', $product->id)->update(['branch_id' => null]);
        $token = (string) Str::uuid();
        $item = [
            'id' => $product->id,
            'name' => 'Coffee',
            'qty' => 1,
            'price' => 20,
            'unitType' => 'unit',
            'unitLabel' => 'pcs',
            'unitMultiplier' => 1,
            'stockUnits' => 1,
            'stockAvailable' => 12,
            'discountType' => 'percent',
            'discountValue' => 0,
            'tax' => 0,
        ];

        $saved = $this->postJson(route('pos.suspense.store'), [
            'claim_token' => $token,
            'customer_name' => 'Walk-in Customer',
            'items' => [$item],
        ])->assertOk()->assertJsonPath('message', 'Sale suspended.');
        $id = (int) $saved->json('id');

        $this->getJson(route('pos.suspense.index', ['claim_token' => $token]))
            ->assertOk()
            ->assertJsonPath('sales.0.item_count', 1)
            ->assertJsonPath('sales.0.total', 20);

        $this->postJson(route('pos.suspense.claim', $id), ['claim_token' => $token])
            ->assertOk()
            ->assertJsonPath('items.0.id', $product->id)
            ->assertJsonPath('customer_name', 'Walk-in Customer');

        $this->getJson(route('pos.suspense.index', ['claim_token' => $token]))
            ->assertOk()
            ->assertJsonPath('sales.0.locked', true)
            ->assertJsonPath('sales.0.locked_by_this_terminal', true);

        $this->postJson(route('pos.suspense.claim', $id), ['claim_token' => (string) Str::uuid()])
            ->assertConflict();

        $item['qty'] = 2;
        $item['stockUnits'] = 2;
        $this->postJson(route('pos.suspense.store'), [
            'id' => $id,
            'claim_token' => $token,
            'customer_name' => 'Walk-in Customer',
            'items' => [$item],
        ])->assertOk();

        $this->assertDatabaseHas('pos_suspended_sales', [
            'id' => $id,
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'item_count' => 1,
            'total' => 40,
            'claim_token' => null,
        ]);
        $this->assertSame(1, PosSuspendedSale::count());

        $this->deleteJson(route('pos.suspense.destroy', $id), ['claim_token' => $token])
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('pos_suspended_sales', ['id' => $id]);
    }

    public function test_suspended_sales_are_isolated_by_company_and_branch(): void
    {
        $company = Company::create(['name' => 'Branch Suspense', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'north',
            'active_branch_name' => 'North',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'north',
            'branch_name' => 'North',
            'name' => 'Tea',
            'sku' => 'SUSPENSE-TEA',
            'stock' => 4,
            'price' => 15,
            'purchase_price' => 8,
        ]);
        $token = (string) Str::uuid();
        $saved = $this->postJson(route('pos.suspense.store'), [
            'claim_token' => $token,
            'items' => [[
                'id' => $product->id,
                'name' => 'Tea',
                'qty' => 1,
                'price' => 15,
                'tax' => 0,
            ]],
        ])->assertOk();
        $id = (int) $saved->json('id');

        $this->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'south',
            'active_branch_name' => 'South',
        ]);
        $this->getJson(route('pos.suspense.index', ['claim_token' => $token]))
            ->assertOk()
            ->assertJsonCount(0, 'sales');
        $this->postJson(route('pos.suspense.claim', $id), ['claim_token' => $token])->assertNotFound();

        $otherCompany = Company::create(['name' => 'Other Suspense Business', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'admin']);
        $this->actingAs($otherUser)->withSession([
            'current_tenant_id' => $otherCompany->id,
            'active_branch_id' => 'north',
            'active_branch_name' => 'North',
        ]);
        $this->getJson(route('pos.suspense.index', ['claim_token' => $token]))
            ->assertOk()
            ->assertJsonCount(0, 'sales');
        $this->postJson(route('pos.suspense.claim', $id), ['claim_token' => $token])->assertNotFound();
    }

    public function test_staff_without_a_special_permission_can_suspend_pos_sales(): void
    {
        $company = Company::create(['name' => 'Restricted Suspense', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'staff',
            'permissions_override' => [],
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => 'Staff Sale Product',
            'sku' => 'STAFF-SUSPENSE-001',
            'stock' => 3,
            'price' => 10,
            'purchase_price' => 5,
        ]);

        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ])
            ->getJson(route('pos.suspense.index'))
            ->assertOk()
            ->assertJsonCount(0, 'sales');

        $this->postJson(route('pos.suspense.store'), [
            'claim_token' => (string) Str::uuid(),
            'customer_name' => 'Walk-in Customer',
            'items' => [[
                'id' => $product->id,
                'name' => 'Staff Sale Product',
                'qty' => 1,
                'price' => 10,
            ]],
        ])->assertOk()
            ->assertJsonPath('message', 'Sale suspended.');
    }

    public function test_users_with_the_sales_suspense_role_permission_can_access_sales_suspense(): void
    {
        $company = Company::create(['name' => 'Enabled Suspense', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'staff',
            'permissions_override' => ['sales.pos.suspend'],
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->getJson(route('pos.suspense.index'))
            ->assertOk()
            ->assertJsonCount(0, 'sales');
    }

    public function test_missing_suspense_installation_returns_an_actionable_error(): void
    {
        $company = Company::create(['name' => 'Suspense Setup', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware();
        Schema::dropIfExists('pos_suspended_sales');

        $this->getJson(route('pos.suspense.index'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Sales Suspense is not fully installed on this server. Deploy the latest code and run php artisan migrate --force.');
    }

    public function test_successful_checkout_removes_the_claimed_suspended_sale_atomically(): void
    {
        $company = Company::create(['name' => 'Suspense Checkout', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);
        $account = Account::create([
            'company_id' => $company->id,
            'name' => 'POS Cash',
            'code' => 'SUSPENSE-CASH',
            'type' => Account::TYPE_ASSET,
            'sub_type' => 'Cash',
            'opening_balance' => 0,
            'is_active' => true,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'Coffee',
            'sku' => 'SUSPENSE-CHECKOUT-COFFEE',
            'stock' => 5,
            'price' => 20,
            'purchase_price' => 10,
        ]);
        $token = (string) Str::uuid();
        $saved = $this->postJson(route('pos.suspense.store'), [
            'claim_token' => $token,
            'items' => [[
                'id' => $product->id,
                'name' => 'Coffee',
                'qty' => 1,
                'price' => 20,
                'tax' => 0,
            ]],
        ])->assertOk();
        $suspendedSaleId = (int) $saved->json('id');
        $this->postJson(route('pos.suspense.claim', $suspendedSaleId), ['claim_token' => $token])->assertOk();

        $response = $this->postJson(route('sales.store'), [
            'payment_method' => 'Cash',
            'total' => 20,
            'paid' => 20,
            'deposit_account_id' => $account->id,
            'suspended_sale_id' => $suspendedSaleId,
            'suspension_claim_token' => $token,
            'items' => [[
                'id' => $product->id,
                'qty' => 1,
                'price' => 20,
                'unitType' => 'unit',
                'stockUnits' => 1,
            ]],
        ]);

        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertDatabaseHas('sales', ['id' => $response->json('sale_id'), 'company_id' => $company->id]);
        $this->assertDatabaseMissing('pos_suspended_sales', ['id' => $suspendedSaleId]);
    }
}
