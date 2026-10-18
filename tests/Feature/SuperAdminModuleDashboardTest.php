<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use App\Support\HotelAccess;
use App\Support\LivestockAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminModuleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_lists_general_and_specialist_modules(): void
    {
        $company = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'super_admin',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform Module Catalogue')
            ->assertSee('Projects &amp; Profitability', false)
            ->assertSee('Tax &amp; Compliance', false)
            ->assertSee('Hotel Management')
            ->assertSee('Livestock &amp; Layer Farm', false)
            ->assertSee('Hotel tenants only')
            ->assertSee('Livestock tenants only')
            ->assertSee(route('super_admin.livestock.index'), false);
    }

    public function test_super_admin_opens_own_livestock_workspace_without_tenant_selector(): void
    {
        $adminCompany = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $adminCompany->id, 'role' => 'super_admin']);
        $externalCompany = Company::create(['name' => 'External Livestock Tenant', 'industry' => 'Livestock']);

        \DB::table('livestock_farms')->insert([
            'company_id' => $adminCompany->id,
            'name' => 'Main Layer Farm',
            'code' => 'SL-001',
            'farm_type' => 'layers',
            'bird_capacity' => 2000,
            'eggs_per_crate' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('livestock_farms')->insert([
            'company_id' => $externalCompany->id, 'name' => 'External Farm', 'code' => 'EXT-001',
            'farm_type' => 'layers', 'bird_capacity' => 1000, 'eggs_per_crate' => 30,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.livestock.index'))
            ->assertOk()
            ->assertSee('Livestock Management')
            ->assertSee('Platform Administration')
            ->assertSee('Main Layer Farm')
            ->assertDontSee('All livestock tenants')
            ->assertDontSee('Livestock tenants')
            ->assertDontSee('External Livestock Tenant')
            ->assertDontSee('External Farm');

        $this->actingAs($superAdmin)->post(route('super_admin.livestock.farms.store'), [
            'company_id' => $externalCompany->id, 'name' => 'Unauthorized Farm', 'code' => 'NOPE-01',
            'bird_capacity' => 100, 'eggs_per_crate' => 30,
        ])->assertForbidden();
        $this->assertDatabaseMissing('livestock_farms', ['company_id' => $externalCompany->id, 'code' => 'NOPE-01']);
    }

    public function test_demo_user_is_entitled_to_both_specialist_workspaces(): void
    {
        $company = Company::create(['name' => 'Guided Demo', 'is_demo' => true, 'status' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->assertTrue(HotelAccess::userIsHotelTenant($user));
        $this->assertTrue(LivestockAccess::userIsLivestockTenant($user));
    }

    public function test_super_admin_can_manage_own_livestock_workspace_end_to_end(): void
    {
        $adminCompany = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $adminCompany->id, 'role' => 'super_admin']);

        $this->actingAs($superAdmin)->post(route('super_admin.livestock.farms.store'), [
            'company_id' => $adminCompany->id, 'name' => 'Prime Farm', 'code' => 'PRIME-01',
            'bird_capacity' => 3000, 'eggs_per_crate' => 30, 'location' => 'Ogun',
        ])->assertRedirect(route('super_admin.livestock.index'));

        $farmId = \DB::table('livestock_farms')->where('company_id', $adminCompany->id)->value('id');
        $this->actingAs($superAdmin)->post(route('super_admin.livestock.flocks.store'), [
            'farm_id' => $farmId, 'batch_code' => 'PRIME-BATCH-01', 'breed' => 'Isa Brown',
            'placement_date' => now()->subMonth()->toDateString(), 'age_at_placement_weeks' => 16,
            'opening_birds' => 500, 'cost_per_bird' => 5000, 'supplier' => 'Test Supplier',
        ])->assertSessionHasNoErrors();

        $flockId = \DB::table('livestock_flocks')->where('farm_id', $farmId)->value('id');
        $this->actingAs($superAdmin)->post(route('super_admin.livestock.opex.store'), [
            'farm_id' => $farmId, 'flock_id' => $flockId, 'expense_date' => now()->toDateString(),
            'category' => 'feeds', 'description' => 'Layer mash', 'quantity' => 10, 'unit' => 'bag',
            'unit_cost' => 20000, 'frequency' => 'weekly', 'vendor' => 'Feed Mill',
        ])->assertSessionHasNoErrors();

        $this->actingAs($superAdmin)->post(route('super_admin.livestock.revenue.store'), [
            'farm_id' => $farmId, 'flock_id' => $flockId, 'revenue_date' => now()->toDateString(),
            'source' => 'eggs', 'description' => 'Egg sales', 'quantity' => 5, 'unit' => 'crate',
            'unit_price' => 6000, 'customer' => 'Market Customer',
        ])->assertSessionHasNoErrors();

        $this->actingAs($superAdmin)->post(route('super_admin.livestock.production.store'), [
            'farm_id' => $farmId, 'flock_id' => $flockId, 'production_date' => now()->toDateString(),
            'opening_birds' => 500, 'mortality' => 1, 'culled' => 0, 'egg_crates' => 12,
            'loose_eggs' => 5, 'damaged_eggs' => 2, 'feed_kg' => 55, 'water_litres' => 100,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('livestock_flocks', ['id' => $flockId, 'current_birds' => 499]);
        $this->assertDatabaseHas('livestock_opex_entries', ['company_id' => $adminCompany->id, 'amount' => 200000]);
        $this->assertDatabaseHas('livestock_revenue_entries', ['company_id' => $adminCompany->id, 'amount' => 30000]);
        $this->assertSame(3, \DB::table('livestock_inventory_movements')->where('company_id', $adminCompany->id)->count());

        $this->actingAs($superAdmin)
            ->get(route('super_admin.livestock.index'))
            ->assertOk()->assertSee('Full super-admin access')->assertSee('Prime Farm')->assertSee('PRIME-BATCH-01');
    }

    public function test_registered_business_and_transfer_lists_use_the_current_company_schema(): void
    {
        $platform = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $platform->id, 'role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $business = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => 'Current Schema Limited',
            'status' => 'active',
            'plan' => 'Professional Yearly',
            'domain_prefix' => 'current-schema',
        ]);
        $owner->forceFill(['company_id' => $business->id])->save();
        Subscription::withoutGlobalScopes()->create([
            'user_id' => $owner->id,
            'company_id' => $business->id,
            'subscriber_name' => 'Current Schema Limited',
            'plan' => 'Professional Yearly',
            'billing_cycle' => 'Yearly',
            'amount' => 80000,
            'status' => 'Active',
            'payment_status' => 'paid',
            'payment_gateway' => 'bank_transfer',
            'transfer_reference' => 'TRANSFER-SCHEMA-001',
            'transfer_submitted_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.users.index', [
                'category' => 'registered_businesses',
                'payment_group' => 'paid',
                'search' => 'Current Schema',
            ]))
            ->assertOk()
            ->assertSee('Current Schema Limited');

        $this->actingAs($superAdmin)
            ->get(route('super_admin.transfer_users.index', ['search' => 'Current Schema']))
            ->assertOk()
            ->assertSee('Current Schema Limited');
    }

    public function test_registered_business_dashboard_counts_companies_including_unpaid_and_additional_workspaces(): void
    {
        $platform = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $platform->id, 'role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $paid = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => 'Paid Business',
            'status' => 'active',
            'domain_prefix' => 'paid-business',
        ]);
        $owner->forceFill(['company_id' => $paid->id])->save();

        Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => 'Awaiting Payment Business',
            'status' => 'pending',
            'domain_prefix' => 'awaiting-business',
        ]);
        $additional = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => 'Second Paid Business',
            'status' => 'active',
            'domain_prefix' => 'second-paid-business',
        ]);
        $trialOwner = User::factory()->create(['role' => 'admin']);
        $trial = Company::withoutGlobalScope('tenant')->create([
            'owner_id' => $trialOwner->id,
            'name' => 'Trial Business',
            'status' => 'trial',
        ]);
        $trialOwner->forceFill(['company_id' => $trial->id])->save();
        Company::withoutGlobalScope('tenant')->create([
            'name' => 'Guided Demo',
            'status' => 'demo',
            'is_demo' => true,
        ]);

        Subscription::withoutGlobalScopes()->create([
            'user_id' => $owner->id,
            'company_id' => $paid->id,
            'subscriber_name' => 'Paid Business',
            'plan' => 'Professional Yearly',
            'billing_cycle' => 'Yearly',
            'amount' => 80000,
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);
        Subscription::withoutGlobalScopes()->create([
            'user_id' => $trialOwner->id,
            'company_id' => $trial->id,
            'subscriber_name' => 'Trial Business',
            'plan' => 'Basic',
            'billing_cycle' => 'Monthly',
            'amount' => 20000,
            'status' => 'Active',
            'payment_status' => 'unpaid',
        ]);
        Subscription::withoutGlobalScopes()->create([
            'user_id' => $owner->id,
            'company_id' => $additional->id,
            'subscriber_name' => 'Second Paid Business',
            'plan' => 'Basic',
            'billing_cycle' => 'Monthly',
            'amount' => 10000,
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);

        $dashboard = $this->actingAs($superAdmin)->get(route('super_admin.dashboard'));
        $dashboard->assertOk();
        $this->assertSame(4, (int) $dashboard->viewData('metrics')['registered_businesses_total']);
        $this->assertSame(2, (int) $dashboard->viewData('metrics')['paid_subs']);
        $this->assertSame(90000.0, (float) $dashboard->viewData('metrics')['registered_user_revenue']);

        $registry = $this->actingAs($superAdmin)->get(route('super_admin.users.index', ['category' => 'registered_businesses']));
        $registry->assertOk()
            ->assertSee('Awaiting Payment Business')
            ->assertSee('Trial Business')
            ->assertDontSee('Guided Demo');
        $this->assertSame(4, (int) $registry->viewData('metrics')['total']);
        $this->assertSame('pending', $registry->viewData('paymentGroup'));
        $this->assertSame(2, $registry->viewData('businesses')->total());
        $pendingIds = $registry->viewData('businesses')->getCollection()->pluck('id')->all();
        $this->assertNotContains($paid->id, $pendingIds);
        $this->assertNotContains($additional->id, $pendingIds);

        $paidRegistry = $this->get(route('super_admin.users.index', ['category' => 'registered_businesses', 'payment_group' => 'paid']));
        $paidRegistry->assertOk()->assertSee('Paid Businesses')->assertSee('Paid Business')->assertSee('Second Paid Business')
            ->assertDontSee('Awaiting Payment Business')->assertDontSee('Trial Business');
        $this->assertSame(2, $paidRegistry->viewData('businesses')->total());
        $paidIds = $paidRegistry->viewData('businesses')->getCollection()->pluck('id')->all();
        $this->assertContains($paid->id, $paidIds);
        $this->assertContains($additional->id, $paidIds);

        $pendingRegistry = $this->get(route('super_admin.users.index', ['category' => 'registered_businesses', 'payment_group' => 'pending']));
        $pendingRegistry->assertOk()->assertSee('Pending Payment Businesses')->assertSee('Awaiting Payment Business')->assertSee('Trial Business')
            ->assertDontSee('Second Paid Business')
            ->assertSee('Pending Businesses')
            ->assertSee('Paid Businesses')
            ->assertSee('Edit business')
            ->assertSee('Delete Selected')
            ->assertSee(route('super_admin.users.businesses.bulk-delete'), false);
        $this->assertSame(2, $pendingRegistry->viewData('businesses')->total());
    }

    public function test_super_admin_can_bulk_delete_only_pending_payment_businesses(): void
    {
        $platform = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $platform->id, 'role' => 'super_admin']);
        $pendingOwner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $pending = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $pendingOwner->id,
            'owner_id' => $pendingOwner->id,
            'name' => 'Pending Business To Archive',
            'status' => 'pending',
        ]);
        $pendingOwner->forceFill(['company_id' => $pending->id])->save();

        $paidOwner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $paid = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $paidOwner->id,
            'owner_id' => $paidOwner->id,
            'name' => 'Paid Business To Protect',
            'status' => 'active',
        ]);
        $paidOwner->forceFill(['company_id' => $paid->id])->save();
        Subscription::withoutGlobalScopes()->create([
            'user_id' => $paidOwner->id,
            'company_id' => $paid->id,
            'subscriber_name' => 'Paid Business To Protect',
            'plan' => 'Basic',
            'billing_cycle' => 'Monthly',
            'amount' => 12000,
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($superAdmin)
            ->from(route('super_admin.users.index', ['category' => 'registered_businesses', 'payment_group' => 'pending']))
            ->delete(route('super_admin.users.businesses.bulk-delete'), [
                'business_ids' => [$pending->id, $paid->id],
            ])
            ->assertSessionHasErrors('business_ids');
        $this->assertNull(Company::withoutGlobalScope('tenant')->find($pending->id)->deleted_at);
        $this->assertNull(Company::withoutGlobalScope('tenant')->find($paid->id)->deleted_at);

        $this->delete(route('super_admin.users.businesses.bulk-delete'), [
            'business_ids' => [$pending->id],
        ])->assertRedirect();

        $this->assertNotNull(Company::withoutGlobalScope('tenant')->find($pending->id)->deleted_at);
        $this->assertNull(Company::withoutGlobalScope('tenant')->find($paid->id)->deleted_at);
        $this->assertDatabaseHas('users', ['id' => $pendingOwner->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['id' => $paidOwner->id, 'status' => 'active']);
    }

    public function test_super_admin_can_edit_and_archive_a_registered_business_without_erasing_payments(): void
    {
        $platform = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $platform->id, 'role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $business = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id, 'name' => 'Original Business', 'status' => 'active',
        ]);
        $owner->forceFill(['company_id' => $business->id])->save();
        $subscription = Subscription::withoutGlobalScopes()->create([
            'user_id' => $owner->id, 'company_id' => $business->id,
            'subscriber_name' => 'Original Business', 'plan' => 'Basic',
            'billing_cycle' => 'Monthly', 'amount' => 12000,
            'status' => 'Active', 'payment_status' => 'paid',
        ]);

        $this->actingAs($superAdmin)->put(route('super_admin.users.businesses.update', $business->id), [
            'name' => 'Updated Business', 'email' => 'business@example.com',
            'phone' => '08000000000', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('companies', ['id' => $business->id, 'name' => 'Updated Business', 'email' => 'business@example.com']);

        $this->delete(route('super_admin.users.businesses.delete', $platform->id))->assertNotFound();
        $this->delete(route('super_admin.users.businesses.delete', $business->id))->assertRedirect();
        $this->assertDatabaseHas('companies', ['id' => $business->id, 'status' => 'inactive']);
        $this->assertNotNull(Company::withoutGlobalScope('tenant')->find($business->id)->deleted_at);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'company_id' => $business->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'status' => 'inactive']);
        $this->get(route('super_admin.users.index', ['category' => 'registered_businesses']))
            ->assertOk()->assertDontSee('Updated Business');
    }
}
