<?php

namespace Tests\Feature\Hotel;

use App\Models\Company;
use App\Models\User;
use App\Support\HotelAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_access_returns_false_when_company_has_no_hotel_data(): void
    {
        $company = Company::create(['name' => 'Acme Retail']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertFalse(HotelAccess::userIsHotelTenant($user));
    }

    public function test_hotel_access_returns_true_when_company_is_hotel_operation(): void
    {
        $company = Company::create([
            'name' => 'Acme Hotel',
            'industry' => 'hotel',
            'plan' => 'Hotel',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertTrue(HotelAccess::userIsHotelTenant($user));
    }
}
