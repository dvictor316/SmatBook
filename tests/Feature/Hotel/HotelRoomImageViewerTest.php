<?php

namespace Tests\Feature\Hotel;

use App\Http\Middleware\{RequireActiveBranch, SubscriptionActive};
use App\Models\{Company, HotelProperty, HotelRoom, HotelRoomType, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelRoomImageViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_board_and_profile_provide_manual_zoom_for_the_uploaded_room_image(): void
    {
        $company = Company::create(['name' => 'Panorama Hotel', 'industry' => 'hotel', 'plan' => 'Hotel']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $property = HotelProperty::create(['company_id' => $company->id, 'name' => 'Main Property', 'code' => 'MAIN', 'is_active' => true]);
        $type = HotelRoomType::create(['company_id' => $company->id, 'property_id' => $property->id, 'name' => 'King', 'code' => 'KING', 'base_rate' => 20000, 'is_active' => true]);
        $room = HotelRoom::create(['company_id' => $company->id, 'property_id' => $property->id,
            'room_type_id' => $type->id, 'room_number' => '101', 'panorama_image' => 'hotel/test-panorama.jpg',
            'operational_status' => 'available', 'housekeeping_status' => 'clean', 'is_active' => true]);
        $this->actingAs($user)->withoutMiddleware([RequireActiveBranch::class, SubscriptionActive::class])
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters']);
        foreach ([route('hotel.rooms.index'), route('hotel.rooms.show', $room)] as $url) {
            $this->get($url)->assertOk()->assertSee('hotel/test-panorama.jpg')->assertSee('data-room-viewport', false)
                ->assertSee('Fit room')->assertSee('hotel-room-viewer.js')->assertDontSee('hotelPanoramaTour');
        }
    }
}
