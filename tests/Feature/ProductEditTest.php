<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_product_stock_edit_saves_without_legacy_unit_fields(): void
    {
        $company = Company::create(['name' => 'Stock Edit Business']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $productId = DB::table('products')->insertGetId([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => 'Existing Stock',
            'sku' => 'EDIT-STOCK-1',
            'price' => 200,
            'purchase_price' => 100,
            'stock' => 5,
            'stock_quantity' => 5,
            'unit_type' => 'unit',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_branch_stocks')->insert([
            'company_id' => $company->id, 'product_id' => $productId,
            'branch_id' => 'hq', 'branch_name' => 'Headquarters',
            'quantity' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_branch_stocks')->insert([
            'company_id' => $company->id, 'product_id' => $productId,
            'branch_id' => 'other', 'branch_name' => 'Other Branch',
            'quantity' => 4, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->withoutMiddleware([
            SubscriptionActive::class,
            RequireActiveBranch::class,
            CheckPlanAccess::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ])->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);

        $this->put(route('inventory.Products.update', $productId), [
            'name' => 'Existing Stock', 'sku' => 'EDIT-STOCK-1',
            'price' => 200, 'purchase_price' => 100,
            'stock' => 3.125, 'unit_type' => 'unit', 'status' => 'active',
        ])->assertRedirect(route('product-list'))->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 3.125, 'stock_quantity' => 3.125, 'base_unit_name' => 'pcs']);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $productId, 'branch_id' => 'hq', 'quantity' => 3.125]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $productId, 'branch_id' => 'other', 'quantity' => 4]);

        $editUrl = route('inventory.Products.edit', $productId);
        $this->from($editUrl)->put(route('inventory.Products.update', $productId), [
            'name' => 'Existing Stock', 'price' => '', 'purchase_price' => 100,
            'stock' => 3.125, 'unit_type' => 'unit', 'status' => 'active',
        ])->assertRedirect($editUrl)->assertSessionHasErrors('price');
        $this->get($editUrl)->assertOk()->assertSee('Stock details were not saved.');
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 3.125]);

        DB::statement("CREATE TRIGGER fail_branch_stock_update BEFORE UPDATE ON product_branch_stocks BEGIN SELECT RAISE(ABORT, 'forced save failure'); END");
        $this->from($editUrl)->put(route('inventory.Products.update', $productId), [
            'name' => 'Existing Stock', 'price' => 200, 'purchase_price' => 100,
            'stock' => 2, 'unit_type' => 'unit', 'status' => 'active',
        ])->assertRedirect($editUrl)->assertSessionHasErrors('save');
        $this->get($editUrl)->assertOk()->assertSee('Stock details could not be saved.');
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 3.125]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $productId, 'quantity' => 3.125]);
    }
}
