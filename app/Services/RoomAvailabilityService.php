<?php
namespace App\Services;

use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class RoomAvailabilityService
{
    /**
     * Check if a room is available for given date range (arrival inclusive, departure exclusive)
     */
    public static function isRoomAvailable(int $roomId, string $arrivalDate, string $departureDate): bool
    {
        // A room is unavailable if there is a reservation overlapping the requested dates
        $overlap = Reservation::where('room_id', $roomId)
            ->where(function ($q) use ($arrivalDate, $departureDate) {
                $q->whereBetween('arrival_date', [$arrivalDate, Carbon::parse($departureDate)->subDay()->toDateString()])
                  ->orWhereBetween('departure_date', [Carbon::parse($arrivalDate)->addDay()->toDateString(), $departureDate]);
            })->exists();

        if ($overlap) {
            return false;
        }

        // Also check stays (occupied)
        $occupied = \DB::table('stays')
            ->where('room_id', $roomId)
            ->where(function ($q) use ($arrivalDate, $departureDate) {
                $q->whereBetween('checkin_at', [$arrivalDate.' 00:00:00', $departureDate.' 23:59:59'])
                  ->orWhereBetween('expected_checkout_at', [$arrivalDate.' 00:00:00', $departureDate.' 23:59:59']);
            })->exists();

        return !$occupied;
    }

    /**
     * Return available rooms for a given property and date range
     */
    public static function availableRoomsForProperty(int $propertyId, string $arrivalDate, string $departureDate)
    {
        $rooms = HotelRoom::where('property_id', $propertyId)->get();
        return $rooms->filter(function ($room) use ($arrivalDate, $departureDate) {
            return self::isRoomAvailable($room->id, $arrivalDate, $departureDate);
        })->values();
    }
}
