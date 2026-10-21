<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\SuperAdminRecordMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminRecordMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_keeps_data_unchanged_and_finds_unattributed_linked_customers_and_purchases(): void
    {
        $f = $this->fixtures();
        $before = $this->snapshot();
        $plan = app(SuperAdminRecordMigration::class)->plan($f['admin']->id, $f['source']->id, $f['target']->id);
        $this->assertSame([], $plan['problems']);
        $this->assertSame(1, $plan['roots']['sales']);
        $this->assertSame(1, $plan['counts']['customers']['reassigned']);
        $this->assertSame(1, $plan['counts']['purchases']['reassigned']);
        $this->assertSame(1, $plan['counts']['transactions']['reassigned']);
        $this->assertSame(1, $plan['counts']['sale_items']['linked']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_migration_preserves_ids_money_stock_and_other_accounts_and_is_idempotent(): void
    {
        $f = $this->fixtures();
        $userBefore = $f['staff']->fresh()->getAttributes();
        $agentBefore = $f['agent']->fresh()->getAttributes();
        $original = $this->snapshot();
        $options = $this->migrationOptions($f) + ['--expect-sales' => 1, '--expect-products' => 1, '--apply' => true];
        $this->artisan('workspace:migrate-super-admin-records', $options)->assertSuccessful();
        foreach (['sales', 'products', 'customers', 'purchases', 'accounts', 'transactions', 'product_branch_stocks', 'sale_items', 'purchase_items'] as $table) {
            $row = (array) DB::table($table)->first();
            $old = $original[$table][0];
            $this->assertSame($f['target']->id, (int) $row['company_id']);
            unset($row['company_id'], $old['company_id']);
            $this->assertSame($old, $row, $table.' values/IDs were altered');
        }
        $this->assertSame($userBefore, $f['staff']->fresh()->getAttributes());
        $this->assertSame($agentBefore, $f['agent']->fresh()->getAttributes());
        $this->assertSame($original['companies'], $this->snapshot()['companies']);
        $this->assertDatabaseHas('products', ['sku' => 'STAFF-PRODUCT', 'company_id' => $f['source']->id, 'user_id' => $f['staff']->id]);
        $this->assertDatabaseHas('customers', ['customer_name' => 'Unlinked customer', 'company_id' => $f['source']->id]);
        $this->assertSame($f['target']->id, (int) $f['admin']->fresh()->company_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'records_migrated', 'user_id' => $f['admin']->id]);
        $this->actingAs($f['admin']->fresh());
        session()->forget(['active_branch_scope', 'active_branch_id', 'active_branch_name']);
        app(\App\Support\ActiveBranchResolver::class)->ensureSession($f['admin']->fresh());
        $this->assertSame('all', session('active_branch_scope'));
        $after = $this->snapshot();
        $this->artisan('workspace:migrate-super-admin-records', $options)->assertSuccessful();
        $this->assertSame($after, $this->snapshot());
    }

    public function test_foreign_records_sharing_a_parent_block_the_entire_migration(): void
    {
        $f = $this->fixtures();
        $foreign = Company::create(['user_id' => $f['agent']->id, 'owner_id' => $f['agent']->id, 'name' => 'Other BO']);
        DB::table('sales')->insert([
            'company_id' => $foreign->id, 'user_id' => $f['agent']->id, 'customer_id' => $f['customer'],
            'invoice_no' => 'OTHER', 'receipt_no' => 'OTHER-R', 'order_number' => 'OTHER-O', 'order_date' => now()->toDateString(),
        ]);
        $before = $this->snapshot();
        $this->artisan('workspace:migrate-super-admin-records', $this->migrationOptions($f) + ['--apply' => true, '--expect-sales' => 1, '--expect-products' => 1])
            ->expectsOutputToContain('Migration blocked for review')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_wrong_expected_counts_or_an_unowned_destination_cannot_move_records(): void
    {
        $f = $this->fixtures();
        $before = $this->snapshot();
        $this->artisan('workspace:migrate-super-admin-records', $this->migrationOptions($f) + ['--apply' => true, '--expect-sales' => 26, '--expect-products' => 211])->assertFailed();
        $this->assertSame($before, $this->snapshot());
        $this->artisan('workspace:migrate-super-admin-records', ['--user' => $f['admin']->id, '--from' => $f['target']->id, '--to' => $f['source']->id])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_database_failure_rolls_back_all_already_moved_links(): void
    {
        $f = $this->fixtures();
        DB::statement("CREATE TRIGGER fail_workspace_migration BEFORE UPDATE OF company_id ON transactions BEGIN SELECT RAISE(ABORT, 'Migration test rollback'); END");
        $before = $this->snapshot();
        $this->artisan('workspace:migrate-super-admin-records', $this->migrationOptions($f) + [
            '--apply' => true, '--expect-sales' => 1, '--expect-products' => 1,
        ])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_single_workspace_header_has_no_extra_business_pill_but_keeps_account_menu_action(): void
    {
        $f = $this->fixtures();
        $this->withoutMiddleware([\App\Http\Middleware\ForceLogoutExpiredSession::class, \App\Http\Middleware\EnforceDeviceSessionLimit::class]);
        $this->actingAs($f['admin'])->withSession(['workspace_context' => 'platform'])
            ->get(route('super_admin.dashboard'))->assertOk()
            ->assertDontSee('aria-label="Switch business"', false)
            ->assertSee('Add another business');
    }

    public function test_recovered_enterprise_dashboard_shows_sales_after_a_stale_source_session(): void
    {
        $f = $this->fixtures();
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('MONTH', fn ($date) => $date ? (int) date('n', strtotime($date)) : null);
        $pdo->sqliteCreateFunction('MONTHNAME', fn ($date) => $date ? date('F', strtotime($date)) : null);
        $this->artisan('workspace:migrate-super-admin-records', $this->migrationOptions($f) + [
            '--apply' => true, '--expect-sales' => 1, '--expect-products' => 1,
        ])->assertSuccessful();
        $this->withoutMiddleware([\App\Http\Middleware\ForceLogoutExpiredSession::class, \App\Http\Middleware\EnforceDeviceSessionLimit::class]);
        $response = $this->actingAs($f['admin']->fresh())->withSession([
            'workspace_context' => 'business', 'current_tenant_id' => $f['source']->id,
            'active_branch_id' => 'obsolete-branch', 'active_branch_name' => 'Obsolete',
        ])->get(route('workspace.business.dashboard'));
        $response->assertOk()->assertViewIs('SuperAdmin.dashboards.enterprise');
        $this->assertSame($f['target']->id, $response->viewData('company')->id);
        $this->assertSame(300.0, (float) $response->viewData('metrics')['totalSales']);
        $this->assertSame(1, (int) $response->viewData('metrics')['totalInvoices']);
        $this->assertSame('all', session('active_branch_scope'));
        $response->assertDontSee('aria-label="Switch business"', false);
    }

    private function migrationOptions(array $f): array
    {
        return ['--user' => $f['admin']->id, '--from' => $f['source']->id, '--to' => $f['target']->id];
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $agent = User::factory()->create(['role' => 'agent']);
        $source = Company::create(['user_id' => $agent->id, 'name' => 'Source business']);
        $target = Company::create(['user_id' => $admin->id, 'owner_id' => $admin->id, 'name' => 'Private Enterprise', 'plan' => 'enterprise']);
        $admin->forceFill(['company_id' => $target->id])->save();
        $staff = User::factory()->create(['role' => 'cashier', 'company_id' => $source->id]);
        $product = DB::table('products')->insertGetId(['company_id' => $source->id, 'user_id' => $admin->id,
            'name' => 'Own product', 'sku' => 'MIGRATION-SKU', 'price' => 100, 'purchase_price' => 50, 'stock' => 5, 'stock_quantity' => 5]);
        DB::table('products')->insert(['company_id' => $source->id, 'user_id' => $staff->id, 'name' => 'Other staff product', 'sku' => 'STAFF-PRODUCT', 'price' => 5, 'purchase_price' => 2]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $source->id, 'user_id' => null, 'customer_name' => 'Linked customer']);
        DB::table('customers')->insert(['company_id' => $source->id, 'user_id' => null, 'customer_name' => 'Unlinked customer']);
        $sale = DB::table('sales')->insertGetId(['company_id' => $source->id, 'user_id' => $admin->id, 'customer_id' => $customer,
            'invoice_no' => 'OWN', 'receipt_no' => 'OWN-R', 'order_number' => 'OWN-O', 'order_date' => now()->toDateString(),
            'total' => 300, 'paid' => 200, 'balance' => 100, 'branch_id' => 'original-hq', 'branch_name' => 'Original HQ']);
        DB::table('sale_items')->insert(['sale_id' => $sale, 'product_id' => $product, 'quantity' => 3, 'qty' => 3, 'unit_price' => 100, 'total_price' => 300]);
        $purchase = DB::table('purchases')->insertGetId(['company_id' => $source->id, 'user_id' => null, 'purchase_no' => 'OWN-P',
            'created_at' => now(), 'updated_at' => now(), 'total_amount' => 400, 'branch_id' => 'original-hq', 'branch_name' => 'Original HQ']);
        DB::table('purchase_items')->insert(['purchase_id' => $purchase, 'product_id' => $product, 'qty' => 8, 'unit_price' => 50]);
        $account = DB::table('accounts')->insertGetId(['company_id' => $source->id, 'user_id' => $admin->id, 'name' => 'Cash', 'code' => 'MIG-CASH', 'type' => 'Asset']);
        DB::table('transactions')->insert(['company_id' => $source->id, 'user_id' => null, 'account_id' => $account,
            'transaction_date' => now()->toDateString(), 'related_id' => $sale, 'related_type' => \App\Models\Sale::class,
            'debit' => 300, 'credit' => 0, 'branch_id' => 'original-hq', 'branch_name' => 'Original HQ']);
        DB::table('product_branch_stocks')->insert(['company_id' => $source->id, 'product_id' => $product,
            'branch_id' => 'original-hq', 'branch_name' => 'Original HQ', 'quantity' => 5]);
        return compact('admin', 'agent', 'staff', 'source', 'target', 'customer');
    }

    private function snapshot(): array
    {
        $tables = ['users', 'companies', 'sales', 'sale_items', 'products', 'purchases', 'purchase_items', 'customers', 'accounts', 'transactions', 'product_branch_stocks', 'settings', 'activity_logs'];
        return collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all()])->all();
    }
}
