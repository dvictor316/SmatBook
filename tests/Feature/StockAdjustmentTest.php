<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_history_does_not_double_count_synthetic_purchase_mirrors(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => 'Inventory History Company',
            'status' => 'active',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'History Test Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'Opening Stock Product',
            'sku' => 'HISTORY-INITIAL-001',
            'price' => 20,
            'purchase_price' => 10,
            'stock' => 30,
            'stock_quantity' => 30,
            'category_id' => $categoryId,
            'status' => 'active',
        ]);

        $historyId = DB::table('inventory_history')->where('product_id', $product->id)->value('id');
        $this->assertDatabaseHas('inventory_history', [
            'id' => $historyId,
            'reference' => 'Initial Stock',
            'quantity' => 30,
        ]);
        $this->assertDatabaseHas('purchases', ['purchase_no' => 'AUTO-STK-' . $historyId]);

        $response = $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id])
            ->get(route('inventory.history', $product->id))
            ->assertOk()
            ->assertSee('Balance After Movement')
            ->assertSee('Initial Stock')
            ->assertDontSee('AUTO-STK-');
    }

    public function test_manual_stock_in_saves_stock_and_inventory_history(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => 'Stock Adjustment Company',
            'status' => 'active',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Adjustment Test Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'Adjustment Test Product',
            'sku' => 'ADJUSTMENT-TEST-001',
            'price' => 20,
            'purchase_price' => 10,
            'stock' => 5,
            'stock_quantity' => 5,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->withSession(['active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters'])
            ->post(route('inventory.adjust'), [
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 7, 'stock_quantity' => 7]);
        $this->assertDatabaseHas('inventory_history', [
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => 2,
            'company_id' => $company->id,
            'branch_id' => 'hq',
        ]);
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $productId,
            'branch_id' => 'hq',
            'quantity' => 7,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->withSession(['active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters'])
            ->from(route('inventory.Products'))
            ->post(route('inventory.adjust'), [
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => 20,
            ])
            ->assertRedirect(route('inventory.Products'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 7, 'stock_quantity' => 7]);
    }
}
