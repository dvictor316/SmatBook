<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccessDeniedRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckPlanAccess::class,
            \App\Http\Middleware\SubscriptionActive::class,
            \App\Http\Middleware\RequireActiveBranch::class,
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
            \App\Http\Middleware\VerifyTenantSession::class,
        ]);
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/test-access/allowed', fn () => response('<p>Allowed</p>'))->middleware('permission:inventory.products.view');
            Route::get('/test-access/denied', fn () => response('private'))->middleware('permission:reports.reports.view');
            Route::post('/test-access/denied', fn () => response('changed'))->middleware('permission:reports.reports.edit');
            Route::get('/test-access/policy', fn () => abort(403));
        });
    }

    private function staff(array $permissions = []): User
    {
        $company = Company::create(['name' => 'Access Test', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'cashier', 'permissions_override' => $permissions]);
        $this->actingAs($user)->withSession(['current_tenant_id' => $company->id]);
        return $user;
    }

    public function test_denied_pages_and_actions_return_to_the_accessible_origin(): void
    {
        $this->staff(['inventory.products.view']);
        $origin = url('/test-access/allowed');
        $this->get($origin)->assertOk();
        $this->get('/test-access/denied')->assertRedirect($origin)->assertSessionHas('error');
        $this->get($origin)->assertOk();
        $this->from($origin)->post('/test-access/denied')->assertRedirect($origin);
        $this->get($origin)->assertOk();
        $this->from($origin)->get('/test-access/policy')->assertRedirect($origin);
    }

    public function test_json_denials_keep_the_forbidden_status_and_do_not_execute_actions(): void
    {
        $this->staff();
        $this->getJson('/test-access/denied')->assertForbidden();
        $this->postJson('/test-access/denied')->assertForbidden();
    }

    public function test_external_self_and_revoked_origins_are_not_used(): void
    {
        $user = $this->staff(['inventory.products.view']);
        $this->get('/test-access/allowed')->assertOk();
        $user->update(['permissions_override' => []]);
        $this->from('https://example.com/phishing')->get('/test-access/denied')->assertRedirect(route('profile'));
        $this->from(url('/test-access/denied'))->get('/test-access/denied')->assertRedirect(route('access.recovery'));
        $this->get(route('access.recovery'))->assertOk()->assertSee('Sign out');
    }

    public function test_roles_are_owner_only_even_for_administrators_with_role_permissions(): void
    {
        $staff = $this->staff(['roles.roles.view', 'roles.roles.create', 'roles.roles.edit', 'roles.roles.delete']);
        $staff->update(['role' => 'admin']);
        $this->getJson(route('roles.index'))->assertForbidden();
        $this->postJson(route('roles.store'), ['name' => 'Forbidden role'])->assertForbidden();
        $this->putJson(route('roles.update', 999), ['name' => 'Forbidden role'])->assertForbidden();
        $this->postJson(route('roles.permissions.update'), ['role_id' => 999])->assertForbidden();
        $this->deleteJson(route('roles.destroy', 999))->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'Forbidden role']);
        Company::whereKey($staff->company_id)->update(['user_id' => $staff->id]);
        $this->assertTrue($staff->canManageRoles());
        $this->postJson(route('roles.store'), ['name' => 'Owner role'])->assertCreated();
    }

    public function test_ownership_is_limited_to_the_active_business_and_super_admin_is_supported(): void
    {
        $user = $this->staff();
        Company::whereKey($user->company_id)->update(['owner_id' => $user->id]);
        $this->assertTrue($user->canManageRoles());
        $other = Company::create(['name' => 'Other Business', 'plan' => 'Enterprise']);
        session(['current_tenant_id' => $other->id]);
        $this->assertFalse($user->canManageRoles());
        $user->role = 'super_admin';
        $this->assertTrue($user->canManageRoles());
    }
}
