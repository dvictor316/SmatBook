<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\Setting;
use App\Support\BusinessWorkspaceResolver;
use App\Support\SuperAdminBusinessWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminWorkspaceRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_locates_existing_sales_and_never_changes_accounts_or_records(): void
    {
        [$admin, $old, $empty, $owner, $client] = $this->fixtures();
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', ['--user' => $admin->id])
            ->expectsOutputToContain('Original Enterprise')
            ->expectsOutputToContain('Current Empty Workspace')
            ->expectsOutputToContain('Sales branches for company #'.$old->id)
            ->expectsOutputToContain('READ ONLY')
            ->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_verified_relink_restores_original_workspace_without_moving_sales_or_affecting_business_owners(): void
    {
        [$admin, $old, $empty, $owner, $client] = $this->fixtures();
        $salesBefore = DB::table('sales')->orderBy('id')->get()->toJson();
        $companiesBefore = DB::table('companies')->orderBy('id')->get()->toJson();
        $ownerBefore = $owner->fresh()->getAttributes();
        $this->artisan('workspace:recover-super-admin', ['--user' => $admin->id, '--company' => $old->id, '--apply' => true])->assertSuccessful();
        $this->assertSame($old->id, (int) $admin->fresh()->company_id);
        $this->assertSame($salesBefore, DB::table('sales')->orderBy('id')->get()->toJson());
        $this->assertSame($companiesBefore, DB::table('companies')->orderBy('id')->get()->toJson());
        $this->assertSame($ownerBefore, $owner->fresh()->getAttributes());
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'primary_restored']);

        // A fresh login must retain the recovered company rather than return to the empty one.
        $admin = $admin->fresh();
        $this->actingAs($admin);
        session()->forget(['current_tenant_id', 'active_branch_id', 'active_branch_name']);
        $this->assertSame($old->id, app(SuperAdminBusinessWorkspace::class)->activate($admin)->id);
        $this->assertSame(1, DB::table('sales')->where('company_id', $old->id)->count());
        $this->assertSame(1, DB::table('sales')->where('company_id', $client->id)->count());
    }

    public function test_creator_id_does_not_allow_recovery_of_a_clients_company(): void
    {
        [$admin, $old, $empty, $owner, $client] = $this->fixtures();
        // Super admins can have created transactions for a client; that is not company ownership.
        $this->sale($admin, $client, 'ADMIN-CREATED-CLIENT-SALE');
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', ['--user' => $admin->id, '--company' => $client->id, '--apply' => true])
            ->expectsOutputToContain('Ownership is not verified')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_recovery_requires_one_explicit_super_admin_and_company(): void
    {
        $this->fixtures();
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', ['--apply' => true])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_regular_owner_is_not_a_recovery_target(): void
    {
        [$admin, $old, $empty, $owner, $client] = $this->fixtures();
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', ['--user' => $owner->id, '--company' => $old->id, '--apply' => true])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_empty_duplicate_is_retired_while_original_sales_and_other_businesses_remain_unchanged(): void
    {
        [$admin, $old, $empty, $owner, $client] = $this->fixtures();
        $empty->update(['name' => $admin->name.' Enterprise Business']);
        $sales = DB::table('sales')->orderBy('id')->get()->toJson();
        $clientBefore = $client->fresh()->getAttributes();
        $ownerBefore = $owner->fresh()->getAttributes();
        $options = ['--user' => $admin->id, '--company' => $old->id, '--retire-empty' => $empty->id];
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', $options)->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
        $this->artisan('workspace:recover-super-admin', [...$options, '--apply' => true])->assertSuccessful();

        $this->assertSame($old->id, (int) $admin->fresh()->company_id);
        $this->assertSame('inactive', $empty->fresh()->status);
        $this->assertSame($sales, DB::table('sales')->orderBy('id')->get()->toJson());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame($ownerBefore, $owner->fresh()->getAttributes());
        $this->assertDatabaseCount('companies', 3);
        $admin = $admin->fresh();
        $this->actingAs($admin);
        session(['current_tenant_id' => $empty->id]);
        $resolver = app(BusinessWorkspaceResolver::class);
        $this->assertFalse($resolver->canAccess($admin, $empty->id));
        $this->assertSame($old->id, $resolver->activeCompanyId($admin));
        $this->assertSame($old->id, app(SuperAdminBusinessWorkspace::class)->activate($admin)->id);
        $this->assertDatabaseCount('companies', 3);
    }

    public function test_duplicate_with_sales_is_not_retired_and_primary_link_is_unchanged(): void
    {
        [$admin, $old, $empty] = $this->fixtures();
        $empty->update(['name' => $admin->name.' Enterprise Business']);
        $this->sale($admin, $empty, 'NEW-WORKSPACE-SALE');
        // Even deleted sales block retiring the business.
        DB::table('sales')->where('invoice_no', 'NEW-WORKSPACE-SALE')->update(['deleted_at' => now()]);
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', [
            '--user' => $admin->id, '--company' => $old->id, '--retire-empty' => $empty->id, '--apply' => true,
        ])->expectsOutputToContain('sales contains records')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_duplicate_with_saved_settings_is_not_retired(): void
    {
        [$admin, $old, $empty] = $this->fixtures();
        $empty->update(['name' => $admin->name.' Enterprise Business']);
        Setting::create(['key' => 'company_name_company_'.$empty->id, 'value' => 'Saved Business Profile']);
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', [
            '--user' => $admin->id, '--company' => $old->id, '--retire-empty' => $empty->id, '--apply' => true,
        ])->expectsOutputToContain('saved business settings')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_duplicate_used_by_another_account_cannot_be_retired(): void
    {
        [$admin, $old, $empty, $owner] = $this->fixtures();
        $empty->update(['name' => $admin->name.' Enterprise Business']);
        $owner->forceFill(['company_id' => $empty->id])->save();
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', [
            '--user' => $admin->id, '--company' => $old->id, '--retire-empty' => $empty->id, '--apply' => true,
        ])->expectsOutputToContain('users contains records')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_original_business_cannot_be_retired_by_using_the_same_id(): void
    {
        [$admin, $old] = $this->fixtures();
        $before = $this->snapshot();
        $this->artisan('workspace:recover-super-admin', [
            '--user' => $admin->id, '--company' => $old->id, '--retire-empty' => $old->id, '--apply' => true,
        ])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $old = Company::create(['user_id' => $admin->id, 'owner_id' => $admin->id, 'name' => 'Original Enterprise', 'plan' => 'enterprise']);
        $empty = Company::create(['user_id' => $admin->id, 'owner_id' => $admin->id, 'name' => 'Current Empty Workspace', 'plan' => 'enterprise']);
        $client = Company::create(['user_id' => $owner->id, 'owner_id' => $owner->id, 'name' => 'Client Business', 'plan' => 'basic']);
        $admin->forceFill(['company_id' => $empty->id])->save();
        $owner->forceFill(['company_id' => $client->id])->save();
        $this->sale($admin, $old, 'OLD-ENTERPRISE-SALE');
        $this->sale($owner, $client, 'CLIENT-SALE');
        return [$admin, $old, $empty, $owner, $client];
    }

    private function sale(User $user, Company $company, string $invoice): void
    {
        DB::table('sales')->insert([
            'user_id' => $user->id, 'company_id' => $company->id,
            'invoice_no' => $invoice, 'receipt_no' => $invoice.'-R', 'order_number' => $invoice.'-O', 'order_date' => now()->toDateString(), 'total' => 25000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function snapshot(): array
    {
        return collect(['users', 'companies', 'sales', 'purchases', 'products', 'customers', 'subscriptions', 'settings', 'activity_logs'])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    }
}
