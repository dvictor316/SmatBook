<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HotelWorkspaceController extends Controller
{
    public function roomStatus(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $status = (string) $request->query('status', '');
        $housekeeping = (string) $request->query('housekeeping', '');

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($status !== '', fn ($query) => $query->where('operational_status', $status))
            ->when($housekeeping !== '', fn ($query) => $query->where('housekeeping_status', $housekeeping))
            ->orderBy('room_number')
            ->paginate(36)
            ->withQueryString();

        $statusTotals = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->selectRaw('operational_status, COUNT(*) as total_count')
            ->groupBy('operational_status')
            ->pluck('total_count', 'operational_status');

        return view('hotel.rooms.status', compact('rooms', 'status', 'housekeeping', 'statusTotals'));
    }

    public function roomCalendar(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $start = Carbon::parse($request->query('start_date', now()->toDateString()))->startOfDay();
        $days = max(7, min(30, (int) $request->query('days', 14)));
        $end = $start->copy()->addDays($days - 1)->endOfDay();

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderBy('room_number')
            ->limit(40)
            ->get();

        $reservations = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('room_id')
            ->whereDate('arrival_date', '<=', $end->toDateString())
            ->whereDate('departure_date', '>=', $start->toDateString())
            ->get();

        $stays = Stay::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('room_id')
            ->where('checkin_at', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('actual_checkout_at')
                    ->orWhere('actual_checkout_at', '>=', $start);
            })
            ->get();

        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dates[] = $cursor->copy();
        }

        return view('hotel.rooms.calendar', compact('rooms', 'reservations', 'stays', 'dates', 'start', 'days'));
    }

    public function laundry(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $orders = FolioItem::query()
            ->with('folio.customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('service_code', 'LAUNDRY')
            ->latest('service_date')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.operations.laundry', compact('orders'));
    }

    public function minibar(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $entries = FolioItem::query()
            ->with('folio.customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('service_code', 'MINIBAR')
            ->latest('service_date')
            ->paginate(20)
            ->withQueryString();

        $activeStays = Stay::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->orderBy('id', 'desc')
            ->limit(40)
            ->get();

        return view('hotel.operations.minibar', compact('entries', 'activeStays'));
    }

    public function roomService(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $items = FolioItem::query()
            ->with('folio.customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('service_code', ['ROOM_SERVICE', 'RESTAURANT'])
            ->latest('service_date')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.operations.room_service', compact('items'));
    }

    public function conference(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $bookings = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereRaw('LOWER(COALESCE(source, "")) in (?, ?)', ['conference', 'event'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.operations.conference', compact('bookings'));
    }

    public function corporateAccounts()
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $cityLedgers = GuestFolio::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'city_ledger')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.business.corporate_accounts', compact('cityLedgers'));
    }

    public function groupBookings()
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $groups = Reservation::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('adults', '>=', 4)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.business.group_bookings', compact('groups'));
    }

    public function bookingSources()
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $sources = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->selectRaw('COALESCE(source, "direct") as booking_source, COUNT(*) as reservations_count, SUM(COALESCE(total,0)) as gross_value')
            ->groupBy('booking_source')
            ->orderByDesc('reservations_count')
            ->get();

        return view('hotel.business.booking_sources', compact('sources'));
    }

    public function reports()
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->propertyId();

        $kpis = [
            'arrivals_today' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('arrival_date', now()->toDateString())->count(),
            'departures_today' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('departure_date', now()->toDateString())->count(),
            'occupancy' => Stay::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'checked_in')->count(),
            'room_revenue_today' => FolioItem::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('service_date', now()->toDateString())->where('service_code', 'ROOM_NIGHT')->sum('amount'),
        ];

        return view('hotel.reports.index', compact('kpis'));
    }

    private function propertyId(): ?int
    {
        return HotelProperty::query()
            ->where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');
    }
}
