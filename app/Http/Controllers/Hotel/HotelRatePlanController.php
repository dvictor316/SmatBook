<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelProperty;
use App\Models\HotelRatePlan;
use App\Models\HotelRoomType;
use Illuminate\Http\Request;

class HotelRatePlanController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');

        $plans = HotelRatePlan::query()
            ->with('roomType')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $roomTypes = HotelRoomType::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hotel.rate_plans.index', compact('plans', 'roomTypes', 'propertyId'));
    }

    public function store(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;

        $data = $request->validate([
            'room_type_id' => 'nullable|integer|exists:hotel_room_types,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:60',
            'rate' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'applicable_days' => 'nullable|string|max:120',
            'meal_plan' => 'nullable|string|max:120',
        ]);

        $propertyId = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');

        HotelRatePlan::create([
            'company_id' => $companyId,
            'property_id' => $propertyId,
            'room_type_id' => $data['room_type_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'rate' => $data['rate'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'applicable_days' => $data['applicable_days'] ?? null,
            'meal_plan' => $data['meal_plan'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Rate plan created successfully.');
    }

    public function duplicate(HotelRatePlan $plan)
    {
        abort_unless((int) $plan->company_id === (int) auth()->user()->company_id, 404);

        $copy = $plan->replicate();
        $copy->name = $plan->name . ' (Copy)';
        $copy->code = $plan->code ? $plan->code . '-COPY' : null;
        $copy->save();

        return back()->with('success', 'Rate plan duplicated.');
    }

    public function toggle(HotelRatePlan $plan)
    {
        abort_unless((int) $plan->company_id === (int) auth()->user()->company_id, 404);

        $plan->update([
            'is_active' => !$plan->is_active,
        ]);

        return back()->with('success', 'Rate plan status updated.');
    }
}
