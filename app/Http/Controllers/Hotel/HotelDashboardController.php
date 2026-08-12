<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\GuestFolio;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HotelDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $totalRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->count();

        $availableRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'available')
            ->count();

        $occupiedRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'occupied')
            ->count();

        $reservedRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'reserved')
            ->count();

        $dirtyRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('housekeeping_status', 'dirty')
            ->count();

        $maintenanceRooms = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'maintenance')
            ->count();

        $today = now()->toDateString();

        $todayArrivals = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->whereDate('arrival_date', $today)
                ->count()
            : 0;

        $todayDepartures = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->whereDate('departure_date', $today)
                ->count()
            : 0;

        $inHouseGuests = Schema::hasTable('stays')
            ? Stay::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->where('status', 'checked_in')
                ->count()
            : 0;

        $reservationDeposits = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->sum('deposit_received')
            : 0;

        $folioBalances = Schema::hasTable('guest_folios')
            ? GuestFolio::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->sum('balance')
            : 0;

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        return view('hotel.dashboard.index', compact(
            'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'dirtyRooms', 'maintenanceRooms',
            'todayArrivals', 'todayDepartures', 'inHouseGuests', 'reservationDeposits', 'folioBalances', 'occupancyRate'
        ));
    }

    public function frontDesk(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();
        $today = now()->toDateString();

        $arrivals = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->whereDate('arrival_date', $today)
                ->orderBy('arrival_date')
                ->limit(20)
                ->get()
            : collect();

        $departures = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->whereDate('departure_date', $today)
                ->orderBy('departure_date')
                ->limit(20)
                ->get()
            : collect();

        $inHouse = Schema::hasTable('stays')
            ? Stay::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->where('status', 'checked_in')
                ->latest('id')
                ->limit(20)
                ->get()
            : collect();

        $availableCount = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'available')
            ->count();

        $occupiedCount = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'occupied')
            ->count();

        $reservedCount = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('operational_status', 'reserved')
            ->count();

        $dirtyCount = HotelRoom::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('housekeeping_status', 'dirty')
            ->count();

        return view('hotel.frontdesk.index', compact(
            'arrivals', 'departures', 'inHouse', 'availableCount', 'occupiedCount', 'reservedCount', 'dirtyCount'
        ));
    }

    public function inHouse(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $stays = Schema::hasTable('stays')
            ? Stay::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->where('status', 'checked_in')
                ->latest('id')
                ->paginate(20)
            : collect();

        return view('hotel.in_house.index', compact('stays'));
    }

    public function guests(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;

        $guestIds = collect();
        if (Schema::hasTable('reservations')) {
            $guestIds = $guestIds->merge(Reservation::where('company_id', $companyId)->whereNotNull('customer_id')->pluck('customer_id'));
        }
        if (Schema::hasTable('stays')) {
            $guestIds = $guestIds->merge(Stay::where('company_id', $companyId)->whereNotNull('customer_id')->pluck('customer_id'));
        }

        $guestIds = $guestIds->unique()->values();

        $guests = $guestIds->isEmpty()
            ? collect()
            : \App\Models\Customer::whereIn('id', $guestIds)->paginate(20);

        return view('hotel.guests.index', compact('guests'));
    }

    public function deposits(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $deposits = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->where('deposit_received', '>', 0)
                ->latest('id')
                ->paginate(20)
            : collect();

        return view('hotel.deposits.index', compact('deposits'));
    }

    public function settings(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $property = HotelProperty::where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->first();

        return view('hotel.settings.index', compact('property'));
    }

    private function currentPropertyId(): ?int
    {
        $companyId = (int) auth()->user()->company_id;
        $branchId = auth()->user()->branch_id;

        $property = HotelProperty::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        return $property?->id;
    }
}
