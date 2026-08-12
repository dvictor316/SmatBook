<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\HotelProperty;
use App\Models\HotelRoomType;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : $this->currentPropertyId();

        $reservations = Reservation::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('arrival_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('departure_date', '<=', $request->to_date))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $properties = HotelProperty::where('company_id', $companyId)->orderBy('name')->get();
        return view('hotel.reservations.index', compact('reservations', 'properties', 'propertyId'));
    }

    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $property = HotelProperty::where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->first();
        $roomTypes = $property ? HotelRoomType::where('company_id', $companyId)->where('property_id', $property->id)->where('is_active', true)->get() : collect();
        return view('hotel.reservations.create', compact('property', 'roomTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'customer_id' => 'nullable|exists:customers,id',
            'nights' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'nightly_rate' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
        ]);

        $propertyId = $this->currentPropertyId();
        if (!$propertyId) {
            return back()->withErrors(['error' => 'No active hotel property found for current branch.'])->withInput();
        }

        $arrival = \Illuminate\Support\Carbon::parse($data['arrival_date']);
        $departure = \Illuminate\Support\Carbon::parse($data['departure_date']);
        $nights = max(1, (int) ($data['nights'] ?? $arrival->diffInDays($departure)));
        $nightlyRate = (float) ($data['nightly_rate'] ?? 0);
        $subtotal = $nightlyRate * $nights;
        $depositRequired = (float) ($data['deposit_required'] ?? 0);

        $reservation = Reservation::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
            'property_id' => $propertyId,
            'reservation_number' => strtoupper(Str::random(8)),
            'nights' => $nights,
            'adults' => (int) ($data['adults'] ?? 1),
            'children' => (int) ($data['children'] ?? 0),
            'nightly_rate' => $nightlyRate,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'deposit_required' => $depositRequired,
            'balance' => $subtotal,
            'status' => 'reserved',
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('hotel.reservations.show', $reservation)->with('success','Reservation created');
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);
        return view('hotel.reservations.show', compact('reservation'));
    }

    private function currentPropertyId(): ?int
    {
        $companyId = auth()->user()->company_id;
        $branchId = auth()->user()->branch_id;

        $property = HotelProperty::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        return $property?->id;
    }
}
