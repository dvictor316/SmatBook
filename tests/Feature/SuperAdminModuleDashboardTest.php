<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
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
            ->assertSee('Livestock tenants only');
    }
}
