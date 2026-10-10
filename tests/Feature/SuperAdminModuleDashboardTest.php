<?php

namespace Tests\Feature;

use App\Models\Company;
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
}
