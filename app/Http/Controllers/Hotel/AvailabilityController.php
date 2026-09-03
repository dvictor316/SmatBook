<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoomAvailabilityService;
use App\Models\HotelProperty;
use App\Models\HotelRoom;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $property = HotelProperty::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->first();
        return view('hotel.availability.index', compact('property'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date'
        ]);

        $propertyId = $request->input('property_id') ?: optional(
            HotelProperty::where('company_id', auth()->user()->company_id)
                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->first()
        )->id;

        if (!$propertyId) {
            return back()->withErrors(['error' => 'No active hotel property found for current branch.'])->withInput();
        }

        $rooms = RoomAvailabilityService::availableRoomsForProperty($propertyId, $request->arrival_date, $request->departure_date);
        return view('hotel.availability.results', compact('rooms'));
    }

    public function roomsJson(Request $request)
    {
        $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'property_id' => 'nullable|integer',
            'room_type_id' => 'nullable|integer',
        ]);

        $propertyId = $request->input('property_id') ?: optional(
            HotelProperty::where('company_id', auth()->user()->company_id)
                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->first()
        )->id;

        if (!$propertyId) {
            return response()->json(['rooms' => [], 'message' => 'No active hotel property found.'], 422);
        }

        $availableIds = RoomAvailabilityService::availableRoomsForProperty(
            (int) $propertyId,
            (string) $request->arrival_date,
            (string) $request->departure_date
        )->pluck('id')->all();

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', auth()->user()->company_id)
            ->where('property_id', (int) $propertyId)
            ->when($request->filled('room_type_id'), fn ($query) => $query->where('room_type_id', (int) $request->room_type_id))
            ->where('is_active', true)
            ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
            ->get()
            ->map(fn ($room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type_id' => $room->room_type_id,
                'room_type' => $room->type?->name,
                'rate' => (float) ($room->base_rate_override ?: ($room->type?->base_rate ?? 0)),
                'operational_status' => $room->operational_status,
                'housekeeping_status' => $room->housekeeping_status,
                'available' => in_array((int) $room->id, array_map('intval', $availableIds), true),
            ]);

        return response()->json([
            'rooms' => $rooms,
            'available_count' => $rooms->where('available', true)->count(),
            'unavailable_count' => $rooms->where('available', false)->count(),
        ]);
    }
}
