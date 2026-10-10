<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Services\DemoProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSpecialistModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioned_demo_contains_hotel_and_livestock_sample_data(): void
    {
        $request = DemoRequest::create([
            'full_name' => 'Amina Bello',
            'company_name' => 'Guided Product Tour',
            'business_type' => 'General',
            'email' => 'amina.demo@example.test',
            'phone' => '08030000000',
            'country' => 'NG',
            'number_of_users' => 2,
            'purpose' => 'Evaluate specialist modules',
            'status' => 'approved',
        ]);

        $result = app(DemoProvisioningService::class)->provision($request);
        $companyId = $result['company']->id;

        $this->assertDatabaseHas('hotel_properties', [
            'company_id' => $companyId,
            'name' => 'SmartProbook Demo Hotel',
        ]);
        $this->assertDatabaseCount('hotel_rooms', 3);
        $this->assertDatabaseHas('livestock_farms', [
            'company_id' => $companyId,
            'name' => 'Green Acres Layer Farm',
        ]);
        $this->assertDatabaseHas('livestock_flocks', [
            'company_id' => $companyId,
            'breed' => 'Isa Brown',
            'status' => 'active',
        ]);
        $this->assertSame(7, \DB::table('livestock_daily_productions')->where('company_id', $companyId)->count());
        $this->assertTrue($result['user']->isDemoUser());
    }
}
