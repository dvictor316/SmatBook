<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\HotelProperty;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = Reservation::where('company_id', auth()->user()->company_id)->paginate(20);
        return view('hotel.reservations.index', compact('reservations'));
    }

    public function create(Request $request)
    {
        $property = HotelProperty::where('company_id', auth()->user()->company_id)->first();
        return view('hotel.reservations.create', compact('property'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'customer_id' => 'nullable|exists:customers,id',
            'nights' => 'required|integer|min:1',
        ]);

        $reservation = Reservation::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
            'property_id' => HotelProperty::where('company_id', auth()->user()->company_id)->first()->id,
            'reservation_number' => strtoupper(Str::random(8)),
            'status' => 'reserved'
        ]));

        return redirect()->route('hotel.reservations.show', $reservation)->with('success','Reservation created');
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);
        return view('hotel.reservations.show', compact('reservation'));
    }
}
