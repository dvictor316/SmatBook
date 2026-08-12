<?php

namespace Tests\Feature\Hotel;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelSuperAdminOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_hotel_overview_loads_without_industry_column_crash(): void
    {
        $company = Company::create(['name' => 'Super Company']);

        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('super_admin.hotels.index'));

        $response->assertStatus(200);
        $response->assertSee('Hotel Overview');
    }
}
