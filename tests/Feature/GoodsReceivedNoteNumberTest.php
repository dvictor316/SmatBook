<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoodsReceivedNoteNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_number_generation_checks_records_across_tenants(): void
    {
        $company = Company::create(['name' => 'Current GRN Workspace']);
        $otherCompany = Company::create(['name' => 'Other GRN Workspace']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'name' => 'Current Supplier',
        ]);
        $otherSupplier = Supplier::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Supplier',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'name' => 'GRN Test Product',
            'sku' => 'GRN-TEST-PRODUCT',
            'purchase_price' => 10,
            'price' => 15,
            'stock' => 0,
            'stock_quantity' => 0,
        ]);
        $purchase = Purchase::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'purchase_no' => 'PO-GRN-TEST',
            'supplier_id' => $supplier->id,
            'total_amount' => 50,
            'status' => 'pending',
        ]);
        $purchaseItem = PurchaseItem::create([
            'company_id' => $company->id,
            'branch_id' => 'hq',
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'qty' => 5,
            'unit_price' => 10,
        ]);
        DB::table('goods_received_notes')->insert([
            'company_id' => $otherCompany->id,
            'grn_number' => 'GRN-00001',
            'supplier_id' => $otherSupplier->id,
            'received_date' => now()->toDateString(),
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ])->post(route('grn.store'), [
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchase->id,
            'received_date' => now()->toDateString(),
            'items' => [[
                'purchase_item_id' => $purchaseItem->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'ordered_quantity' => 5,
                'received_quantity' => 5,
                'unit_cost' => 10,
            ]],
        ])->assertRedirect(route('grn.index'))->assertSessionHas('success');

        $grn = GoodsReceivedNote::withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->firstOrFail();
        $this->assertSame('GRN-00002', $grn->grn_number);
        $this->assertDatabaseHas('grn_items', [
            'grn_id' => $grn->id,
            'product_id' => $product->id,
            'received_quantity' => 5,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'id' => $purchaseItem->id,
            'received_qty' => 5,
        ]);
    }
}
