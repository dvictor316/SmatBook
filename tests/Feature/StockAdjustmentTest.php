<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_and_verified_legacy_opening_stock_post_once_to_the_original_branch(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id, 'owner_id' => $user->id, 'name' => 'Opening Inventory Company',
        ]);
        $user->update(['company_id' => $company->id]);
        $category = DB::table('categories')->insertGetId(['name' => 'Opening Cost Category']);
        $attributes = [
            'user_id' => $user->id, 'company_id' => $company->id, 'category_id' => $category,
            'name' => 'Opening Inventory', 'price' => 20, 'purchase_price' => 10,
            'stock' => 30, 'stock_quantity' => 30, 'status' => 'active',
        ];
        $newProduct = Product::create($attributes + ['sku' => 'OPENING-NEW', 'branch_id' => 'hq', 'branch_name' => 'Headquarters']);
        $this->assertDatabaseHas('transactions', [
            'reference' => 'PROD-OB-'.$newProduct->id, 'company_id' => $company->id, 'branch_id' => 'hq', 'debit' => 300,
            'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
        ]);

        $legacy = Product::create($attributes + ['sku' => 'OPENING-LEGACY']);
        $openingDate = now()->subDays(2);
        DB::table('inventory_history')->where('product_id', $legacy->id)->update([
            'branch_id' => 'other', 'branch_name' => 'Other Branch', 'created_at' => $openingDate,
        ]);
        $command = ['company' => $company->id, '--product' => $legacy->id];
        $this->artisan('ledger:post-inventory-openings', $command)
            ->expectsOutputToContain('Would post')->assertSuccessful();
        $this->assertDatabaseMissing('transactions', ['reference' => 'PROD-OB-'.$legacy->id]);
        $this->artisan('ledger:post-inventory-openings', $command + ['--apply' => true])->assertSuccessful();
        $this->artisan('ledger:post-inventory-openings', $command + ['--apply' => true])
            ->expectsOutputToContain('Already recorded')->assertSuccessful();
        $legacyJournal = DB::table('transactions')->where('reference', 'PROD-OB-'.$legacy->id)
            ->where('branch_id', 'other')->whereDate('transaction_date', $openingDate->toDateString());
        $this->assertSame(2, $legacyJournal->count());
        $this->assertEquals(300, $legacyJournal->sum('debit'));
        $this->assertEquals(300, $legacyJournal->sum('credit'));
        $this->assertDatabaseHas('products', ['id' => $legacy->id, 'stock' => 30]);

        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id, 'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters', 'active_branch_scope' => 'branch',
        ])->get(route('balance-sheet'))->assertOk()
            ->assertViewHas('totalAssets', 300.0)
            ->assertViewHas('totalEquity', 300.0);
    }

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
        $purchaseItemId = DB::table('purchase_items')->insertGetId(array_intersect_key([
            'purchase_id' => $purchaseId,
            'product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 100,
            'unit_type' => 'carton',
            'conversion_factor' => 1000,
            'stock_units' => 2000,
            'company_id' => $company->id,
            'created_at' => $purchaseTime,
            'updated_at' => $purchaseTime,
        ], array_flip(Schema::getColumnListing('purchase_items'))));
        $stockInId = DB::table('inventory_history')->insertGetId(array_intersect_key([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 2000,
            'type' => 'in',
            'reference' => 'Stock In',
            'created_at' => $purchaseTime,
            'updated_at' => $purchaseTime,
        ], array_flip(Schema::getColumnListing('inventory_history'))));

        $historyResponse = $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id])
            ->get(route('inventory.history', $product->id))
            ->assertOk();
        $historyResponse->assertSee('Quantity (transaction unit)')
            ->assertSee('Stock change (pcs)')
            ->assertSee('+2')
            ->assertSee('carton');
        $historyResponse->assertViewHas('inventoryHistories', function ($histories) {
                $purchaseMovements = $histories->where('reference', 'PUR-CONVERSION-1000');

                return $purchaseMovements->count() === 1
                    && (float) $purchaseMovements->first()->quantity === 2000.0
                    && (float) $purchaseMovements->first()->display_quantity === 2.0
                    && strtolower((string) $purchaseMovements->first()->display_unit_label) === 'carton'
                    && $histories->first()->running_balance === 2000.0;
            });
        $historyResponse->assertSee('Historical balances need review')->assertDontSee('Unavailable');

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
        $this->assertSame(2000.0, (float) $summary->total_in, json_encode($summary));
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

        $marker = 'Purchase stock backfill item '.$purchaseItemId;
        DB::table('inventory_history')->where('id', $stockInId)->update([
            'reference' => $marker,
            'created_at' => now(),
        ]);
        $this->withoutMiddleware()->withSession(['current_tenant_id' => $company->id])
            ->get(route('inventory.history', $product->id))->assertOk()
            ->assertViewHas('totalIn', 3000.0)
            ->assertViewHas('inventoryHistories', fn ($rows) => $rows->count() === 2
                && $rows->first()->reference === $marker
                && $rows->first()->running_balance === 2000.0
                && !$rows->contains('reference', 'PUR-CONVERSION-1000'));
    }

    public function test_history_uses_recorded_purchase_units_and_excludes_pending_orders_without_changing_stock(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id, 'owner_id' => $user->id,
            'name' => 'History Reconciliation', 'status' => 'active',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'History Reconciliation', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $product = Product::create([
            'user_id' => $user->id, 'company_id' => $company->id,
            'name' => 'Test Stock', 'sku' => 'HISTORY-RECONCILE', 'category_id' => $categoryId,
            'price' => 20, 'purchase_price' => 10, 'stock' => 0, 'stock_quantity' => 0,
            'base_unit_name' => 'pcs', 'unit_type' => 'unit', 'status' => 'active',
            'branch_id' => 'hq', 'branch_name' => 'Headquarters',
        ]);
        DB::table('products')->where('id', $product->id)->update([
            'stock' => 67, 'stock_quantity' => 67, 'purchase_unit_id' => 1, 'conversion_rate' => 125,
            'unit_type' => 'carton', 'units_per_carton' => 125,
        ]);
        foreach ([
            ['PUR-RECEIVED', 'received', 50, 'pcs', 1, null],
            ['PUR-LEGACY', 'received', 3, null, 1, null],
            ['PUR-FACTOR', 'received', 2, null, 4, null],
            ['PUR-SAVED', 'received', 2, null, 3, 6],
            ['PO-PENDING', 'pending', 20, 'pcs', 1, null],
        ] as [$number, $status, $quantity, $unit, $factor, $stockUnits]) {
            $purchaseId = DB::table('purchases')->insertGetId(array_intersect_key([
                'purchase_no' => $number, 'status' => $status, 'total_amount' => 100,
                'company_id' => $company->id, 'user_id' => $user->id,
                'branch_id' => 'hq', 'branch_name' => 'Headquarters',
                'created_at' => now(), 'updated_at' => now(),
            ], array_flip(Schema::getColumnListing('purchases'))));
            DB::table('purchase_items')->insert(array_intersect_key([
                'purchase_id' => $purchaseId, 'product_id' => $product->id, 'qty' => $quantity,
                'unit_price' => 10, 'unit_type' => $unit, 'conversion_factor' => $factor,
                'stock_units' => $stockUnits, 'company_id' => $company->id,
                'created_at' => now(), 'updated_at' => now(),
            ], array_flip(Schema::getColumnListing('purchase_items'))));
        }

        $response = $this->actingAs($user)->withoutMiddleware()
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters'])
            ->get(route('inventory.history', $product->id))->assertOk()
            ->assertSee('PUR-RECEIVED')->assertDontSee('PO-PENDING')
            ->assertViewHas('historyReconciles', true)->assertViewHas('openingBalance', 0.0);
        $response->assertViewHas('inventoryHistories', function ($rows) {
            $purchase = $rows->firstWhere('reference', 'PUR-RECEIVED');
            $legacy = $rows->firstWhere('reference', 'PUR-LEGACY');
            $factor = $rows->firstWhere('reference', 'PUR-FACTOR');
            $saved = $rows->firstWhere('reference', 'PUR-SAVED');
            return $purchase && (float) $purchase->quantity === 50.0
                && $legacy && (float) $legacy->quantity === 3.0
                && $factor && (float) $factor->quantity === 8.0 && $factor->display_unit_label === 'unknown unit'
                && $saved && (float) $saved->quantity === 6.0 && $saved->display_unit_label === 'unknown unit'
                && (float) $rows->first()->running_balance === 67.0;
        });
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 67, 'stock_quantity' => 67]);

        DB::table('products')->where('id', $product->id)->update(['stock' => 20, 'stock_quantity' => 20]);
        $this->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters'])
            ->get(route('inventory.history', $product->id))->assertOk()
            ->assertSee('Historical balances need review')->assertSee('Balances and values are estimates')
            ->assertDontSee('Unavailable')
            ->assertViewHas('historyReconciles', false)->assertViewHas('openingBalance', null)
            ->assertViewHas('inventoryHistories', fn ($rows) => $rows->first()->running_balance === 20.0
                && $rows->first()->stock_value === 200.0
                && $rows->firstWhere('reference', 'PUR-RECEIVED')->running_balance === 3.0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 20, 'stock_quantity' => 20]);

        $historyId = DB::table('inventory_history')->insertGetId(array_intersect_key([
            'user_id' => $user->id, 'company_id' => $company->id, 'product_id' => $product->id,
            'quantity' => 60, 'type' => 'out', 'reference' => 'Earlier stock out',
            'branch_id' => 'hq', 'branch_name' => 'Headquarters',
            'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ], array_flip(Schema::getColumnListing('inventory_history'))));
        $this->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters'])
            ->get(route('inventory.history', $product->id))->assertOk()
            ->assertSee('Historical balances need review')
            ->assertViewHas('historyReconciles', false)
            ->assertViewHas('inventoryHistories', fn ($rows) => $rows->first()->running_balance === 20.0
                && $rows->last()->running_balance === -47.0
                && $rows->last()->stock_value === -470.0);
        $this->assertDatabaseHas('inventory_history', ['id' => $historyId, 'quantity' => 60]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 20, 'stock_quantity' => 20]);
    }

    public function test_history_keeps_recorded_sale_outflows_after_returns_and_deleted_sale_items(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::create(['name' => 'Stock Sale History']);
        $user->update(['company_id' => $company->id]);
        $category = DB::table('categories')->insertGetId(['name' => 'Stock Sales']);
        $product = Product::create([
            'company_id' => $company->id, 'user_id' => $user->id, 'category_id' => $category,
            'name' => 'Sale stock', 'sku' => 'IMMUTABLE-STOCK', 'stock' => 30, 'stock_quantity' => 30,
            'purchase_price' => 10, 'price' => 15, 'status' => 'active',
        ]);
        DB::table('inventory_history')->where('product_id', $product->id)->update(['created_at' => now()->subDays(4)]);
        $sale = Sale::create([
            'company_id' => $company->id, 'user_id' => $user->id, 'terminal_id' => 'POS1',
            'invoice_no' => 'INV-STOCK-RETURN', 'total' => 90, 'created_at' => now()->subDays(3),
        ]);
        $item = $sale->items()->create([
            'product_id' => $product->id, 'qty' => 6, 'stock_units' => 6, 'unit_price' => 15,
        ]);
        DB::table('sale_items')->where('id', $item->id)->update(['created_at' => now()->subDays(3)]);
        foreach ([
            ['Sales', 'out', 10, 3, null],
            ['Stock Return', 'in', 4, 2, null],
            ['Sales', 'out', 3, 1, json_encode(['sale_id' => 999, 'sale_item_id' => 999, 'invoice_no' => 'INV-DELETED'])],
            ['Sale Deletion', 'in', 3, 0, null],
        ] as [$reference, $type, $quantity, $daysAgo, $remarks]) {
            DB::table('inventory_history')->insert([
                'product_id' => $product->id, 'company_id' => $company->id, 'user_id' => $user->id,
                'reference' => $reference, 'type' => $type, 'quantity' => $quantity,
                'created_at' => now()->subDays($daysAgo), 'remarks' => $remarks,
            ]);
        }
        DB::table('products')->where('id', $product->id)->update(['stock' => 24, 'stock_quantity' => 24]);

        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id, 'active_branch_scope' => 'all',
        ])->get(route('inventory.history', $product->id))->assertOk()
            ->assertViewHas('openingBalance', 0.0)
            ->assertViewHas('inventoryHistories', fn ($rows) => $rows->count() === 5
                && (float) $rows->firstWhere('reference', 'INV-STOCK-RETURN')->running_balance === 20.0
                && (float) $rows->firstWhere('reference', 'INV-DELETED')->running_balance === 21.0
                && (float) $rows->first()->running_balance === 24.0);
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
