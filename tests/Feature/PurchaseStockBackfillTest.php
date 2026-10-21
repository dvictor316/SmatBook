<?php

namespace Tests\Feature;

use App\Models\{Company, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Artisan, DB};
use Tests\TestCase;

class PurchaseStockBackfillTest extends TestCase
{
    use RefreshDatabase;

    private string $backfillOutput = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fixture(string $registered = '2026-10-19 10:00:00', string $received = '2026-10-20 10:00:00'): array
    {
        $company = Company::create(['name' => 'Recent Client']);
        DB::table('companies')->where('id', $company->id)->update(['created_at' => $registered]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $product = Product::withoutEvents(fn () => Product::create(['company_id' => $company->id, 'user_id' => $user->id,
            'name' => 'Rice', 'sku' => 'BACKFILL-'.$company->id, 'price' => 200, 'purchase_price' => 100, 'stock' => 5, 'stock_quantity' => 5]));
        DB::table('product_branch_stocks')->insert(['product_id' => $product->id, 'company_id' => $company->id,
            'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'quantity' => 5, 'created_at' => $registered, 'updated_at' => $registered]);
        $purchase = DB::table('purchases')->insertGetId(['purchase_no' => 'PUR-CLIENT-'.$company->id, 'company_id' => $company->id,
            'user_id' => $user->id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'status' => 'received',
            'purchase_type' => 'inventory', 'total_amount' => 200, 'created_at' => $received, 'updated_at' => $received]);
        $item = DB::table('purchase_items')->insertGetId(['purchase_id' => $purchase, 'product_id' => $product->id,
            'company_id' => $company->id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'qty' => 2,
            'unit_price' => 100, 'unit_type' => 'pcs', 'stock_units' => 2, 'conversion_factor' => 1, 'created_at' => $received, 'updated_at' => $received]);
        $this->movement($product, 5, 'in', 'Initial Stock', $registered);
        return [$company, $product, $purchase, $item];
    }

    private function movement(Product $product, float $quantity, string $type, string $reference, string $at): void
    {
        DB::table('inventory_history')->insert(['product_id' => $product->id, 'company_id' => $product->company_id,
            'user_id' => $product->user_id, 'branch_id' => 'hq', 'branch_name' => 'Headquarters', 'quantity' => $quantity,
            'type' => $type, 'reference' => $reference, 'created_at' => $at, 'updated_at' => $at]);
    }

    private function runBackfill(array $options = []): int
    {
        $output = new \Symfony\Component\Console\Output\BufferedOutput;
        $status = Artisan::call('inventory:backfill-purchase-stock', $options + ['--date' => '2026-10-20'], $output);
        $this->backfillOutput = $output->fetch();
        return $status;
    }

    public function test_default_preview_never_changes_stock_and_apply_is_idempotent(): void
    {
        [, $product, , $item] = $this->fixture();
        $this->assertSame(0, $this->runBackfill());
        $this->assertStringContainsString('WOULD ADD', $this->backfillOutput);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5, 'stock_quantity' => 5]);
        $this->assertDatabaseCount('inventory_history', 1);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7, 'stock_quantity' => 7]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 7]);
        $this->assertDatabaseHas('inventory_history', ['reference' => 'Purchase stock backfill item '.$item, 'quantity' => 2]);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertStringContainsString('DONE', $this->backfillOutput);
        $this->assertDatabaseCount('inventory_history', 2);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
    }

    public function test_already_received_purchases_are_not_credited_twice(): void
    {
        [, $product] = $this->fixture();
        $this->movement($product, 2, 'in', 'Stock In', '2026-10-20 10:00:01');
        DB::table('products')->where('id', $product->id)->update(['stock' => 7, 'stock_quantity' => 1]);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->update(['quantity' => 7]);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertStringContainsString('POSTED', $this->backfillOutput);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7, 'stock_quantity' => 7]);
        $this->assertDatabaseCount('inventory_history', 2);
    }

    public function test_today_sales_are_preserved_when_yesterdays_missing_receipt_is_added(): void
    {
        [, $product] = $this->fixture();
        $this->movement($product, 1, 'out', 'Sales', '2026-10-21 09:00:00');
        DB::table('products')->where('id', $product->id)->update(['stock' => 4, 'stock_quantity' => 4]);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->update(['quantity' => 4]);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6, 'stock_quantity' => 6]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 6]);
    }

    public function test_receipt_day_uses_nigerian_midnight_and_excludes_other_registration_days(): void
    {
        [, $included] = $this->fixture(received: '2026-10-19 23:00:00');
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $included->id, 'stock' => 7]);
        [, $older] = $this->fixture(registered: '2026-10-18 10:00:00');
        [, $today] = $this->fixture(received: '2026-10-20 23:00:00');
        [, $before] = $this->fixture(received: '2026-10-19 22:59:59');
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        foreach ([$older, $today, $before] as $untouched) $this->assertDatabaseHas('products', ['id' => $untouched->id, 'stock' => 5]);
    }

    public function test_multiple_recent_clients_require_a_company_id_before_writing(): void
    {
        [$company, $first] = $this->fixture();
        [, $second] = $this->fixture();
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertStringContainsString('Several businesses match', $this->backfillOutput);
        foreach ([$first, $second] as $product) $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertSame(0, $this->runBackfill(['--company' => (string) $company->id, '--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $first->id, 'stock' => 7]);
        $this->assertDatabaseHas('products', ['id' => $second->id, 'stock' => 5]);
    }

    public function test_unverified_balances_and_unallocated_stock_ins_are_left_for_review(): void
    {
        [$company, $product] = $this->fixture();
        DB::table('products')->where('id', $product->id)->update(['stock' => 7, 'stock_quantity' => 7]);
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertStringContainsString('REVIEW', $this->backfillOutput);
        $this->assertDatabaseCount('inventory_history', 1);
        DB::table('products')->where('id', $product->id)->update(['stock' => 6, 'stock_quantity' => 6]);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->update(['quantity' => 6]);
        $this->movement($product, 1, 'in', 'Stock In', '2026-10-20 18:00:00');
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
        $this->assertDatabaseCount('inventory_history', 2);
    }

    public function test_orders_synthetic_receipts_and_assets_are_not_replayed(): void
    {
        [, $product, $purchase] = $this->fixture();
        foreach (['PO-ONLY', 'AUTO-STK-123', 'HIST-INV-123'] as $number) {
            DB::table('purchases')->where('id', $purchase)->update(['purchase_no' => $number]);
            $this->assertSame(0, $this->runBackfill(['--apply' => true]));
            $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        }
        DB::table('purchases')->where('id', $purchase)->update(['purchase_no' => 'PUR-ASSET', 'purchase_type' => 'asset']);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseCount('inventory_history', 1);
    }

    public function test_failure_during_audit_writing_rolls_back_stock_and_markers(): void
    {
        [, $product] = $this->fixture();
        DB::statement("CREATE TRIGGER fail_backfill BEFORE UPDATE ON products BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseCount('inventory_history', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 5]);
    }

    public function test_invalid_dates_and_conflicting_modes_are_rejected(): void
    {
        $this->assertSame(1, $this->runBackfill(['--date' => '2026-02-30']));
        $this->assertSame(1, $this->runBackfill(['--dry-run' => true, '--apply' => true]));
    }
    public function test_missing_branch_credit_is_restored_without_adding_company_stock_again(): void
    {
        [, $product] = $this->fixture();
        $this->movement($product, 2, 'in', 'Stock In', '2026-10-20 10:00:01');
        DB::table('products')->where('id', $product->id)->update(['stock' => 7, 'stock_quantity' => 7]);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 7]);
        $this->assertDatabaseHas('inventory_history', ['reference' => 'Purchase branch backfill 2026-10-20 hq', 'quantity' => 0]);
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseCount('inventory_history', 3);
    }

    public function test_edited_lines_are_reported_without_adding_stock(): void
    {
        [, $product, , $item] = $this->fixture();
        DB::table('purchase_items')->where('id', $item)->update(['created_at' => '2026-10-21 10:00:00']);
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseCount('inventory_history', 1);
    }

    public function test_stored_carton_units_are_used_and_missing_branch_rows_are_rebuilt(): void
    {
        [, $product, , $item] = $this->fixture();
        DB::table('purchase_items')->where('id', $item)->update(['stock_units' => 24, 'conversion_factor' => 12, 'unit_type' => 'ctn']);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->delete();
        $this->assertSame(0, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 29]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 29]);
    }

    public function test_discrepancies_larger_than_yesterdays_branch_receipts_are_not_overwritten(): void
    {
        [, $product] = $this->fixture();
        $this->movement($product, 2, 'in', 'Stock In', '2026-10-20 10:00:01');
        DB::table('products')->where('id', $product->id)->update(['stock' => 7, 'stock_quantity' => 7]);
        DB::table('product_branch_stocks')->where('product_id', $product->id)->update(['quantity' => 0]);
        $this->assertSame(1, $this->runBackfill(['--apply' => true]));
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'quantity' => 0]);
        $this->assertDatabaseCount('inventory_history', 2);
    }

    public function test_receipt_product_ownership_must_match_the_selected_business(): void
    {
        [$company, $first, , $item] = $this->fixture();
        [, $foreign] = $this->fixture();
        DB::table('purchase_items')->where('id', $item)->update(['product_id' => $foreign->id]);
        $this->assertSame(1, $this->runBackfill(['--company' => (string) $company->id, '--apply' => true]));
        foreach ([$first, $foreign] as $product) $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

}
