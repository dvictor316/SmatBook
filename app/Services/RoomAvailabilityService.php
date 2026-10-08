<?php

namespace App\Services;

use App\Models\HotelRoom;
use App\Models\HotelRoomBlock;
use App\Models\HotelGroupRoomAllocation;
use App\Models\Reservation;
use Illuminate\Support\Facades\Schema;

class RoomAvailabilityService
{
    /**
     * Check if a room is available for given date range (arrival inclusive, departure exclusive)
     */
    public static function isRoomAvailable(int $roomId, string $arrivalDate, string $departureDate, ?int $excludeReservationId = null): bool
    {
        $room = HotelRoom::query()->find($roomId);
        if (! $room || ! $room->is_active || in_array((string) $room->operational_status, ['maintenance', 'out_of_order'], true)) {
            return false;
        }

        // Overlap rule: [arrival, departure) intersects [existing_arrival, existing_departure)
        $overlap = Reservation::where('room_id', $roomId)
            ->when($excludeReservationId, fn ($query) => $query->whereKeyNot($excludeReservationId))
            ->whereIn('status', ['reserved', 'confirmed', 'checked_in'])
            ->whereDate('arrival_date', '<', $departureDate)
            ->whereDate('departure_date', '>', $arrivalDate)
            ->exists();

        if ($overlap) {
            return false;
        }

        if (Schema::hasTable('hotel_room_blocks')) {
            $blocked = HotelRoomBlock::query()
                ->where('company_id', $room->company_id)
                ->where('room_id', $roomId)
                ->where('status', 'active')
                ->whereDate('start_date', '<', $departureDate)
                ->whereDate('end_date', '>=', $arrivalDate)
                ->exists();

            if ($blocked) {
                return false;
            }
        }

        // Also check stays (occupied)
        $occupied = false;
        if (Schema::hasTable('stays')) {
            $occupied = \DB::table('stays')
                ->where('room_id', $roomId)
                ->where('status', 'checked_in')
                ->where('checkin_at', '<', $departureDate.' 00:00:00')
                ->where(function ($q) use ($arrivalDate) {
                    $q->whereNull('expected_checkout_at')
                        ->orWhere('expected_checkout_at', '>', $arrivalDate.' 00:00:00');
                })
                ->exists();
        }

        return ! $occupied;
    }

    /**
     * Return available rooms for a given property and date range
     */
    public static function availableRoomsForProperty(int $propertyId, string $arrivalDate, string $departureDate)
    {
        $rooms = HotelRoom::where('property_id', $propertyId)
            ->where('is_active', true)
            ->whereNotIn('operational_status', ['maintenance', 'out_of_order'])
            ->get();

        $available = $rooms->filter(function ($room) use ($arrivalDate, $departureDate) {
            return self::isRoomAvailable($room->id, $arrivalDate, $departureDate);
        })->values();

        if (! Schema::hasTable('hotel_group_room_allocations')) {
            return $available;
        }

        $groupBlocks = HotelGroupRoomAllocation::query()
            ->selectRaw('hotel_group_room_allocations.room_type_id, SUM(hotel_group_room_allocations.rooms) as blocked_rooms')
            ->join('hotel_group_bookings', 'hotel_group_bookings.id', '=', 'hotel_group_room_allocations.group_booking_id')
            ->where('hotel_group_room_allocations.property_id', $propertyId)
            ->whereNull('hotel_group_room_allocations.room_id')
            ->whereIn('hotel_group_room_allocations.status', ['blocked', 'reserved'])
            ->whereIn('hotel_group_bookings.status', ['tentative', 'confirmed', 'in_house'])
            ->whereDate('hotel_group_bookings.arrival_date', '<', $departureDate)
            ->whereDate('hotel_group_bookings.departure_date', '>', $arrivalDate)
            ->where(function ($query) {
                $query->whereNull('hotel_group_bookings.release_date')
                    ->orWhereDate('hotel_group_bookings.release_date', '>=', now()->toDateString());
            })
            ->groupBy('hotel_group_room_allocations.room_type_id')
            ->pluck('blocked_rooms', 'room_type_id');

        foreach ($groupBlocks as $roomTypeId => $blockedRooms) {
            $toRemove = $available->where('room_type_id', (int) $roomTypeId)
                ->sortByDesc('id')
                ->take((int) $blockedRooms)
                ->pluck('id');
            $available = $available->reject(fn ($room) => $toRemove->contains($room->id))->values();
        }

        return $available;
    }
}
