<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Support\HotelAccess;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $panel = (string) $request->query('panel', 'overview');
        $panels = [
            'overview' => 'Hotel Dashboard',
            'tenants' => 'Hotel Tenants',
            'properties' => 'Properties',
            'rooms' => 'Front Desk / Room Board',
            'room_calendar' => 'Room Calendar',
            'room_types' => 'Room Types',
            'reservations' => 'Reservations',
            'availability' => 'Availability Search',
            'check_in' => 'Check-In',
            'stays' => 'Current Stays / In-House',
            'checkout' => 'Checkout',
            'guests' => 'Guest Profiles',
            'folios' => 'Guest Folios',
            'deposits' => 'Deposits / Payments',
            'housekeeping' => 'Housekeeping',
            'maintenance' => 'Maintenance',
            'service_restaurant' => 'Restaurant / POS',
            'service_bar' => 'Bar',
            'service_gym' => 'Gym',
            'service_spa' => 'Spa',
            'service_minibar' => 'Minibar',
            'service_laundry' => 'Laundry',
            'service_room_service' => 'Room Service',
            'services' => 'Service Centers',
            'night_audits' => 'Night Audits',
            'reports' => 'Hotel Reports',
            'settings' => 'Hotel Settings / Feature Status',
        ];
        if (!array_key_exists($panel, $panels)) {
            $panel = 'overview';
        }
        $hotelCompanyIds = HotelAccess::hotelCompanyIds();
        $selectedCompanyId = $request->filled('company_id') ? (int) $request->query('company_id') : null;

        $companiesQuery = Company::query();
        if (!empty($hotelCompanyIds)) {
            $companiesQuery->whereIn('id', $hotelCompanyIds);
        } else {
            $companiesQuery->whereRaw('1 = 0');
        }
        $hotelCompanies = $companiesQuery->orderBy('name')->get(['id', 'name']);

        $totalHotelTenants = $hotelCompanies->count();

        $activeHotelSubscriptions = Subscription::whereNotNull('company_id')
            ->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['paid'])
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['active'])
            ->when(!empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
            ->count();

        $totalProperties = HotelProperty::when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))->count();
        $totalRooms = HotelRoom::when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))->count();
        $availableRooms = HotelRoom::when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))->where('operational_status', 'available')->count();
        $occupiedRooms = HotelRoom::when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))->where('operational_status', 'occupied')->count();
        $reservedRooms = HotelRoom::when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))->where('operational_status', 'reserved')->count();

        $todayReservations = 0;
        if (Schema::hasTable('reservations')) {
            $today = now()->toDateString();
            $todayReservations = \DB::table('reservations')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereDate('arrival_date', '<=', $today)
                ->whereDate('departure_date', '>=', $today)
                ->count();
        }

        $currentInHouseGuests = 0;
        if (Schema::hasTable('stays')) {
            $currentInHouseGuests = \DB::table('stays')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->where('status', 'checked_in')
                ->count();
        }

        $hotelRevenueToday = 0;
        if (Schema::hasTable('hotel_transactions')) {
            $hotelRevenueToday = \DB::table('hotel_transactions')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
        } elseif (Schema::hasTable('folio_items')) {
            $hotelRevenueToday = \DB::table('folio_items')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->where('type', 'charge')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
        }

        $hotelRevenueThisMonth = 0;
        if (Schema::hasTable('hotel_transactions')) {
            $hotelRevenueThisMonth = \DB::table('hotel_transactions')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
        } elseif (Schema::hasTable('folio_items')) {
            $hotelRevenueThisMonth = \DB::table('folio_items')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->where('type', 'charge')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        }

        $outstandingReceivables = 0;
        if (Schema::hasTable('guest_folios')) {
            $outstandingReceivables = \DB::table('guest_folios')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->where('balance', '>', 0)
                ->sum('balance');
        }

        $servicePanelMap = [
            'service_restaurant' => 'restaurant',
            'service_bar' => 'bar',
            'service_gym' => 'gym',
            'service_spa' => 'spa',
            'service_minibar' => 'minibar',
            'service_laundry' => 'laundry',
            'service_room_service' => 'room_service',
        ];
        $selectedServiceCenter = $servicePanelMap[$panel] ?? (string) $request->query('service', 'all');
        $panelData = $this->panelData($panel, $selectedCompanyId, $hotelCompanyIds, $selectedServiceCenter);

        $hotelDemoSeedPresent = Schema::hasTable('hotel_properties')
            && Schema::hasColumn('hotel_properties', 'code')
            && \DB::table('hotel_properties')->where('code', 'like', 'SPB-DEMO-%')->exists();

        $serviceCenters = [
            'all' => ['label' => 'All Services', 'codes' => []],
            'restaurant' => ['label' => 'Restaurant', 'codes' => ['RESTAURANT', 'FOOD', 'POS']],
            'bar' => ['label' => 'Bar & Lounge', 'codes' => ['BAR']],
            'spa' => ['label' => 'Spa & Wellness', 'codes' => ['SPA', 'WELLNESS']],
            'gym' => ['label' => 'Gym & Fitness', 'codes' => ['GYM', 'FITNESS']],
            'room_service' => ['label' => 'Room Service', 'codes' => ['ROOM_SERVICE']],
            'minibar' => ['label' => 'Minibar', 'codes' => ['MINIBAR']],
            'laundry' => ['label' => 'Laundry', 'codes' => ['LAUNDRY']],
            'conference' => ['label' => 'Conference & Events', 'codes' => ['CONFERENCE', 'EVENTS', 'BANQUET']],
        ];

        return view('SuperAdmin.hotels.overview', compact(
            'totalHotelTenants', 'activeHotelSubscriptions', 'totalProperties', 'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'todayReservations', 'currentInHouseGuests', 'hotelRevenueToday', 'hotelRevenueThisMonth', 'outstandingReceivables', 'panel', 'panels', 'panelData', 'hotelCompanies', 'selectedCompanyId', 'serviceCenters', 'selectedServiceCenter', 'hotelDemoSeedPresent'
        ));
    }

    private function panelData(string $panel, ?int $companyId, array $hotelCompanyIds, string $selectedServiceCenter = 'all')
    {
        if (in_array($panel, ['reservations', 'room_calendar', 'availability', 'check_in'], true) && Schema::hasTable('reservations')) {
            return \DB::table('reservations')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if (in_array($panel, ['stays', 'checkout'], true) && Schema::hasTable('stays')) {
            return \DB::table('stays')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'folios' && Schema::hasTable('guest_folios')) {
            return \DB::table('guest_folios')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'deposits' && Schema::hasTable('reservations')) {
            return \DB::table('reservations')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'tenants' && Schema::hasTable('companies')) {
            return Company::query()
                ->when(!empty($hotelCompanyIds), fn($q) => $q->whereIn('id', $hotelCompanyIds))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'properties' && Schema::hasTable('hotel_properties')) {
            return \DB::table('hotel_properties')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'rooms' && Schema::hasTable('hotel_rooms')) {
            return \DB::table('hotel_rooms')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'room_types' && Schema::hasTable('hotel_room_types')) {
            return \DB::table('hotel_room_types')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'guests' && Schema::hasTable('customers')) {
            $ids = collect();
            if (Schema::hasTable('reservations')) {
                $ids = $ids->merge(\DB::table('reservations')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->whereNotNull('customer_id')->pluck('customer_id'));
            }
            if (Schema::hasTable('stays')) {
                $ids = $ids->merge(\DB::table('stays')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->whereNotNull('customer_id')->pluck('customer_id'));
            }

            $ids = $ids->unique()->values();
            if ($ids->isEmpty()) {
                return collect();
            }

            return \DB::table('customers')->whereIn('id', $ids)->orderByDesc('id')->paginate(20)->withQueryString();
        }

        if ($panel === 'services' || str_starts_with($panel, 'service_')) {
            $serviceCodes = [
                'restaurant' => ['RESTAURANT', 'FOOD', 'POS'],
                'bar' => ['BAR'],
                'spa' => ['SPA', 'WELLNESS'],
                'gym' => ['GYM', 'FITNESS'],
                'room_service' => ['ROOM_SERVICE'],
                'minibar' => ['MINIBAR'],
                'laundry' => ['LAUNDRY'],
                'conference' => ['CONFERENCE', 'EVENTS', 'BANQUET'],
            ];

            if (Schema::hasTable('folio_items')) {
                return \DB::table('folio_items')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->when(!empty($serviceCodes[$selectedServiceCenter] ?? []), fn($q) => $q->whereIn('service_code', $serviceCodes[$selectedServiceCenter]))
                    ->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge'])
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }

            if (Schema::hasTable('hotel_transactions')) {
                return \DB::table('hotel_transactions')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }

            return collect();
        }

        if ($panel === 'housekeeping' && Schema::hasTable('hotel_housekeeping_tasks')) {
            return \DB::table('hotel_housekeeping_tasks')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'maintenance') {
            $maintenanceTable = Schema::hasTable('hotel_maintenance_tickets')
                ? 'hotel_maintenance_tickets'
                : (Schema::hasTable('hotel_maintenance_requests') ? 'hotel_maintenance_requests' : null);

            if ($maintenanceTable) {
                return \DB::table($maintenanceTable)
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }
        }

        if ($panel === 'night_audits' && Schema::hasTable('hotel_night_audits')) {
            return \DB::table('hotel_night_audits')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'reports') {
            if (Schema::hasTable('hotel_transactions')) {
                return \DB::table('hotel_transactions')
                    ->selectRaw('company_id, DATE(created_at) as business_date, SUM(amount) as total_amount, COUNT(*) as tx_count')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->groupBy('company_id', 'business_date')
                    ->orderByDesc('business_date')
                    ->paginate(20)
                    ->withQueryString();
            }

            if (Schema::hasTable('folio_items')) {
                return \DB::table('folio_items')
                    ->selectRaw('company_id, DATE(service_date) as business_date, SUM(amount) as total_amount, COUNT(*) as tx_count')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge'])
                    ->groupBy('company_id', 'business_date')
                    ->orderByDesc('business_date')
                    ->paginate(20)
                    ->withQueryString();
            }
        }

        if ($panel === 'settings') {
            $rows = collect([
                (object) ['setting' => 'hotel_properties_table', 'status' => Schema::hasTable('hotel_properties') ? 'available' : 'missing'],
                (object) ['setting' => 'hotel_rooms_table', 'status' => Schema::hasTable('hotel_rooms') ? 'available' : 'missing'],
                (object) ['setting' => 'hotel_room_types_table', 'status' => Schema::hasTable('hotel_room_types') ? 'available' : 'missing'],
                (object) ['setting' => 'reservations_table', 'status' => Schema::hasTable('reservations') ? 'available' : 'missing'],
                (object) ['setting' => 'stays_table', 'status' => Schema::hasTable('stays') ? 'available' : 'missing'],
                (object) ['setting' => 'guest_folios_table', 'status' => Schema::hasTable('guest_folios') ? 'available' : 'missing'],
            ]);

            return $rows;
        }

        return collect();
    }
}
