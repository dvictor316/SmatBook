<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoomAvailabilityService;
use App\Models\HotelProperty;

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
}
