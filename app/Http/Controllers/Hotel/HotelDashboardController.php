<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HotelDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId($request);
        $property = $propertyId ? HotelProperty::query()->find($propertyId) : null;
        $properties = HotelProperty::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        [$fromDate, $toDate, $rangeKey] = $this->resolveRange($request);
        $daysInRange = max(1, Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1);

        $totalRoomsQuery = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId));

        $totalRooms = (clone $totalRoomsQuery)->count();
        $availableRooms = (clone $totalRoomsQuery)->where('operational_status', 'available')->count();
        $occupiedRooms = (clone $totalRoomsQuery)->where('operational_status', 'occupied')->count();
        $reservedRooms = (clone $totalRoomsQuery)->where('operational_status', 'reserved')->count();
        $dirtyRooms = (clone $totalRoomsQuery)->where('housekeeping_status', 'dirty')->count();
        $cleaningRooms = (clone $totalRoomsQuery)->where('housekeeping_status', 'cleaning')->count();
        $maintenanceRooms = (clone $totalRoomsQuery)->where('operational_status', 'maintenance')->count();
        $outOfOrderRooms = (clone $totalRoomsQuery)->where('operational_status', 'out_of_order')->count();

        $today = now()->toDateString();
        $todayArrivals = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', $today)
            ->count();

        $todayDepartures = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('departure_date', $today)
            ->count();

        $inHouseGuests = Stay::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->count();

        $reservationDeposits = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->sum('deposit_received');

        $folioBalances = GuestFolio::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->sum('balance');

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        $chargesQuery = FolioItem::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge'])
            ->whereBetween('service_date', [$fromDate, $toDate]);

        $paymentsQuery = FolioItem::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('type', ['payment', 'deposit_applied'])
            ->whereBetween('service_date', [$fromDate, $toDate]);

        $todayRevenue = (clone $chargesQuery)
            ->whereDate('service_date', $today)
            ->sum('amount');

        $monthRevenue = (clone $chargesQuery)
            ->whereBetween('service_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        $roomRevenue = (clone $chargesQuery)
            ->where('service_code', 'ROOM_NIGHT')
            ->sum('amount');

        $revenueByDepartmentRows = (clone $chargesQuery)
            ->selectRaw('COALESCE(service_code, "OTHER") as service_code, SUM(amount) as total_amount')
            ->groupBy('service_code')
            ->orderByDesc('total_amount')
            ->get();

        $revenueByDepartment = [
            'ROOM_NIGHT' => 0.0,
            'RESTAURANT' => 0.0,
            'BAR' => 0.0,
            'SPA' => 0.0,
            'GYM' => 0.0,
            'TICKETING' => 0.0,
            'LAUNDRY' => 0.0,
            'MINIBAR' => 0.0,
            'OTHER' => 0.0,
        ];

        foreach ($revenueByDepartmentRows as $row) {
            $code = strtoupper((string) ($row->service_code ?? 'OTHER'));
            $amount = (float) ($row->total_amount ?? 0);

            if (array_key_exists($code, $revenueByDepartment)) {
                $revenueByDepartment[$code] += $amount;
                continue;
            }

            if ($code === 'WELLNESS') {
                $revenueByDepartment['SPA'] += $amount;
                continue;
            }

            if ($code === 'FITNESS') {
                $revenueByDepartment['GYM'] += $amount;
                continue;
            }

            if ($code === 'EVENT') {
                $revenueByDepartment['TICKETING'] += $amount;
                continue;
            }

            if (in_array($code, ['ROOM_SERVICE', 'OTHER_SERVICE', 'POS_CHARGE'], true)) {
                $revenueByDepartment['OTHER'] += $amount;
                continue;
            }

            $revenueByDepartment['OTHER'] += $amount;
        }

        $paymentMethodDistributionRows = (clone $paymentsQuery)
            ->selectRaw('COALESCE(service_code, "PAYMENT") as service_code, SUM(amount) as total_amount')
            ->groupBy('service_code')
            ->orderByDesc('total_amount')
            ->get();

        $reservationSourceRows = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereBetween('arrival_date', [$fromDate, $toDate])
            ->selectRaw('COALESCE(source, "direct") as source, COUNT(*) as total_count')
            ->groupBy('source')
            ->orderByDesc('total_count')
            ->limit(8)
            ->get();

        $roomTypePerformanceRows = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereBetween('arrival_date', [$fromDate, $toDate])
            ->leftJoin('hotel_room_types', 'reservations.room_type_id', '=', 'hotel_room_types.id')
            ->selectRaw('COALESCE(hotel_room_types.name, "Unassigned") as room_type_name, COUNT(reservations.id) as total_reservations, SUM(COALESCE(reservations.total,0)) as total_revenue')
            ->groupBy('room_type_name')
            ->orderByDesc('total_revenue')
            ->limit(8)
            ->get();

        $occupancyTrend = [];
        $adrTrend = [];
        $revparTrend = [];
        $revenueTrend = [];

        for ($day = Carbon::parse($fromDate)->startOfDay(); $day->lte(Carbon::parse($toDate)->endOfDay()); $day->addDay()) {
            $dateKey = $day->toDateString();
            $label = $day->format('d M');

            $occupiedCountForDay = Stay::query()
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->where('checkin_at', '<=', $day->copy()->endOfDay())
                ->where(function ($query) use ($day) {
                    $query->whereNull('actual_checkout_at')
                        ->orWhere('actual_checkout_at', '>=', $day->copy()->startOfDay());
                })
                ->count();

            $occupancyPct = $totalRooms > 0 ? round(($occupiedCountForDay / $totalRooms) * 100, 2) : 0;

            $roomRevenueDay = FolioItem::query()
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->whereDate('service_date', $dateKey)
                ->where('service_code', 'ROOM_NIGHT')
                ->sum('amount');

            $otherRevenueDay = FolioItem::query()
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->whereDate('service_date', $dateKey)
                ->whereIn('type', ['charge', 'service', 'pos_charge'])
                ->where('service_code', '!=', 'ROOM_NIGHT')
                ->sum('amount');

            $paymentsDay = FolioItem::query()
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->whereDate('service_date', $dateKey)
                ->whereIn('type', ['payment', 'deposit_applied'])
                ->sum('amount');

            $adrDay = $occupiedCountForDay > 0 ? round(((float) $roomRevenueDay) / $occupiedCountForDay, 2) : 0;
            $revparDay = $totalRooms > 0 ? round(((float) $roomRevenueDay) / $totalRooms, 2) : 0;

            $occupancyTrend[] = ['label' => $label, 'value' => $occupancyPct];
            $adrTrend[] = ['label' => $label, 'value' => $adrDay];
            $revparTrend[] = ['label' => $label, 'value' => $revparDay];
            $revenueTrend[] = [
                'label' => $label,
                'room' => (float) $roomRevenueDay,
                'other' => (float) $otherRevenueDay,
                'payments' => (float) $paymentsDay,
            ];
        }

        $occupiedRoomNights = array_sum(array_map(fn ($point) => (float) $point['value'], $occupancyTrend));
        $occupiedRoomNights = $totalRooms > 0 ? ($occupiedRoomNights / 100) * $totalRooms : 0;
        $adr = $occupiedRoomNights > 0 ? round(((float) $roomRevenue) / $occupiedRoomNights, 2) : 0;
        $revpar = ($totalRooms * $daysInRange) > 0 ? round(((float) $roomRevenue) / ($totalRooms * $daysInRange), 2) : 0;

        $arrivalsPanel = Reservation::query()
            ->with(['customer', 'room', 'roomType'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', '>=', $today)
            ->orderBy('arrival_date')
            ->limit(12)
            ->get();

        $departuresPanel = Reservation::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('departure_date', '>=', $today)
            ->orderBy('departure_date')
            ->limit(12)
            ->get();

        $todayActivity = [
            'checkins_completed' => Stay::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('checkin_at', $today)->count(),
            'checkouts_completed' => Stay::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('actual_checkout_at', $today)->count(),
            'new_reservations' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('created_at', $today)->count(),
            'cancellations' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'cancelled')->whereDate('updated_at', $today)->count(),
            'no_shows' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'no_show')->whereDate('updated_at', $today)->count(),
            'payments_received' => (clone $paymentsQuery)->whereDate('service_date', $today)->sum('amount'),
            'room_changes' => 0,
            'maintenance_tickets' => Schema::hasTable('hotel_maintenance_tickets')
                ? DB::table('hotel_maintenance_tickets')->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('created_at', $today)->count()
                : 0,
        ];

        $arrivalsNeedRoomAssignment = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', $today)
            ->whereNull('room_id')
            ->count();

        $dirtyArrivalRooms = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', $today)
            ->whereHas('room', fn ($query) => $query->where('housekeeping_status', 'dirty'))
            ->count();

        $maintenanceConflicts = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', '>=', $today)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereHas('room', fn ($query) => $query->whereIn('operational_status', ['maintenance', 'out_of_order']))
            ->count();

        $outstandingDepartures = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('departure_date', $today)
            ->where('balance', '>', 0)
            ->count();

        $nightAuditPending = Schema::hasTable('hotel_night_audits')
            ? DB::table('hotel_night_audits')
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->whereDate('audit_date', $today)
                ->doesntExist()
            : false;

        $managementAlerts = collect([
            ['label' => 'Arrivals need room assignment', 'count' => $arrivalsNeedRoomAssignment, 'route' => route('hotel.rooms.calendar'), 'tone' => 'warning'],
            ['label' => 'Arriving rooms still dirty', 'count' => $dirtyArrivalRooms, 'route' => route('hotel.housekeeping.index'), 'tone' => 'danger'],
            ['label' => 'Maintenance conflict with reservation', 'count' => $maintenanceConflicts, 'route' => route('hotel.maintenance.index'), 'tone' => 'danger'],
            ['label' => 'Departures with outstanding balance', 'count' => $outstandingDepartures, 'route' => route('hotel.folios.index'), 'tone' => 'warning'],
            ['label' => 'Night audit pending', 'count' => $nightAuditPending ? 1 : 0, 'route' => route('hotel.night_audit.index'), 'tone' => 'info'],
        ])->filter(fn ($alert) => (int) $alert['count'] > 0)->values();

        $roomStatusBreakdown = [
            ['key' => 'available', 'label' => 'Available', 'count' => $availableRooms, 'route' => route('hotel.rooms.index', ['status' => 'available'])],
            ['key' => 'occupied', 'label' => 'Occupied', 'count' => $occupiedRooms, 'route' => route('hotel.rooms.index', ['status' => 'occupied'])],
            ['key' => 'reserved', 'label' => 'Reserved', 'count' => $reservedRooms, 'route' => route('hotel.rooms.index', ['status' => 'reserved'])],
            ['key' => 'dirty', 'label' => 'Dirty', 'count' => $dirtyRooms, 'route' => route('hotel.housekeeping.index')],
            ['key' => 'cleaning', 'label' => 'Cleaning', 'count' => $cleaningRooms, 'route' => route('hotel.housekeeping.index')],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'count' => $maintenanceRooms, 'route' => route('hotel.maintenance.index')],
            ['key' => 'out_of_order', 'label' => 'Out of Order', 'count' => $outOfOrderRooms, 'route' => route('hotel.maintenance.index')],
        ];

        return view('hotel.dashboard.index', compact(
            'property',
            'properties',
            'rangeKey',
            'fromDate',
            'toDate',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'reservedRooms',
            'dirtyRooms',
            'cleaningRooms',
            'maintenanceRooms',
            'outOfOrderRooms',
            'todayArrivals',
            'todayDepartures',
            'inHouseGuests',
            'todayRevenue',
            'monthRevenue',
            'folioBalances',
            'reservationDeposits',
            'occupancyRate',
            'adr',
            'revpar',
            'revenueByDepartment',
            'paymentMethodDistributionRows',
            'reservationSourceRows',
            'roomTypePerformanceRows',
            'occupancyTrend',
            'adrTrend',
            'revparTrend',
            'revenueTrend',
            'todayActivity',
            'arrivalsPanel',
            'departuresPanel',
            'managementAlerts',
            'roomStatusBreakdown',
            'propertyId'
        ));
    }

    public function frontDesk(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId($request);
        $today = now()->toDateString();

        $search = trim((string) $request->query('q', ''));

        $properties = HotelProperty::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $floor = trim((string) $request->query('floor', ''));
        $roomTypeId = (int) $request->query('room_type_id', 0);
        $status = trim((string) $request->query('status', ''));
        $viewMode = (string) $request->query('view', 'grid');

        $roomTypes = HotelRoomType::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderBy('name')
            ->get();

        $floors = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('floor')
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor');

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($floor !== '', fn ($query) => $query->where('floor', $floor))
            ->when($roomTypeId > 0, fn ($query) => $query->where('room_type_id', $roomTypeId))
            ->when($status !== '', fn ($query) => $query->where(function ($sub) use ($status) {
                $sub->where('operational_status', $status)
                    ->orWhere('housekeeping_status', $status);
            }))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('room_number', 'like', '%' . $search . '%')
                        ->orWhere('operational_status', 'like', '%' . $search . '%')
                        ->orWhere('housekeeping_status', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('room_number')
            ->limit(60)
            ->get();

        $activeStaysByRoom = Stay::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->get()
            ->keyBy('room_id');

        $roomReservationsToday = Reservation::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereDate('arrival_date', '<=', $today)
            ->whereDate('departure_date', '>=', $today)
            ->whereIn('room_id', $rooms->pluck('id'))
            ->orderBy('arrival_date')
            ->get()
            ->groupBy('room_id');

        $arrivals = Reservation::query()
            ->with(['customer', 'room', 'roomType'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', $today)
            ->orderBy('arrival_time')
            ->orderBy('arrival_date')
            ->limit(20)
            ->get();

        $arrivalWithoutRoomCount = $arrivals->filter(fn ($reservation) => !$reservation->room_id)->count();

        $departures = Reservation::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('departure_date', $today)
            ->orderBy('departure_time')
            ->orderBy('departure_date')
            ->limit(20)
            ->get();

        $departureRoomIds = $departures->pluck('room_id')->filter()->values();
        $departuresStayMap = Stay::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereIn('room_id', $departureRoomIds)
            ->get()
            ->keyBy('room_id');

        $departureFolioMap = GuestFolio::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('stay_id', $departuresStayMap->pluck('id'))
            ->get()
            ->keyBy('stay_id');

        $departures = $departures->map(function ($reservation) use ($departuresStayMap, $departureFolioMap) {
            $stay = $reservation->room_id ? $departuresStayMap->get((int) $reservation->room_id) : null;
            $folio = $stay ? $departureFolioMap->get((int) $stay->id) : null;
            $reservation->frontdesk_folio_balance = (float) ($folio->balance ?? 0);
            $reservation->frontdesk_payment_status = (float) ($folio->balance ?? 0) > 0 ? 'outstanding' : 'cleared';
            return $reservation;
        });

        $inHouse = Stay::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->latest('id')
            ->limit(20)
            ->get();

        $inHouseFolioMap = GuestFolio::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('stay_id', $inHouse->pluck('id'))
            ->get()
            ->keyBy('stay_id');

        $inHouse = $inHouse->map(function ($stay) use ($inHouseFolioMap) {
            $folio = $inHouseFolioMap->get((int) $stay->id);
            $stay->frontdesk_balance = (float) ($folio->balance ?? 0);
            return $stay;
        });

        $pendingCheckins = Reservation::query()
            ->with(['customer', 'roomType'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereDate('arrival_date', '<=', $today)
            ->orderBy('arrival_date')
            ->limit(20)
            ->get();

        $maintenanceWithFutureReservations = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', '>=', $today)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereHas('room', function ($query) {
                $query->whereIn('operational_status', ['maintenance', 'out_of_order']);
            })
            ->count();

        $dirtyArrivalRoomsCount = $arrivals
            ->filter(function ($reservation) {
                return $reservation->room && (string) $reservation->room->housekeeping_status === 'dirty';
            })
            ->count();

        $lowDepositArrivalsCount = $arrivals
            ->filter(function ($reservation) {
                return (float) ($reservation->deposit_required ?? 0) > (float) ($reservation->deposit_received ?? 0);
            })
            ->count();

        $overdueCheckoutCount = Stay::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereNotNull('expected_checkout_at')
            ->where('expected_checkout_at', '<', now())
            ->count();

        $unpostedNightlyChargesCount = Stay::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereDoesntHave('reservation')
            ->count();

        $departureOutstandingCount = $departures->filter(fn ($reservation) => (float) ($reservation->frontdesk_folio_balance ?? 0) > 0)->count();

        $alerts = collect([
            ['severity' => 'warning', 'label' => 'Arrivals without assigned room', 'count' => $arrivalWithoutRoomCount],
            ['severity' => 'danger', 'label' => 'Departures with outstanding balance', 'count' => $departureOutstandingCount],
            ['severity' => 'warning', 'label' => 'Dirty rooms needed for arrivals', 'count' => $dirtyArrivalRoomsCount],
            ['severity' => 'danger', 'label' => 'Maintenance rooms with future reservations', 'count' => $maintenanceWithFutureReservations],
            ['severity' => 'warning', 'label' => 'Reservations below required deposit', 'count' => $lowDepositArrivalsCount],
            ['severity' => 'danger', 'label' => 'Overdue checkouts', 'count' => $overdueCheckoutCount],
            ['severity' => 'info', 'label' => 'Potential unposted nightly charges', 'count' => $unpostedNightlyChargesCount],
        ])->filter(fn ($alert) => (int) $alert['count'] > 0)->values();

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

        $priorityCleaning = HotelHousekeepingTask::query()
            ->with(['room.type', 'stay.customer'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn($query) => $query->where('property_id', $propertyId))
            ->where('priority', 'high')
            ->whereIn('status', ['open', 'assigned', 'cleaning'])
            ->latest('id')
            ->limit(8)
            ->get();

        $waitingForRoom = Reservation::query()
            ->with(['customer', 'roomType'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', $today)
            ->whereNull('room_id')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('hotel.frontdesk.index', compact(
            'rooms', 'search', 'arrivals', 'departures', 'inHouse', 'pendingCheckins',
            'availableCount', 'occupiedCount', 'reservedCount', 'dirtyCount',
            'alerts', 'activeStaysByRoom', 'roomReservationsToday', 'properties', 'propertyId',
            'floors', 'floor', 'roomTypes', 'roomTypeId', 'status', 'viewMode', 'priorityCleaning', 'waitingForRoom'
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
                ->with(['customer', 'room'])
                ->latest('id')
                ->paginate(20)
            : collect();

        if ($stays instanceof \Illuminate\Pagination\LengthAwarePaginator && $stays->count() > 0) {
            $stays->getCollection()->transform(function ($stay) {
                $folio = GuestFolio::query()->where('stay_id', $stay->id)->latest('id')->first();
                $stay->folio_charges = (float) ($folio->total_charges ?? 0);
                $stay->folio_payments = (float) ($folio->total_payments ?? 0);
                $stay->folio_balance = (float) ($folio->balance ?? 0);
                return $stay;
            });
        }

        $floors = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('floor')
            ->distinct()
            ->pluck('floor');

        return view('hotel.in_house.index', compact('stays', 'floors'));
    }

    public function guests(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;

        $propertyId = $this->currentPropertyId();
        $guestIds = collect();
        if (Schema::hasTable('reservations')) {
            $guestIds = $guestIds->merge(
                Reservation::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->whereNotNull('customer_id')
                    ->pluck('customer_id')
            );
        }
        if (Schema::hasTable('stays')) {
            $guestIds = $guestIds->merge(
                Stay::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->whereNotNull('customer_id')
                    ->pluck('customer_id')
            );
        }

        $guestIds = $guestIds->unique()->values();

        $guests = $guestIds->isEmpty()
            ? collect()
            : Customer::query()->whereIn('id', $guestIds)->paginate(20);

        if ($guests instanceof \Illuminate\Pagination\LengthAwarePaginator && $guests->count() > 0) {
            $guests->getCollection()->transform(function ($guest) use ($companyId, $propertyId) {
                $guest->total_stays = Stay::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->where('customer_id', $guest->id)
                    ->count();

                $guest->last_stay = Stay::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->where('customer_id', $guest->id)
                    ->max('checkin_at');

                $guest->total_spend = FolioItem::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge'])
                    ->whereIn('folio_id', GuestFolio::query()->where('customer_id', $guest->id)->pluck('id'))
                    ->sum('amount');

                $guest->outstanding_balance = GuestFolio::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->where('customer_id', $guest->id)
                    ->sum('balance');

                $guest->latest_stay_id = Stay::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->where('customer_id', $guest->id)
                    ->latest('id')
                    ->value('id');

                $guest->open_folio_id = GuestFolio::query()
                    ->where('company_id', $companyId)
                    ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                    ->where('customer_id', $guest->id)
                    ->whereIn('status', ['open', 'city_ledger'])
                    ->latest('id')
                    ->value('id');

                return $guest;
            });
        }

        return view('hotel.guests.index', compact('guests'));
    }

    public function updateGuestNote(Request $request, Customer $customer)
    {
        abort_unless((int) $customer->company_id === (int) auth()->user()->company_id, 404);

        if (!Schema::hasColumn('customers', 'notes')) {
            return back()->withErrors(['error' => 'Guest notes column is not available in this installation.']);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer->forceFill(['notes' => $data['notes'] ?? null])->save();

        return back()->with('success', 'Guest note updated.');
    }

    public function deposits(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $deposits = Schema::hasTable('reservations')
            ? Reservation::where('company_id', $companyId)
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->where('deposit_received', '>', 0)
                ->with('customer')
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

    private function currentPropertyId(?Request $request = null): ?int
    {
        $companyId = (int) auth()->user()->company_id;

        if ($request && $request->has('property_id')) {
            $requested = (string) $request->query('property_id');
            if ($requested === 'all' || $requested === '') {
                session(['hotel_property_id' => 'all']);
                return null;
            }

            $candidate = HotelProperty::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $requested)
                ->value('id');

            if ($candidate) {
                session(['hotel_property_id' => (int) $candidate]);
                return (int) $candidate;
            }
        }

        $sessionProperty = session('hotel_property_id');
        if ($sessionProperty === 'all') {
            return null;
        }
        if ((int) $sessionProperty > 0) {
            $exists = HotelProperty::query()->where('company_id', $companyId)->where('id', (int) $sessionProperty)->exists();
            if ($exists) {
                return (int) $sessionProperty;
            }
        }

        $branchId = auth()->user()->branch_id;

        $property = HotelProperty::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        return $property?->id;
    }

    private function resolveRange(Request $request): array
    {
        $rangeKey = (string) $request->query('range', 'today');
        $today = now()->startOfDay();

        return match ($rangeKey) {
            'yesterday' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString(), 'yesterday'],
            'last_7_days' => [$today->copy()->subDays(6)->toDateString(), $today->copy()->toDateString(), 'last_7_days'],
            'last_30_days' => [$today->copy()->subDays(29)->toDateString(), $today->copy()->toDateString(), 'last_30_days'],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString(), 'this_month'],
            'custom' => [
                (string) ($request->query('from_date') ?: $today->copy()->subDays(6)->toDateString()),
                (string) ($request->query('to_date') ?: $today->copy()->toDateString()),
                'custom',
            ],
            default => [$today->toDateString(), $today->toDateString(), 'today'],
        };
    }
}
