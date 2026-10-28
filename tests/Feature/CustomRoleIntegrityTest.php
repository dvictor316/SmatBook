<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomRoleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'Role Test Business', 'plan' => 'Enterprise']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $company->update(['user_id' => $admin->id]);
        $this->actingAs($admin)->withoutMiddleware()->withSession(['current_tenant_id' => $company->id]);
        view()->share('errors', new ViewErrorBag);

        return [$company, $admin];
    }

    public function test_custom_role_stays_selected_and_survives_editing_staff_details(): void
    {
        [$company] = $this->workspace();
        Role::create(['name' => 'Account Officer']);
        $custom = Role::create(['name' => 'Front Counter Supervisor']);
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'front_counter_supervisor', 'role_id' => $custom->id]);
        $response = $this->get(route('users.edit', $staff->id));
        $response->assertOk()->assertSee('<option value="Front Counter Supervisor" selected', false);
        $this->put(route('users.update', $staff->id), [
            'name' => 'Updated Staff Name', 'email' => $staff->email, 'role' => 'Front Counter Supervisor',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $staff->refresh();
        $this->assertSame($custom->id, $staff->role_id);
        $this->assertSame('front_counter_supervisor', $staff->role);
        $this->assertSame('Front Counter Supervisor', $staff->role_display_name);
        $this->get(route('users.index'))->assertOk()->assertSee('Front Counter Supervisor');
    }

    public function test_creating_staff_with_a_custom_role_keeps_the_exact_role_assignment(): void
    {
        [$company] = $this->workspace();
        $custom = Role::create(['name' => 'Warehouse Assistant']);
        $this->post(route('users.store'), [
            'first_name' => 'New Staff', 'email' => 'custom.staff@example.test', 'role' => $custom->name,
            'password' => 'test-password', 'confirm_password' => 'test-password',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'custom.staff@example.test', 'role_id' => $custom->id,
            'role' => 'warehouse_assistant', 'company_id' => $company->id]);
    }

    public function test_assigned_role_is_authoritative_even_when_legacy_role_string_or_loaded_relation_disagrees(): void
    {
        [$company] = $this->workspace();
        $custom = Role::create(['name' => 'Dispatch Clerk']);
        $permission = Permission::create(['name' => 'inventory.products.view']);
        $custom->permissions()->attach($permission);
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'role_id' => $custom->id]);
        $staff->load('role');
        $this->assertTrue($staff->hasRole('dispatch_clerk'));
        $this->assertFalse($staff->hasRole('administrator'));
        $this->assertTrue($staff->hasPermissionTo('inventory.products.view'));
        $this->assertSame('Dispatch Clerk', $staff->role_display_name);
        $this->actingAs($staff);
        try {
            app(CheckRole::class)->handle(Request::create('/protected'), fn () => response('allowed'), 'administrator');
            $this->fail('The stale legacy admin string must not grant admin access.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $response = app(RoleMiddleware::class)->handle(Request::create('/protected'), fn () => response('allowed'), 'administrator');
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_role_aliases_and_legacy_permission_inheritance_work_consistently(): void
    {
        [$company] = $this->workspace();
        $role = Role::create(['name' => 'Account Officer']);
        $permission = Permission::create(['name' => 'finance.payments.view']);
        $role->permissions()->attach($permission);
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'accountant', 'role_id' => null]);
        $this->assertTrue($staff->hasRole('Account Officer'));
        $this->assertTrue($staff->hasPermissionTo('finance.payments.view'));
        $this->assertSame('Account Officer', $staff->role_display_name);
        $staff->update(['role_id' => $role->id]);
        $this->actingAs($staff);
        foreach ([CheckRole::class, RoleMiddleware::class] as $middleware) {
            $response = app($middleware)->handle(Request::create('/protected'), fn () => response('allowed'), 'account_officer');
            $this->assertSame('allowed', $response->getContent());
        }
    }

    public function test_empty_saved_role_permissions_are_not_replaced_with_default_grants(): void
    {
        $this->workspace();
        Role::create(['name' => 'Account Officer']);
        $this->getJson(route('roles.permissions.json', ['role' => 'accountant']))->assertOk()->assertExactJson(['permissions' => []]);
    }

    public function test_renaming_a_custom_role_preserves_assigned_users_and_permissions(): void
    {
        [$company] = $this->workspace();
        $role = Role::create(['name' => 'Dispatch Clerk']);
        $permission = Permission::create(['name' => 'customers.customers.view']);
        $role->permissions()->attach($permission);
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'dispatch_clerk', 'role_id' => $role->id]);
        $legacy = User::factory()->create(['company_id' => $company->id, 'role' => 'dispatch_clerk', 'role_id' => null]);
        $this->put(route('roles.update', $role->id), ['name' => 'Dispatch Supervisor'])->assertSessionHasNoErrors();
        foreach ([$staff->fresh(), $legacy->fresh()] as $assigned) {
            $this->assertSame($role->id, $assigned->role_id);
            $this->assertSame('dispatch_supervisor', $assigned->role);
            $this->assertTrue($assigned->hasPermissionTo('customers.customers.view'));
        }
    }

    public function test_assigned_roles_cannot_be_deleted_or_duplicated_under_another_alias(): void
    {
        [$company] = $this->workspace();
        $role = Role::create(['name' => 'Front Desk']);
        User::factory()->create(['company_id' => $company->id, 'role_id' => $role->id, 'role' => 'front_desk']);
        $this->delete(route('roles.destroy', $role->id))->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $roleCount = Role::count();
        $this->post(route('roles.store'), ['name' => 'front-desk'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('roles', $roleCount);
    }

    public function test_core_role_rename_is_rejected_without_changing_assignments(): void
    {
        [$company] = $this->workspace();
        $role = Role::create(['name' => 'Account Officer', 'is_system_role' => true]);
        $staff = User::factory()->create(['company_id' => $company->id, 'role_id' => $role->id, 'role' => 'accountant']);
        $this->put(route('roles.update', $role->id), ['name' => 'Different Identity'])->assertSessionHasErrors('name');
        $this->assertSame('Account Officer', $role->fresh()->name);
        $this->assertSame($role->id, $staff->fresh()->role_id);
        $this->assertTrue($staff->fresh()->hasRole('account-officer'));
    }

    public function test_changing_role_id_does_not_reuse_a_previously_loaded_role(): void
    {
        [$company] = $this->workspace();
        $first = Role::create(['name' => 'Receiving Clerk']);
        $second = Role::create(['name' => 'Dispatch Clerk']);
        $staff = User::factory()->create(['company_id' => $company->id, 'role_id' => $first->id, 'role' => 'receiving_clerk']);
        $staff->load('roleModel');
        $staff->role_id = $second->id;
        $this->assertFalse($staff->hasRole('Receiving Clerk'));
        $this->assertTrue($staff->hasRole('Dispatch Clerk'));
    }

    public function test_invalid_old_role_input_never_defaults_to_account_officer(): void
    {
        [$company] = $this->workspace();
        $this->withMiddleware()->withoutMiddleware([
            \App\Http\Middleware\CheckPlanAccess::class,
            \App\Http\Middleware\SubscriptionActive::class,
            \App\Http\Middleware\RequireActiveBranch::class,
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
            \App\Http\Middleware\VerifyTenantSession::class,
        ]);
        Role::create(['name' => 'Account Officer']);
        $role = Role::create(['name' => 'Dispatch Clerk']);
        $staff = User::factory()->create(['company_id' => $company->id, 'role_id' => $role->id, 'role' => 'dispatch_clerk']);
        $response = $this->withSession(['_old_input' => ['role' => 'Deleted Role']])->get(route('users.edit', $staff->id));
        $response->assertOk();
        preg_match('/<select[^>]*id="roleSelect".*?<\/select>/s', $response->getContent(), $matches);
        $this->assertStringContainsString('<option value="" disabled selected', $matches[0] ?? 'Role selector missing');
        $this->assertStringNotContainsString('<option value="Account Officer" selected', $matches[0]);
    }

    public function test_tenant_admin_cannot_demote_an_existing_super_admin(): void
    {
        [$company] = $this->workspace();
        $superAdmin = User::factory()->create(['company_id' => $company->id, 'role' => 'super_admin']);
        $this->putJson(route('users.update', $superAdmin->id), ['name' => $superAdmin->name,
            'email' => $superAdmin->email, 'role' => 'cashier'])->assertForbidden();
        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_unknown_roles_and_tenant_super_admin_assignment_are_rejected(): void
    {
        [$company] = $this->workspace();
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'cashier']);
        $this->put(route('users.update', $staff->id), ['name' => $staff->name, 'email' => $staff->email, 'role' => 'Missing Custom Role'])
            ->assertSessionHasErrors('role');
        $this->assertSame('cashier', $staff->fresh()->role);
        $this->putJson(route('users.update', $staff->id), ['name' => $staff->name, 'email' => $staff->email, 'role' => 'super_admin'])->assertForbidden();
        $this->postJson(route('roles.store'), ['name' => 'Super Admin'])->assertForbidden();
    }

    public function test_administrator_role_is_available_to_the_workspace_owner_only(): void
    {
        [$company, $owner] = $this->workspace();
        $administrator = Role::firstOrCreate(['name' => 'Administrator']);
        $permission = Permission::create(['name' => 'roles.roles.delete']);
        $administrator->permissions()->attach($permission);

        $this->assertTrue($owner->fresh()->hasRole('administrator'));
        $owner->forceFill(['role_id' => $administrator->id, 'role' => 'administrator'])->save();
        $this->assertTrue($owner->fresh()->hasRole('administrator'));
        $this->assertTrue($owner->fresh()->hasPermissionTo('roles.roles.delete'));

        $staff = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'administrator',
            'role_id' => $administrator->id,
            'permissions_override' => ['roles.roles.delete'],
        ]);
        $this->assertFalse($staff->hasRole('administrator'));
        $this->assertFalse($staff->hasPermissionTo('roles.roles.delete'));

        $this->actingAs($staff);
        $response = app(RoleMiddleware::class)->handle(
            Request::create('/protected'),
            fn () => response('allowed'),
            'administrator'
        );
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_staff_cannot_be_created_or_changed_to_administrator(): void
    {
        [$company] = $this->workspace();
        $staff = User::factory()->create(['company_id' => $company->id, 'role' => 'cashier']);

        $this->put(route('users.update', $staff->id), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'Administrator',
        ])->assertSessionHasErrors('role');
        $this->assertSame('cashier', $staff->fresh()->role);

        $this->post(route('users.store'), [
            'first_name' => 'Blocked',
            'email' => 'blocked.admin@example.test',
            'role' => 'Administrator',
            'password' => 'test-password',
            'confirm_password' => 'test-password',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'blocked.admin@example.test']);
    }
}
