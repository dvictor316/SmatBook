<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotelRoomType;
use App\Models\HotelProperty;
use Illuminate\Support\Facades\Auth;

class HotelRoomTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $companyId = Auth::user()->company_id;
        $propertyId = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->value('id');

        $types = HotelRoomType::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->paginate(20);
        return view('hotel.room_types.index', compact('types'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $properties = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->get();

        return view('hotel.room_types.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $data = $request->validate([
            'property_id' => 'nullable|exists:hotel_properties,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'bed_type' => 'nullable|string|max:255',
            'beds' => 'nullable|integer|min:0',
            'max_adults' => 'nullable|integer|min:0',
            'max_children' => 'nullable|integer|min:0',
            'max_occupancy' => 'nullable|integer|min:0',
            'base_rate' => 'nullable|numeric|min:0',
        ]);

        $data['company_id'] = $companyId;
        HotelRoomType::create($data);
        return redirect()->route('hotel.room_types.index')->with('success', 'Room type created.');
    }

    public function edit(HotelRoomType $room_type)
    {
        abort_unless($room_type->company_id === Auth::user()->company_id, 404);
        return view('hotel.room_types.edit', ['type' => $room_type]);
    }

    public function update(Request $request, HotelRoomType $room_type)
    {
        abort_unless($room_type->company_id === Auth::user()->company_id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'bed_type' => 'nullable|string|max:255',
            'beds' => 'nullable|integer|min:0',
            'max_adults' => 'nullable|integer|min:0',
            'max_children' => 'nullable|integer|min:0',
            'max_occupancy' => 'nullable|integer|min:0',
            'base_rate' => 'nullable|numeric|min:0',
        ]);

        $room_type->update($data);
        return redirect()->route('hotel.room_types.index')->with('success', 'Room type updated.');
    }

    public function destroy(HotelRoomType $room_type)
    {
        abort_unless($room_type->company_id === Auth::user()->company_id, 404);
        // soft-deactivate rather than hard delete when in use
        $room_type->is_active = false;
        $room_type->save();
        return redirect()->route('hotel.room_types.index')->with('success', 'Room type deactivated.');
    }
}
