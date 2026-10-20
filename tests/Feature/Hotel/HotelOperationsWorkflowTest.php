<?php

namespace Tests\Feature\Hotel;

use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\Company;
use App\Models\GuestFolio;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\HotelHousekeepingTask;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\User;
use App\Models\HotelRoomBlock;
use App\Services\RoomAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private HotelProperty $property;

    private HotelRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Enterprise Hotel',
            'industry' => 'hotel',
            'plan' => 'Hotel',
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->property = HotelProperty::create([
            'company_id' => $this->company->id,
            'name' => 'Grand Property',
            'code' => 'GRAND',
            'is_active' => true,
        ]);
        $roomType = HotelRoomType::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'name' => 'Executive King',
            'code' => 'EK',
            'base_rate' => 50000,
            'is_active' => true,
        ]);
        $this->room = HotelRoom::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '1001',
            'operational_status' => 'available',
            'housekeeping_status' => 'clean',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->withoutMiddleware([RequireActiveBranch::class, SubscriptionActive::class]);
    }

    public function test_staff_can_open_and_resolve_a_guest_request(): void
    {
        $stay = Stay::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'room_id' => $this->room->id,
            'checkin_at' => now(),
            'expected_checkout_at' => now()->addDay(),
            'agreed_rate' => 50000,
            'status' => 'checked_in',
            'checked_in_by' => $this->user->id,
        ]);

        $this->post(route('hotel.operations.requests.store'), [
            'stay_id' => $stay->id,
            'category' => 'concierge',
            'department' => 'concierge',
            'priority' => 'urgent',
            'subject' => 'Airport transfer required',
        ])->assertRedirect();

        $guestRequest = \App\Models\HotelGuestRequest::firstOrFail();
        $this->assertSame('open', $guestRequest->status);
        $this->assertSame($this->room->id, $guestRequest->room_id);
        $this->assertTrue($guestRequest->due_at->between(now()->addMinutes(14), now()->addMinutes(16)));

        $this->put(route('hotel.operations.requests.update', $guestRequest), [
            'status' => 'resolved',
            'priority' => 'urgent',
            'assigned_to' => $this->user->id,
            'resolution_note' => 'Driver confirmed and guest notified.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hotel_guest_requests', [
            'id' => $guestRequest->id,
            'status' => 'resolved',
            'resolved_by' => $this->user->id,
        ]);
    }

    public function test_reservation_amendment_recalculates_totals_and_status_lifecycle(): void
    {
        $reservation = Reservation::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'reservation_number' => 'RES-1001',
            'room_id' => $this->room->id,
            'arrival_date' => now()->addDays(2)->toDateString(),
            'departure_date' => now()->addDays(3)->toDateString(),
            'nights' => 1,
            'adults' => 1,
            'nightly_rate' => 50000,
            'subtotal' => 50000,
            'total' => 50000,
            'balance' => 50000,
            'status' => 'reserved',
        ]);

        $this->put(route('hotel.reservations.update', $reservation), [
            'arrival_date' => now()->addDays(2)->toDateString(),
            'departure_date' => now()->addDays(5)->toDateString(),
            'room_id' => $this->room->id,
            'adults' => 2,
            'children' => 1,
            'nightly_rate' => 60000,
            'discount' => 10000,
            'tax' => 5000,
            'service_charge' => 5000,
            'other_charges' => 0,
            'deposit_required' => 50000,
            'deposit_received' => 20000,
            'source' => 'Corporate',
        ])->assertRedirect();

        $reservation->refresh();
        $this->assertSame(3, $reservation->nights);
        $this->assertEquals(180000, $reservation->subtotal);
        $this->assertEquals(180000, $reservation->total);
        $this->assertEquals(160000, $reservation->balance);

        $this->post(route('hotel.reservations.status', $reservation), [
            'status' => 'cancelled',
            'reason' => 'Guest changed travel plans.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->user->id,
        ]);
    }

    public function test_reservation_creation_uses_actual_nights_and_all_pricing_components(): void
    {
        $this->get(route('hotel.reservations.create'))
            ->assertOk()
            ->assertSee('Walk-in / guest details to follow')
            ->assertSee('Rate Plan');

        $response = $this->post(route('hotel.reservations.store'), [
            'arrival_date' => now()->toDateString(),
            'departure_date' => now()->addDays(2)->toDateString(),
            'nights' => 1,
            'room_type_id' => $this->room->room_type_id,
            'room_id' => $this->room->id,
            'nightly_rate' => 50000,
            'discount' => 1000,
            'tax' => 7500,
            'service_charge' => 5000,
            'other_charges' => 2000,
        ]);

        $reservation = Reservation::firstOrFail();
        $response->assertRedirect(route('hotel.reservations.show', $reservation));
        $this->assertSame(2, $reservation->nights);
        $this->assertEquals(100000, $reservation->subtotal);
        $this->assertEquals(1000, $reservation->discount);
        $this->assertEquals(7500, $reservation->tax);
        $this->assertEquals(5000, $reservation->service_charge);
        $this->assertEquals(2000, $reservation->other_charges);
        $this->assertEquals(113500, $reservation->total);
        $this->assertEquals(113500, $reservation->balance);
    }

    public function test_guest_can_complete_reservation_folio_checkout_and_housekeeping_journey(): void
    {
        $this->post(route('hotel.reservations.store'), [
            'arrival_date' => now()->toDateString(),
            'departure_date' => now()->addDay()->toDateString(),
            'room_type_id' => $this->room->room_type_id,
            'room_id' => $this->room->id,
            'nightly_rate' => 10000,
            'deposit_received' => 10000,
        ])->assertRedirect();

        $reservation = Reservation::firstOrFail();
        $this->post(route('hotel.checkin', $reservation))->assertRedirect();
        $stay = Stay::where('reservation_id', $reservation->id)->firstOrFail();
        $folio = GuestFolio::where('stay_id', $stay->id)->firstOrFail();

        app(\App\Services\Hotel\HotelFolioService::class)->postCharge($folio, [
            'description' => 'Room service',
            'amount' => 25000,
            'service_code' => 'ROOM_SERVICE',
        ]);
        $this->post(route('hotel.folios.payments.store', $folio), [
            'amount' => 5000,
            'payment_method' => 'cash',
            'reference' => 'JOURNEY-PAYMENT-1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('hotel.checkout.index', ['stay_id' => $stay->id]))
            ->assertOk()
            ->assertSee('value="10000.00"', false);

        $this->post(route('hotel.checkout', $stay), [
            'settlement_method' => 'cash',
            'paid_amount' => 10000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('checked_out', $stay->fresh()->status);
        $this->assertSame('completed', $reservation->fresh()->status);
        $this->assertSame('closed', $folio->fresh()->status);
        $this->assertSame('dirty', $this->room->fresh()->housekeeping_status);
        $this->assertSame(1, HotelHousekeepingTask::where('stay_id', $stay->id)->where('status', 'open')->count());

        $folioItemCount = $folio->items()->count();
        $this->post(route('hotel.checkout', $stay), [
            'settlement_method' => 'cash',
            'paid_amount' => 0,
        ])->assertSessionHasErrors('error');
        $this->assertSame($folioItemCount, $folio->items()->count());

        $task = HotelHousekeepingTask::where('stay_id', $stay->id)->firstOrFail();
        $this->post(route('hotel.housekeeping.tasks.complete', $task))->assertRedirect();
        $this->assertSame('clean', $this->room->fresh()->housekeeping_status);
        $this->assertSame('available', $this->room->fresh()->operational_status);
    }

    public function test_dirty_room_is_not_checkin_ready_for_front_desk_staff(): void
    {
        $this->user->update(['role' => 'frontdesk']);
        $this->room->update(['housekeeping_status' => 'dirty']);
        $this->post(route('hotel.reservations.store'), [
            'arrival_date' => now()->toDateString(),
            'departure_date' => now()->addDay()->toDateString(),
            'room_type_id' => $this->room->room_type_id,
            'room_id' => $this->room->id,
            'nightly_rate' => 10000,
        ])->assertRedirect();
        $reservation = Reservation::firstOrFail();

        $this->get(route('hotel.checkin.index', ['arrival' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Room Dirty')
            ->assertSee('disabled', false);
        $this->post(route('hotel.checkin', $reservation))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertDatabaseMissing('stays', ['reservation_id' => $reservation->id]);
    }

    public function test_completing_a_housekeeping_task_does_not_clear_other_tasks_or_maintenance_state(): void
    {
        $this->room->update(['housekeeping_status' => 'dirty']);
        $tasks = collect(['checkout_clean', 'deep_clean'])->map(fn ($type) => HotelHousekeepingTask::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'room_id' => $this->room->id,
            'task_type' => $type,
            'status' => 'open',
            'priority' => 'normal',
            'created_by' => $this->user->id,
        ]));

        $this->post(route('hotel.housekeeping.tasks.complete', $tasks->first()))->assertRedirect();
        $this->assertSame('dirty', $this->room->fresh()->housekeeping_status);
        $this->assertSame('available', $this->room->fresh()->operational_status);

        $this->post(route('hotel.housekeeping.tasks.complete', $tasks->last()))->assertRedirect();
        $this->assertSame('clean', $this->room->fresh()->housekeeping_status);

        $this->room->update(['operational_status' => 'maintenance', 'housekeeping_status' => 'dirty']);
        $this->post(route('hotel.housekeeping.rooms.clean', $this->room))->assertRedirect();
        $this->assertSame('maintenance', $this->room->fresh()->operational_status);
        $this->assertSame('clean', $this->room->fresh()->housekeeping_status);
    }

    public function test_folio_payment_must_settle_before_closure(): void
    {
        $stay = Stay::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'room_id' => $this->room->id,
            'checkin_at' => now()->subDay(),
            'expected_checkout_at' => now()->addDay(),
            'agreed_rate' => 50000,
            'status' => 'checked_in',
        ]);
        $folio = GuestFolio::create([
            'company_id' => $this->company->id,
            'property_id' => $this->property->id,
            'stay_id' => $stay->id,
            'folio_number' => 'FOLIO-1001',
            'status' => 'open',
        ]);
        app(\App\Services\Hotel\HotelFolioService::class)->postCharge($folio, [
            'description' => 'Room charge',
            'amount' => 50000,
            'service_code' => 'ROOM_NIGHT',
        ]);

        $this->post(route('hotel.folios.close', $folio))->assertSessionHasErrors('folio');

        $response = $this->post(route('hotel.folios.payments.store', $folio), [
            'amount' => 50000,
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-1001',
        ]);
        $response->assertRedirect();
        $this->assertStringContainsString('/receipt', (string) $response->headers->get('Location'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('folio_items', [
            'folio_id' => $folio->id,
            'type' => 'payment',
            'amount' => 50000,
        ]);

        $folio->refresh();
        $this->assertEquals(0, $folio->balance);
        $this->post(route('hotel.folios.payments.store', $folio), [
            'amount' => 1,
            'payment_method' => 'cash',
            'reference' => 'BANK-OVERPAY',
        ])->assertSessionHasErrors('amount');
        $stay->update(['status' => 'completed', 'actual_checkout_at' => now()]);
        $this->post(route('hotel.folios.close', $folio))->assertRedirect();
        $this->assertSame('closed', $folio->fresh()->status);
    }

    public function test_blocked_room_is_excluded_from_availability(): void
    {
        HotelRoomBlock::create([
            'company_id' => $this->company->id, 'property_id' => $this->property->id, 'room_id' => $this->room->id,
            'start_date' => now()->addDays(2)->toDateString(), 'end_date' => now()->addDays(4)->toDateString(),
            'block_type' => 'maintenance', 'reason' => 'Air conditioning replacement', 'status' => 'active', 'created_by' => $this->user->id,
        ]);

        $this->assertFalse(RoomAvailabilityService::isRoomAvailable(
            $this->room->id,
            now()->addDays(3)->toDateString(),
            now()->addDays(5)->toDateString()
        ));
        $this->assertFalse(RoomAvailabilityService::availableRoomsForProperty(
            $this->property->id,
            now()->addDays(3)->toDateString(),
            now()->addDays(5)->toDateString()
        )->contains('id', $this->room->id));
    }
}
