<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Support\BusinessWorkspaceResolver;
use App\Support\SuperAdminBusinessWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminBusinessWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The production dashboard uses MySQL month functions; mirror them in SQLite.
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('MONTH', fn ($date) => $date ? (int) date('n', strtotime($date)) : null);
        $pdo->sqliteCreateFunction('MONTHNAME', fn ($date) => $date ? date('F', strtotime($date)) : null);
        $this->withoutMiddleware([ForceLogoutExpiredSession::class, EnforceDeviceSessionLimit::class]);
    }

    public function test_business_dashboard_provisions_private_enterprise_company_and_branch(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'company_id' => null]);
        $this->actingAs($admin)->withSession(['workspace_context' => 'platform'])
            ->get(route('workspace.business.dashboard'))
            ->assertOk()->assertDontSee('Please complete setup and select a branch');

        $companyId = (int) session('current_tenant_id');
        $this->assertGreaterThan(0, $companyId);
        $this->assertDatabaseHas('companies', ['id' => $companyId, 'user_id' => $admin->id, 'owner_id' => $admin->id, 'plan' => 'enterprise']);
        $this->assertSame('enterprise', session('user_plan'));
        $this->assertNotEmpty(session('active_branch_id'));
        $this->assertNotEmpty(Setting::get('branches_json_company_'.$companyId));
        $this->assertSame($companyId, (int) $admin->fresh()->company_id);
        $this->assertDatabaseCount('subscriptions', 0);

        $this->get(route('workspace.platform'))->assertRedirect(route('super_admin.dashboard'));
        $this->assertNull(session('current_tenant_id'));
        $this->get(route('workspace.business.dashboard'))->assertOk();
        $this->assertSame($companyId, (int) session('current_tenant_id'));
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_stale_client_assignment_does_not_upgrade_or_reuse_the_clients_workspace(): void
    {
        $client = User::factory()->create(['role' => 'admin']);
        $foreign = Company::create(['user_id' => $client->id, 'owner_id' => $client->id, 'name' => 'Client', 'plan' => 'basic']);
        $admin = User::factory()->create(['role' => 'super_admin', 'company_id' => $foreign->id]);
        $this->actingAs($admin);
        session(['workspace_context' => 'business', 'current_tenant_id' => $foreign->id]);

        $own = app(SuperAdminBusinessWorkspace::class)->activate($admin);

        $this->assertNotSame($foreign->id, $own->id);
        $this->assertSame('basic', $foreign->fresh()->plan);
        $this->assertSame($client->id, (int) $foreign->fresh()->user_id);
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($client, $own->id));
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($admin, $foreign->id));
    }

    public function test_existing_owned_business_and_branch_are_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $company = Company::create(['user_id' => $admin->id, 'owner_id' => $admin->id, 'name' => 'Own Existing Business']);
        $admin->forceFill(['company_id' => $company->id])->save();
        Setting::create(['key' => 'branches_json_company_'.$company->id, 'value' => json_encode([
            ['id' => 'own-branch', 'name' => 'Own Branch', 'is_active' => true],
        ])]);
        $this->actingAs($admin);
        session(['workspace_context' => 'platform']);

        $own = app(SuperAdminBusinessWorkspace::class)->activate($admin);
        $this->assertSame($company->id, $own->id);
        $this->assertSame('Own Existing Business', $own->name);
        $this->assertSame('own-branch', session('active_branch_id'));
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_each_super_admin_gets_a_different_private_business(): void
    {
        $first = User::factory()->create(['role' => 'super_admin']);
        $second = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($first);
        $firstCompany = app(SuperAdminBusinessWorkspace::class)->activate($first);
        $this->actingAs($second);
        $secondCompany = app(SuperAdminBusinessWorkspace::class)->activate($second);
        $this->assertNotSame($firstCompany->id, $secondCompany->id);
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($second, $firstCompany->id));
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($first, $secondCompany->id));
    }

    public function test_legacy_company_shared_with_another_account_is_not_claimed(): void
    {
        $shared = Company::create(['name' => 'Shared Legacy Company', 'plan' => 'basic']);
        $admin = User::factory()->create(['role' => 'super_admin', 'company_id' => $shared->id]);
        User::factory()->create(['role' => 'admin', 'company_id' => $shared->id]);
        $this->actingAs($admin);
        $own = app(SuperAdminBusinessWorkspace::class)->activate($admin);
        $this->assertNotSame($shared->id, $own->id);
        $this->assertSame('basic', $shared->fresh()->plan);
        $this->assertNull($shared->fresh()->user_id);
        $this->assertSame($own->id, (int) $admin->fresh()->company_id);
        session()->forget('current_tenant_id');
        $this->assertSame($own->id, app(SuperAdminBusinessWorkspace::class)->activate($admin)->id);
        $this->assertDatabaseCount('companies', 2);
    }

    public function test_conflicting_owner_fields_do_not_upgrade_the_clients_business(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'admin']);
        $foreign = Company::create([
            'user_id' => $client->id, 'owner_id' => $admin->id, 'name' => 'Conflicting Legacy Ownership', 'plan' => 'basic',
        ]);
        $admin->forceFill(['company_id' => $foreign->id])->save();
        $this->actingAs($admin);
        $own = app(SuperAdminBusinessWorkspace::class)->activate($admin);
        $this->assertNotSame($foreign->id, $own->id);
        $this->assertSame('basic', $foreign->fresh()->plan);
        $this->assertSame($client->id, (int) $foreign->fresh()->user_id);
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($admin, $foreign->id));
    }

    public function test_regular_business_owner_cannot_claim_complimentary_super_admin_enterprise_workspace(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->actingAs($owner);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(SuperAdminBusinessWorkspace::class)->activate($owner);
    }
}
