<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\HotelProperty;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Support\Facades\Auth;

class HotelRoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $propertyId = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->value('id');

        $status = trim((string) $request->query('status', ''));
        $viewMode = (string) $request->query('view', 'grid');

        $rooms = HotelRoom::with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($status !== '', fn ($q) => $q->where(function ($sub) use ($status) {
                $sub->where('operational_status', $status)
                    ->orWhere('housekeeping_status', $status);
            }))
            ->paginate(30);

        $activeStays = Stay::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->get()
            ->keyBy('room_id');

        $nextReservations = Reservation::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereIn('room_id', $rooms->pluck('id'))
            ->whereDate('arrival_date', '>=', now()->toDateString())
            ->orderBy('arrival_date')
            ->get()
            ->groupBy('room_id')
            ->map(fn ($items) => $items->first());

        $summary = [
            'available' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('operational_status', 'available')->count(),
            'occupied' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('operational_status', 'occupied')->count(),
            'dirty' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('housekeeping_status', 'dirty')->count(),
            'maintenance' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->whereIn('operational_status', ['maintenance', 'out_of_order'])->count(),
        ];

        return view('hotel.rooms.index', compact('rooms', 'activeStays', 'nextReservations', 'summary', 'status', 'viewMode'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $propertyId = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->value('id');

        $roomTypes = HotelRoomType::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('is_active', true)
            ->get();

        $properties = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->get();

        return view('hotel.rooms.create', compact('roomTypes', 'properties'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $data = $request->validate([
            'property_id' => 'required|exists:hotel_properties,id',
            'room_number' => 'required|string|max:50',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'floor' => 'nullable|string|max:50',
            'wing' => 'nullable|string|max:50',
            'base_rate_override' => 'nullable|numeric|min:0',
            'operational_status' => 'nullable|string|max:50',
            'housekeeping_status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;

        // enforce uniqueness per property
        if (HotelRoom::where('property_id', $data['property_id'])->where('room_number', $data['room_number'])->exists()) {
            return back()->withErrors(['room_number' => 'Room number already exists for this property'])->withInput();
        }

        HotelRoom::create($data);
        return redirect()->route('hotel.rooms.index')->with('success', 'Room created.');
    }

    public function edit(HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $companyId = Auth::user()->company_id;
        $roomTypes = HotelRoomType::where('company_id', $companyId)->where('is_active', true)->get();
        return view('hotel.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $data = $request->validate([
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'floor' => 'nullable|string|max:50',
            'wing' => 'nullable|string|max:50',
            'base_rate_override' => 'nullable|numeric|min:0',
            'operational_status' => 'nullable|string|max:50',
            'housekeeping_status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $room->update($data);
        return redirect()->route('hotel.rooms.index')->with('success', 'Room updated.');
    }

    public function destroy(HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $room->is_active = false;
        $room->save();
        return redirect()->route('hotel.rooms.index')->with('success', 'Room deactivated.');
    }
}
