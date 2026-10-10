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

    public function test_super_admin_can_open_livestock_oversight_from_sidebar(): void
    {
        $adminCompany = Company::create(['name' => 'Platform Administration']);
        $superAdmin = User::factory()->create(['company_id' => $adminCompany->id, 'role' => 'super_admin']);
        $farmCompany = Company::create(['name' => 'Sunrise Layers', 'industry' => 'Livestock / Layer Farm']);

        \DB::table('livestock_farms')->insert([
            'company_id' => $farmCompany->id,
            'name' => 'Main Layer Farm',
            'code' => 'SL-001',
            'farm_type' => 'layers',
            'bird_capacity' => 2000,
            'eggs_per_crate' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.livestock.index'))
            ->assertOk()
            ->assertSee('Livestock Management')
            ->assertSee('Sunrise Layers')
            ->assertSee('Main Layer Farm');
    }

    public function test_demo_user_is_entitled_to_both_specialist_workspaces(): void
    {
        $company = Company::create(['name' => 'Guided Demo', 'is_demo' => true, 'status' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->assertTrue(HotelAccess::userIsHotelTenant($user));
        $this->assertTrue(LivestockAccess::userIsLivestockTenant($user));
    }
}
