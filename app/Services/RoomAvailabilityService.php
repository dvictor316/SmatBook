<?php
namespace App\Services;

use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Support\Facades\Schema;

class RoomAvailabilityService
{
    /**
     * Check if a room is available for given date range (arrival inclusive, departure exclusive)
     */
    public static function isRoomAvailable(int $roomId, string $arrivalDate, string $departureDate): bool
    {
        // Overlap rule: [arrival, departure) intersects [existing_arrival, existing_departure)
        $overlap = Reservation::where('room_id', $roomId)
            ->whereIn('status', ['reserved', 'confirmed', 'checked_in'])
            ->whereDate('arrival_date', '<', $departureDate)
            ->whereDate('departure_date', '>', $arrivalDate)
            ->exists();

        if ($overlap) {
            return false;
        }

        // Also check stays (occupied)
        $occupied = false;
        if (Schema::hasTable('stays')) {
            $occupied = \DB::table('stays')
                ->where('room_id', $roomId)
                ->where('status', 'checked_in')
                ->where(function ($q) use ($arrivalDate, $departureDate) {
                    $q->whereRaw('COALESCE(expected_checkout_at, NOW()) > ?', [$arrivalDate.' 00:00:00'])
                      ->whereRaw('checkin_at < ?', [$departureDate.' 23:59:59']);
                })
                ->exists();
        }

        return !$occupied;
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
        return $rooms->filter(function ($room) use ($arrivalDate, $departureDate) {
            return self::isRoomAvailable($room->id, $arrivalDate, $departureDate);
        })->values();
    }
}
