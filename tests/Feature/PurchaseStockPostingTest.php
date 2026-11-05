<?php

namespace Tests\Feature;

use App\Models\{Company, Product, ProductBranchStock, ProductUnit, Purchase, Setting, Supplier, User};
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

    public function test_zero_quantity_transfer_adds_branch_catalog_visibility_without_stock_history(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);
        $historyCount = DB::table('inventory_history')->where('product_id', $product->id)->count();

        $this->post(route('inventory.transfer'), [
            'product_id' => $product->id,
            'from_branch_id' => 'hq',
            'to_branch_id' => 'branch-b',
            'quantity' => 0,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => 'branch-b',
            'quantity' => 0,
        ]);
        $this->assertSame($historyCount, DB::table('inventory_history')->where('product_id', $product->id)->count());
        $this->get(route('inventory.transfer-products.search', ['q' => 'Ri']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->get(route('inventory.transfer-products.search', ['q' => 'ice']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->get(route('inventory.transfer-products.search', ['q' => 'CHASE-STOCK']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);

        $this->withSession(['active_branch_id' => 'branch-b', 'active_branch_name' => 'Branch B'])
            ->get(route('product-list'))
            ->assertOk()
            ->assertViewHas('productRows', fn ($rows) => $rows->contains('id', $product->id));
    }

    public function test_positive_transfer_decreases_source_and_increases_destination_once(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);

        $this->post(route('inventory.transfer'), [
            'product_id' => $product->id,
            'from_branch_id' => 'hq',
            'to_branch_id' => 'branch-b',
            'quantity' => 2,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => 'hq',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => 'branch-b',
            'quantity' => 2,
        ]);
        $this->assertSame(2, DB::table('inventory_history')
            ->where('product_id', $product->id)
            ->where('reference', 'like', 'Branch Transfer%')
            ->count());
    }

    public function test_positive_transfer_seeds_a_missing_source_branch_before_debiting_it(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);
        ProductBranchStock::where('product_id', $product->id)->where('branch_id', 'hq')->delete();

        $this->post(route('inventory.transfer'), [
            'product_id' => $product->id,
            'from_branch_id' => 'hq',
            'to_branch_id' => 'branch-b',
            'quantity' => 2,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => 'hq',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $product->id,
            'branch_id' => 'branch-b',
            'quantity' => 2,
        ]);
    }

    public function test_bulk_catalog_add_is_idempotent_and_transfer_totals_cover_filtered_pages(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);
        $secondProduct = Product::create([
            'company_id' => $product->company_id,
            'user_id' => auth()->id(),
            'name' => 'Beans',
            'sku' => 'BULK-CATALOG-TEST',
            'stock' => 0,
            'price' => 100,
            'purchase_price' => 50,
        ]);

        $payload = [
            'add_all_products' => 1,
            'from_branch_id' => 'hq',
            'to_branch_id' => 'branch-b',
        ];
        $this->post(route('inventory.transfer'), $payload)->assertSessionHas('success');
        $this->post(route('inventory.transfer'), $payload)->assertSessionHas('success');

        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'branch_id' => 'branch-b', 'quantity' => 0]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $secondProduct->id, 'branch_id' => 'branch-b', 'quantity' => 0]);
        $this->assertSame(2, DB::table('stock_transfer_audits')->where('to_branch_id', 'branch-b')->count());

        $this->get(route('inventory.transfer-audit', ['from_date' => now()->toDateString(), 'per_page' => 1]))
            ->assertOk()
            ->assertViewHas('audits', fn ($audits) => $audits->count() === 1 && $audits->total() === 2)
            ->assertViewHas('summary', fn ($summary) => (int) $summary->transfer_count === 2
                && (int) $summary->product_count === 2
                && (float) $summary->quantity_total === 0.0);
    }

    public function test_bulk_catalog_option_is_preserved_when_branch_validation_requires_correction(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);

        $this->from(route('product-list'))
            ->post(route('inventory.transfer'), ['add_all_products' => 1])
            ->assertRedirect(route('product-list'))
            ->assertSessionHasErrors(['from_branch_id', 'to_branch_id'])
            ->assertSessionDoesntHaveErrors('quantity')
            ->assertSessionHas('_old_input.add_all_products', 1);

        $this->get(route('product-list'))
            ->assertOk()
            ->assertSee('transferAllProducts.addEventListener(\'change\', syncTransferMode);', false)
            ->assertSee('quantityInput.required = !transferAllProducts.checked;', false)
            ->assertSee('syncTransferMode();', false);
    }

    public function test_invalid_transfer_inputs_show_actionable_errors_and_reopen_the_transfer_form(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);

        $this->from(route('product-list'))
            ->post(route('inventory.transfer'), [
                'product_id' => 'not-a-product',
                'from_branch_id' => 'hq',
                'to_branch_id' => 'branch-b',
                'quantity' => 'not-a-number',
            ])
            ->assertRedirect(route('product-list'))
            ->assertSessionHas('stock_transfer_validation_failed', true)
            ->assertSessionHasErrors([
                'product_id' => 'Please select a product from the search results.',
                'quantity' => 'Enter a valid numeric quantity (0 or more).',
            ]);

        $this->get(route('product-list'))
            ->assertOk()
            ->assertSee('Please select a product from the search results.')
            ->assertSee('Enter a valid numeric quantity (0 or more).')
            ->assertSee('bootstrap.Modal.getOrCreateInstance(transferModal).show()', false);
    }

    public function test_transfer_modal_scrolls_and_same_branch_validation_is_actionable(): void
    {
        [$product] = $this->workspace();
        $this->configureTransferBranches($product);

        $this->from(route('product-list'))
            ->post(route('inventory.transfer'), [
                'product_id' => $product->id,
                'from_branch_id' => 'hq',
                'to_branch_id' => 'hq',
                'quantity' => 1,
            ])
            ->assertRedirect(route('product-list'))
            ->assertSessionHasErrors([
                'to_branch_id' => 'Choose a destination branch different from the source branch.',
            ])
            ->assertSessionHas('_old_input.from_branch_id', 'hq')
            ->assertSessionHas('_old_input.to_branch_id', 'hq');

        $this->get(route('product-list'))
            ->assertOk()
            ->assertSee('modal-dialog-centered modal-dialog-scrollable', false)
            ->assertSee('Choose a destination branch different from the source branch.')
            ->assertSee('syncDestinationBranches();', false);
    }

    private function configureTransferBranches(Product $product): void
    {
        Setting::create([
            'key' => 'branches_json_company_' . $product->company_id,
            'value' => json_encode([
                ['id' => 'hq', 'name' => 'Headquarters', 'is_active' => true],
                ['id' => 'branch-b', 'name' => 'Branch B', 'is_active' => true],
            ]),
        ]);
        session([
            'current_tenant_id' => $product->company_id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
            'user_plan' => 'professional',
        ]);
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
