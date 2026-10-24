<?php

namespace Tests\Feature\Orders;

use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderEntryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_form_lists_scoped_suppliers_and_saves_all_item_rows(): void
    {
        $company = Company::create(['name' => 'Order Entry Business']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'Scoped Supplier',
        ]);
        $firstProduct = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'First Product',
            'sku' => 'ORDER-FIRST',
            'purchase_price' => 10,
            'price' => 15,
            'stock' => 8,
        ]);
        $secondProduct = Product::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => 'hq',
            'branch_name' => 'Headquarters',
            'name' => 'Second Product',
            'sku' => 'ORDER-SECOND',
            'purchase_price' => 20,
            'price' => 30,
            'stock' => 4,
        ]);

        $this->withViewErrors([])->get(route('add-purchases-order'))
            ->assertOk()
            ->assertSee('Scoped Supplier')
            ->assertSee('First Product');

        $this->post(route('purchase-orders.store'), [
            'purchase_id' => 'PO-ENTRY-01',
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2, 'rate' => 10],
                ['product_id' => $secondProduct->id, 'quantity' => 3, 'rate' => 20],
            ],
        ])->assertRedirect(route('purchase-orders'))->assertSessionHas('success');

        $purchase = Purchase::where('purchase_no', 'PO-ENTRY-01')->firstOrFail();
        $this->assertSame((float) 80, (float) $purchase->total_amount);
        $this->assertSame(2, $purchase->items()->count());
        $this->assertSame(0, (int) $purchase->items()->sum('received_qty'));

        $this->get(route('edit-purchases-order', $purchase->id))
            ->assertOk()
            ->assertSee('Edit Purchase Order')
            ->assertSee('Scoped Supplier');

        $updatePayload = [
            'purchase_id' => 'PO-ENTRY-01',
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 1, 'rate' => 10],
            ],
        ];
        $this->put(route('purchase-orders.update', $purchase->id), $updatePayload)
            ->assertRedirect(route('purchase-orders'))
            ->assertSessionHas('success');
        $this->assertSame(10.0, (float) $purchase->fresh()->total_amount);
        $this->assertSame(1, $purchase->fresh()->items()->count());

        DB::table('purchase_items')->where('purchase_id', $purchase->id)->update(['received_qty' => 1]);
        $this->put(route('purchase-orders.update', $purchase->id), $updatePayload)
            ->assertSessionHasErrors('items');

        $this->assertSame(8.0, (float) $firstProduct->fresh()->stock);
        $this->assertSame(4.0, (float) $secondProduct->fresh()->stock);
    }

    public function test_sales_create_route_uses_the_complete_invoice_order_form(): void
    {
        $company = Company::create(['name' => 'Sales Order Entry Business', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_scope' => 'all',
        ]);
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));

        $response = $this->withViewErrors([])->get(route('sales.create'));

        $response->assertOk()
            ->assertViewIs('Sales.Invoices.create-invoices')
            ->assertSee('Add Invoice')
            ->assertSee(route('invoices.store'));
    }
}
