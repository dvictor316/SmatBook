<?php

namespace Tests\Feature\Hotel;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelTenantRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_hotel_tenant_cannot_access_hotel_dashboard(): void
    {
        $company = Company::create(['name' => 'Retail Co']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->withoutMiddleware([\App\Http\Middleware\RequireActiveBranch::class, \App\Http\Middleware\SubscriptionActive::class])
            ->get(route('hotel.dashboard'));

        $response->assertStatus(403);
    }

    public function test_hotel_tenant_can_access_hotel_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Hotel Co',
            'industry' => 'hotel',
            'plan' => 'Hotel',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->withoutMiddleware([\App\Http\Middleware\RequireActiveBranch::class, \App\Http\Middleware\SubscriptionActive::class])
            ->get(route('hotel.dashboard'));

        $response->assertStatus(200);
    }
}
