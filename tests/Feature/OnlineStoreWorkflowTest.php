<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockExpiredDemoUser;
use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\Company;
use App\Models\OnlineStoreOrder;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineStoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            SubscriptionActive::class,
            CheckPlanAccess::class,
            RequireActiveBranch::class,
            EnforceDeviceSessionLimit::class,
            ForceLogoutExpiredSession::class,
            BlockExpiredDemoUser::class,
        ]);
    }

    public function test_public_store_lists_only_its_company_products_and_reserves_stock(): void
    {
        [$user, $company] = $this->workspace('Fresh Market');
        [$foreignUser, $foreignCompany] = $this->workspace('Foreign Market');
        $categoryId = $this->category($user->id, $company->id, 'Fruit');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Orange Crate', 'ORG-001', 10, 12500);
        $this->product($foreignUser->id, $foreignCompany->id, $categoryId, 'Foreign Apples', 'APL-999', 8, 9000);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'fresh-market',
            'store_name' => 'Fresh Market',
            'is_enabled' => true,
        ]);

        $this->get(route('storefront.catalogue', 'fresh-market'))
            ->assertOk()
            ->assertSee('Orange Crate')
            ->assertDontSee('Foreign Apples');

        $this->post(route('storefront.orders.store', 'fresh-market'), [
            'customer_name' => 'Ada Buyer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 2],
        ])->assertSessionHasNoErrors()->assertSessionHas('store_order_success');

        $this->assertDatabaseHas('online_store_orders', [
            'company_id' => $company->id,
            'customer_name' => 'Ada Buyer',
            'total_amount' => 25000,
            'status' => 'pending_confirmation',
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 8, 'stock_quantity' => 8]);
    }

    public function test_custom_domain_resolves_the_enabled_store(): void
    {
        [$user, $company] = $this->workspace('Mapped Store');
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'mapped-store',
            'custom_domain' => 'shop.mapped-store.test',
            'store_name' => 'Mapped Store',
            'is_enabled' => true,
        ]);

        $this->get('http://shop.mapped-store.test/store')
            ->assertOk()
            ->assertSee('Mapped Store');
    }

    public function test_cancelling_an_online_order_restores_reserved_stock(): void
    {
        [$user, $company] = $this->workspace('Cancellation Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Returnable Box', 'BOX-001', 10, 2500);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'cancellation-store',
            'store_name' => 'Cancellation Store',
            'is_enabled' => true,
        ]);

        $this->post(route('storefront.orders.store', 'cancellation-store'), [
            'customer_name' => 'Buyer One',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 3],
        ])->assertSessionHasNoErrors();

        $order = OnlineStoreOrder::withoutGlobalScopes()->firstOrFail();
        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'cancelled'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('online_store_orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10, 'stock_quantity' => 10]);
    }

    public function test_foreign_workspace_cannot_update_an_online_order(): void
    {
        [$owner, $ownerCompany] = $this->workspace('Order Owner');
        [$other, $otherCompany] = $this->workspace('Other Workspace');
        $order = OnlineStoreOrder::withoutGlobalScopes()->create([
            'company_id' => $ownerCompany->id,
            'order_number' => 'WEB-FOREIGN-001',
            'customer_name' => 'Customer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'status' => 'pending_confirmation',
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);

        $this->actingAs($other)
            ->withSession(['current_tenant_id' => $otherCompany->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'fulfilled'])
            ->assertNotFound();

        $this->assertSame('pending_confirmation', $order->fresh()->status);
    }

    private function workspace(string $name): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => $name,
            'status' => 'active',
            'plan' => 'Enterprise Yearly',
            'currency_code' => 'NGN',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();
        return [$user, $company];
    }

    private function category(int $userId, int $companyId, string $name): int
    {
        return DB::table('categories')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function product(int $userId, int $companyId, int $categoryId, string $name, string $sku, int $stock, float $price): int
    {
        return DB::table('products')->insertGetId([
            'user_id' => $userId,
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'purchase_price' => 5000,
            'stock' => $stock,
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
