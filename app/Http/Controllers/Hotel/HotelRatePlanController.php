<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelProperty;
use App\Models\HotelRatePlan;
use App\Models\HotelRoomType;
use App\Models\HotelRateRestriction;
use App\Support\HotelPropertyContext;
use Illuminate\Http\Request;

class HotelRatePlanController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelPropertyContext::propertyId($companyId);

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

        $restrictions = HotelRateRestriction::query()->with('ratePlan')->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->orderByDesc('start_date')->limit(50)->get();

        return view('hotel.rate_plans.index', compact('plans', 'roomTypes', 'propertyId', 'restrictions'));
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

        $propertyId = HotelPropertyContext::propertyId($companyId);
        abort_unless($propertyId, 422, 'No active hotel property is configured.');

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

    public function storeRestriction(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelPropertyContext::propertyId($companyId);
        abort_unless($propertyId, 422, 'No active hotel property is configured.');
        $data = $request->validate([
            'rate_plan_id' => 'required|integer|exists:hotel_rate_plans,id', 'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date', 'applicable_days' => 'nullable|string|max:30',
            'min_stay' => 'nullable|integer|min:1|max:365', 'max_stay' => 'nullable|integer|min:1|max:365|gte:min_stay',
            'closed_to_arrival' => 'nullable|boolean', 'closed_to_departure' => 'nullable|boolean', 'stop_sell' => 'nullable|boolean', 'notes' => 'nullable|string|max:1000',
        ]);
        abort_unless(HotelRatePlan::where('company_id', $companyId)->where('property_id', $propertyId)->whereKey($data['rate_plan_id'])->exists(), 404);
        HotelRateRestriction::create([...$data, 'company_id' => $companyId, 'property_id' => $propertyId, 'created_by' => auth()->id(),
            'closed_to_arrival' => $request->boolean('closed_to_arrival'), 'closed_to_departure' => $request->boolean('closed_to_departure'), 'stop_sell' => $request->boolean('stop_sell')]);
        return back()->with('success', 'Rate restriction added.');
    }

    public function destroyRestriction(HotelRateRestriction $restriction)
    {
        abort_unless((int) $restriction->company_id === (int) auth()->user()->company_id, 404);
        $restriction->delete();
        return back()->with('success', 'Rate restriction removed.');
    }
}
