<?php

namespace Tests\Feature;

use App\Models\{Company, Product, ProductBranchStock, ProductUnit, Purchase, Supplier, User};
use App\Support\BranchInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseStockPostingTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'Purchase stock checks']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters',
        ]);
        $supplier = Supplier::create(['company_id' => $company->id, 'user_id' => $user->id, 'name' => 'Test Supplier']);
        $product = Product::create(['company_id' => $company->id, 'user_id' => $user->id, 'name' => 'Rice',
            'sku' => 'PURCHASE-STOCK', 'stock' => 5, 'price' => 150, 'purchase_price' => 100,
            'unit_type' => 'unit', 'base_unit_name' => 'pcs']);
        ProductBranchStock::create(['product_id' => $product->id, 'company_id' => $company->id,
            'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'quantity' => 5]);
        return [$product, ['purchase_no' => 'PUR-STOCK-TEST', 'supplier_id' => $supplier->id,
            'products' => [['product_id' => $product->id, 'quantity' => 2, 'rate' => 100, 'unit' => 'pcs']]]];
    }

    private function assertStock(Product $product, float $global, float $branch): void
    {
        $product->refresh();
        $this->assertEquals($global, $product->stock);
        $this->assertEquals($global, $product->stock_quantity);
        $this->assertEquals($branch, app(BranchInventoryService::class)->getAvailableStock($product));
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'branch_id' => 'hq', 'quantity' => $branch]);
    }

    public function test_saved_purchases_add_up_and_edits_apply_only_the_quantity_difference(): void
    {
        [$product, $payload] = $this->workspace();
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 7, 7);
        $purchase = Purchase::where('purchase_no', 'PUR-STOCK-TEST')->firstOrFail();
        $payload['products'][0]['quantity'] = 4;
        $this->put(route('purchases.update', $purchase), $payload)->assertSessionHas('success');
        $this->assertStock($product, 9, 9);
        $payload['purchase_no'] = 'PUR-STOCK-SECOND';
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 13, 13);
        $this->get(route('product-list'))->assertOk()->assertViewHas('productRows',
            fn ($rows) => (float) $rows->firstWhere('id', $product->id)?->active_branch_stock === 13.0);
    }

    public function test_purchase_repairs_a_stale_legacy_stock_quantity_without_losing_the_addition(): void
    {
        [$product, $payload] = $this->workspace();
        DB::table('products')->where('id', $product->id)->update(['stock_quantity' => 1]);
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 7, 7);
    }

    public function test_purchase_receives_only_the_new_quantity_into_an_empty_branch(): void
    {
        [$product, $payload] = $this->workspace();
        ProductBranchStock::where('product_id', $product->id)->update(['quantity' => 0]);
        ProductBranchStock::create(['product_id' => $product->id, 'company_id' => $product->company_id,
            'branch_id' => 'other', 'branch_name' => 'Other branch', 'quantity' => 5]);
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 7, 2);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'branch_id' => 'other', 'quantity' => 5]);
    }

    public function test_deleting_a_carton_purchase_reverses_all_units_received(): void
    {
        [$product, $payload] = $this->workspace();
        ProductUnit::create(['company_id' => $product->company_id, 'user_id' => auth()->id(), 'product_id' => $product->id,
            'unit_name' => 'Carton', 'unit_symbol' => 'ctn', 'conversion_factor' => 12, 'status' => 'active']);
        $payload['products'][0]['unit'] = 'ctn';
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 29, 29);
        $purchase = Purchase::where('purchase_no', 'PUR-STOCK-TEST')->firstOrFail();
        $this->delete(route('purchases.destroy', $purchase))->assertSessionHas('success');
        $this->assertStock($product, 5, 5);
    }
    public function test_an_unchanged_purchase_can_be_edited_after_some_received_stock_has_been_sold(): void
    {
        [$product, $payload] = $this->workspace();
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $purchase = Purchase::where('purchase_no', 'PUR-STOCK-TEST')->firstOrFail();
        DB::table('products')->where('id', $product->id)->update(['stock' => 1, 'stock_quantity' => 1]);
        ProductBranchStock::where('product_id', $product->id)->update(['quantity' => 1]);
        $this->put(route('purchases.update', $purchase), $payload)->assertSessionHas('success');
        $this->assertStock($product, 1, 1);
    }

    public function test_reversing_an_empty_branch_receipt_does_not_display_another_branchs_stock(): void
    {
        [$product, $payload] = $this->workspace();
        ProductBranchStock::where('product_id', $product->id)->update(['quantity' => 0]);
        ProductBranchStock::create(['product_id' => $product->id, 'company_id' => $product->company_id,
            'branch_id' => 'other', 'branch_name' => 'Other branch', 'quantity' => 5]);
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $purchase = Purchase::where('purchase_no', 'PUR-STOCK-TEST')->firstOrFail();
        $this->delete(route('purchases.destroy', $purchase))->assertSessionHas('success');
        $this->assertStock($product, 5, 0);
    }

    public function test_a_failed_branch_update_rolls_back_the_purchase_and_product_stock(): void
    {
        [$product, $payload] = $this->workspace();
        DB::statement("CREATE TRIGGER fail_purchase_stock BEFORE UPDATE ON product_branch_stocks BEGIN SELECT RAISE(ABORT, 'forced branch stock failure'); END");
        $this->post(route('purchases.store'), $payload)->assertSessionHas('error');
        $this->assertDatabaseMissing('purchases', ['purchase_no' => 'PUR-STOCK-TEST']);
        $this->assertStock($product, 5, 5);
    }

    public function test_purchase_cannot_add_stock_to_another_businesss_product(): void
    {
        [, $payload] = $this->workspace();
        config(['tenancy.enforce_scopes_in_console' => true]);
        $otherCompany = Company::create(['name' => 'Other business']);
        $otherProduct = DB::table('products')->insertGetId([
            'company_id' => $otherCompany->id, 'user_id' => auth()->id(), 'name' => 'Private product', 'sku' => 'PRIVATE-STOCK',
            'price' => 100, 'purchase_price' => 50, 'stock' => 9, 'stock_quantity' => 9, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $payload['products'][0]['product_id'] = $otherProduct;
        $this->post(route('purchases.store'), $payload)->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $otherProduct, 'company_id' => $otherCompany->id, 'stock' => 9, 'stock_quantity' => 9]);
        $this->assertDatabaseMissing('purchases', ['purchase_no' => 'PUR-STOCK-TEST']);
    }

    public function test_a_legacy_purchase_product_and_branch_balance_keep_their_existing_quantity(): void
    {
        [$product, $payload] = $this->workspace();
        DB::table('products')->where('id', $product->id)->update(['company_id' => null]);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->update(['company_id' => null]);
        $this->post(route('purchases.store'), $payload)->assertSessionHas('success');
        $this->assertStock($product, 7, 7);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id,
            'company_id' => session('current_tenant_id'), 'branch_id' => 'hq', 'quantity' => 7]);
    }

}
