<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id])
            ->from(route('inventory.history', $product->id))
            ->post(route('inventory.history.update'), [
                'id' => $historyId,
                'quantity' => 999,
                'type' => 'out',
            ])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_history', ['id' => $historyId, 'quantity' => 30, 'type' => 'in']);

        $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id])
            ->from(route('inventory.history', $product->id))
            ->post(route('inventory.history.delete'), ['id' => $historyId])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_history', ['id' => $historyId, 'quantity' => 30, 'type' => 'in']);
    }

    public function test_purchase_history_uses_saved_base_quantity_once_and_stock_report_is_available(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => 'Conversion History Company',
            'status' => 'active',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Conversion History Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'Carton Conversion Product',
            'sku' => 'HISTORY-CONVERSION-1000',
            'price' => 200,
            'purchase_price' => 100,
            'stock' => 1000,
            'stock_quantity' => 1000,
            'base_unit_name' => 'pcs',
            'unit_type' => 'unit',
            'conversion_rate' => 1,
            'category_id' => $categoryId,
            'status' => 'active',
        ]);
        $history = DB::table('inventory_history')
            ->where('product_id', $product->id)
            ->where('reference', 'Initial Stock')
            ->first();
        DB::table('inventory_history')->where('id', $history->id)->update([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        DB::table('products')->where('id', $product->id)->update([
            'stock' => 2000,
            'stock_quantity' => 2000,
        ]);

        $purchaseTime = now()->subSeconds(30);
        $purchaseId = DB::table('purchases')->insertGetId(array_intersect_key([
            'purchase_no' => 'PUR-CONVERSION-1000',
            'supplier_id' => null,
            'total_amount' => 100,
            'status' => 'received',
            'company_id' => $company->id,
            'user_id' => $user->id,
            'created_at' => $purchaseTime,
            'updated_at' => $purchaseTime,
        ], array_flip(Schema::getColumnListing('purchases'))));
        DB::table('purchase_items')->insert(array_intersect_key([
            'purchase_id' => $purchaseId,
            'product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 100,
            'unit_type' => 'carton',
            'conversion_factor' => 1000,
            'stock_units' => 1000,
            'company_id' => $company->id,
            'created_at' => $purchaseTime,
            'updated_at' => $purchaseTime,
        ], array_flip(Schema::getColumnListing('purchase_items'))));
        DB::table('inventory_history')->insert(array_intersect_key([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'type' => 'in',
            'reference' => 'Stock In',
            'created_at' => $purchaseTime,
            'updated_at' => $purchaseTime,
        ], array_flip(Schema::getColumnListing('inventory_history'))));

        $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id])
            ->get(route('inventory.history', $product->id))
            ->assertOk()
            ->assertViewHas('inventoryHistories', function ($histories) {
                $purchaseMovements = $histories->where('reference', 'PUR-CONVERSION-1000');

                return $purchaseMovements->count() === 1
                    && (float) $purchaseMovements->first()->quantity === 1000.0
                    && (float) $histories->first()->running_balance === 2000.0;
            });

        $this->withMiddleware();
        $reportResponse = $this->actingAs($user)->withSession(['current_tenant_id' => $company->id])
            ->get(route('reports.stock-movements', [
            'from_date' => now()->subDay()->toDateString(),
            'to_date' => now()->toDateString(),
            'product_id' => $product->id,
            'branch_scope' => 'all',
            ]));
        $reportResponse->assertOk()->assertSee('Stock Movement Report');
        $summary = $reportResponse->viewData('summary');
        $this->assertSame(1, (int) $summary->movement_count, json_encode($summary));
        $this->assertSame(1000.0, (float) $summary->total_in, json_encode($summary));
        $csvResponse = $this->get(route('reports.stock-movements', [
            'format' => 'csv',
            'from_date' => now()->subDay()->toDateString(),
            'to_date' => now()->toDateString(),
            'product_id' => $product->id,
            'branch_scope' => 'all',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Stock In', $csvResponse->streamedContent());

        $this->get(route('reports.stock', ['branch_scope' => 'all']))
            ->assertOk()
            ->assertSee('Live snapshot as at')
            ->assertSee('Stock Movements');
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
