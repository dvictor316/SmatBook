<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\HotelOperationalEvent;
use App\Models\Stay;
use App\Services\RoomAvailabilityService;
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
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $query->where(function ($sub) use ($term) {
                    $sub->where('reservation_number', 'like', '%' . $term . '%')
                        ->orWhere('source', 'like', '%' . $term . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('customer_name', 'like', '%' . $term . '%')
                                ->orWhere('phone', 'like', '%' . $term . '%')
                                ->orWhere('email', 'like', '%' . $term . '%');
                        });
                });
            })
            ->with(['customer', 'roomType', 'room'])
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

        $arrivalDate = (string) $request->query('arrival_date', now()->toDateString());
        $departureDate = (string) $request->query('departure_date', now()->addDay()->toDateString());
        $prefilledRoomTypeId = (int) $request->query('room_type_id', 0);
        $prefilledRoomId = (int) $request->query('room_id', 0);

        $availableRooms = collect();
        if ($property && $request->filled('arrival_date') && $request->filled('departure_date')) {
            $availableRooms = RoomAvailabilityService::availableRoomsForProperty((int) $property->id, $arrivalDate, $departureDate);
        }

        return view('hotel.reservations.create', compact('property', 'roomTypes', 'availableRooms', 'arrivalDate', 'departureDate', 'prefilledRoomTypeId', 'prefilledRoomId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'room_id' => 'nullable|exists:hotel_rooms,id',
            'customer_id' => 'nullable|exists:customers,id',
            'nights' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'nightly_rate' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'deposit_received' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:120',
            'special_requests' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:1000',
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
        $depositReceived = (float) ($data['deposit_received'] ?? 0);

        $roomId = isset($data['room_id']) && (int) $data['room_id'] > 0 ? (int) $data['room_id'] : null;
        if ($roomId) {
            $room = HotelRoom::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('property_id', $propertyId)
                ->findOrFail($roomId);

            if (!RoomAvailabilityService::isRoomAvailable($room->id, $arrival->toDateString(), $departure->toDateString())) {
                return back()->withErrors(['error' => 'Selected room is unavailable for those dates.'])->withInput();
            }
        }

        $reservation = Reservation::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
            'property_id' => $propertyId,
            'reservation_number' => strtoupper(Str::random(8)),
            'room_id' => $roomId,
            'nights' => $nights,
            'adults' => (int) ($data['adults'] ?? 1),
            'children' => (int) ($data['children'] ?? 0),
            'nightly_rate' => $nightlyRate,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'deposit_required' => $depositRequired,
            'deposit_received' => $depositReceived,
            'balance' => max(0, $subtotal - $depositReceived),
            'status' => 'reserved',
            'source' => $data['source'] ?? 'direct',
            'special_requests' => $data['special_requests'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'created_by' => auth()->id(),
        ]));

        HotelOperationalEvent::create([
            'company_id' => $reservation->company_id,
            'property_id' => $reservation->property_id,
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'room_id' => $reservation->room_id,
            'event_type' => 'reservation.created',
            'title' => 'Reservation created',
            'description' => 'Reservation ' . $reservation->reservation_number . ' created.',
            'meta' => ['arrival_date' => $reservation->arrival_date?->toDateString(), 'departure_date' => $reservation->departure_date?->toDateString()],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('hotel.reservations.show', $reservation)->with('success','Reservation created');
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);

        $reservation->load(['customer', 'room', 'roomType']);
        $stay = Stay::query()
            ->where('company_id', $reservation->company_id)
            ->where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        $events = HotelOperationalEvent::query()
            ->where('company_id', $reservation->company_id)
            ->where(function ($query) use ($reservation, $stay) {
                $query->where('reservation_id', $reservation->id);
                if ($stay) {
                    $query->orWhere('stay_id', $stay->id);
                }
            })
            ->latest('id')
            ->limit(30)
            ->get();

        $availableRooms = HotelRoom::query()
            ->where('company_id', $reservation->company_id)
            ->where('property_id', $reservation->property_id)
            ->where('is_active', true)
            ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
            ->get();

        return view('hotel.reservations.show', compact('reservation', 'stay', 'events', 'availableRooms'));
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
