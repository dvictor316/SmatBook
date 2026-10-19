<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsAndOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            CheckPlanAccess::class, EnforceDeviceSessionLimit::class, ForceLogoutExpiredSession::class,
            RequireActiveBranch::class, SubscriptionActive::class, VerifyTenantSession::class,
        ]);
    }

    private function workspace(string $role = 'cashier', ?array $permissions = []): array
    {
        $company = Company::create(['name' => 'Permission Test', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => $role, 'permissions_override' => $permissions]);
        $this->actingAs($user)->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all']);

        return [$user, $company];
    }

    public function test_dashboard_and_stock_routes_enforce_revoked_permissions_even_for_admin_overrides(): void
    {
        foreach (['cashier', 'admin'] as $role) {
            $this->workspace($role);
            $this->get(route('user.dashboard'))->assertForbidden();
            $this->get(route('workspace.business.dashboard'))->assertForbidden();
            $this->post(route('inventory.adjust'), ['type' => 'in', 'quantity' => 10])->assertForbidden();
            $this->post(route('inventory.Products.store'), ['stock' => 10])->assertForbidden();
            $this->post(route('inventory.Products.import'))->assertForbidden();
        }
    }

    public function test_product_creation_does_not_grant_opening_stock_access(): void
    {
        $this->workspace('cashier', ['inventory.products.create']);
        $this->post(route('inventory.Products.store'), ['stock_cartons' => 2])->assertForbidden();
        $this->post(route('inventory.Products.import'))->assertForbidden();
    }

    public function test_role_permission_revocation_is_effective_on_the_next_request(): void
    {
        [$user] = $this->workspace('cashier', null);
        $role = Role::create(['name' => 'Restricted Staff']);
        $permission = Permission::firstOrCreate(['name' => 'inventory.products.create']);
        $role->permissions()->sync([$permission->id]);
        $user->update(['role_id' => $role->id]);
        $this->assertTrue($user->hasPermissionTo('inventory.products.create'));
        $role->permissions()->sync([]);
        $this->post(route('inventory.Products.store'))->assertForbidden();
    }

    public function test_both_payment_delete_routes_restore_the_original_customer_opening_debt(): void
    {
        [$user, $company] = $this->workspace('admin', null);
        foreach (['payments.report-destroy', 'payments.destroy'] as $route) {
            $customer = Customer::create(['company_id' => $company->id, 'user_id' => $user->id, 'customer_name' => 'Opening Customer', 'balance' => 3000000]);
            $sale = Sale::create([
                'company_id' => $company->id, 'user_id' => $user->id, 'customer_id' => $customer->id,
                'invoice_no' => 'OPENING-BAL-'.$customer->id, 'subtotal' => 5000000, 'total' => 5000000,
                'paid' => 2000000, 'amount_paid' => 2000000, 'balance' => 3000000, 'payment_status' => 'partial',
            ]);
            $payment = Payment::create(['company_id' => $company->id, 'user_id' => $user->id, 'created_by' => $user->id,
                'customer_id' => $customer->id, 'sale_id' => $sale->id, 'amount' => 2000000, 'method' => 'Bank Transfer', 'status' => 'Completed']);
            $response = $this->delete(route($route, $payment->id));
            $this->assertContains($response->status(), [200, 302]);
            $this->assertSame(5000000.0, (float) $customer->fresh()->balance);
            $this->assertSame(5000000.0, (float) $sale->fresh()->balance);
            $this->assertSame(0.0, (float) $sale->fresh()->amount_paid);
            $this->get(route('reports.accounts-receivable'))->assertOk()
                ->assertViewHas('receivables', fn ($rows) => (float) $rows->firstWhere('customer_id', $customer->id)->opening_balance === 5000000.0);
        }
    }

    public function test_report_recalculates_a_previously_stale_opening_balance(): void
    {
        [$user, $company] = $this->workspace('admin', null);
        $customer = Customer::create(['company_id' => $company->id, 'user_id' => $user->id, 'customer_name' => 'Stale Customer', 'balance' => 3000000]);
        Sale::create(['company_id' => $company->id, 'user_id' => $user->id, 'customer_id' => $customer->id,
            'invoice_no' => 'OPENING-BAL-'.$customer->id, 'total' => 5000000, 'subtotal' => 5000000,
            'paid' => 2000000, 'amount_paid' => 2000000, 'balance' => 3000000]);
        $this->get(route('reports.accounts-receivable'))->assertOk()->assertViewHas('totalDue', 5000000.0);
    }

    public function test_user_edit_can_revoke_every_permission(): void
    {
        [$admin, $company] = $this->workspace('admin', null);
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'cashier',
            'permissions_override' => ['dashboard.overview.view', 'inventory.products.add_opening_stock']]);
        $this->put(route('users.update', $staff->id), [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'cashier', 'permissions_submitted' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([], $staff->fresh()->permissions_override);
        $this->actingAs($staff->fresh())->get(route('user.dashboard'))->assertForbidden();
    }

    public function test_receipt_shows_only_mode_while_report_shows_the_bank(): void
    {
        [$user, $company] = $this->workspace('admin', null);
        $account = Account::create(['company_id' => $company->id, 'name' => 'Selected Bank Plc', 'code' => 'BANK-TEST', 'type' => 'Asset', 'is_active' => true]);
        $sale = Sale::create(['company_id' => $company->id, 'user_id' => $user->id, 'invoice_no' => 'TEST-BANK',
            'subtotal' => 200, 'total' => 200, 'paid' => 200, 'amount_paid' => 200, 'balance' => 0,
            'payment_method' => 'card', 'payment_details' => ['card_account_name' => $account->name]]);
        $this->assertSame('Selected Bank Plc', $sale->payment_report_label);
        $payment = Payment::create(['company_id' => $company->id, 'user_id' => $user->id, 'created_by' => $user->id,
            'sale_id' => $sale->id, 'account_id' => $account->id, 'amount' => 200, 'method' => 'POS/Card', 'status' => 'Completed']);
        $this->get(route('payments.receipt', $payment->id))->assertOk()->assertSee('POS/Card')->assertDontSee('Selected Bank Plc');
        $this->get(route('reports.payment'))->assertOk()->assertSee('Selected Bank Plc');
    }
}
