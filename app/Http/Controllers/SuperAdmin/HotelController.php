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
            'progress' => 'Upgrade Progress',
            'overview' => 'Hotel Dashboard',
            'tenants' => 'Hotel Tenants',
            'properties' => 'Properties',
            'rooms' => 'Front Desk / Room Board',
            'room_gallery' => 'Room Gallery',
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
            'service_ticketing' => 'Ticketing / Events',
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

        $hotelScope = function ($query) use ($selectedCompanyId, $hotelCompanyIds) {
            return $query
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->when(!$selectedCompanyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds));
        };

        $totalProperties = $hotelScope(HotelProperty::query())->count();
        $roomHasActiveColumn = $this->hasColumn('hotel_rooms', 'is_active');
        $roomScope = fn() => $hotelScope(HotelRoom::query())
            ->when($roomHasActiveColumn, fn($q) => $q->where('is_active', 1));
        $roomStatusCounts = $roomScope()
            ->selectRaw('COALESCE(operational_status, "available") as status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $totalRooms = (int) $roomStatusCounts->sum();
        $availableRooms = (int) ($roomStatusCounts['available'] ?? 0);
        $occupiedRooms = (int) ($roomStatusCounts['occupied'] ?? 0);
        $reservedRooms = (int) ($roomStatusCounts['reserved'] ?? 0);

        $todayReservations = 0;
        if ($this->hasTable('reservations')) {
            $today = now()->toDateString();
            $todayReservations = \DB::table('reservations')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereDate('arrival_date', '<=', $today)
                ->whereDate('departure_date', '>=', $today)
                ->count();
        }

        $currentInHouseGuests = 0;
        if ($this->hasTable('stays')) {
            $currentInHouseGuests = \DB::table('stays')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->where('status', 'checked_in')
                ->count();
        }

        $hotelRevenueToday = 0;
        if ($this->hasTable('hotel_transactions')) {
            $hotelRevenueToday = \DB::table('hotel_transactions')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
        } elseif ($this->hasTable('folio_items')) {
            $hotelRevenueToday = \DB::table('folio_items')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->where('type', 'charge')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
        }

        $hotelRevenueThisMonth = 0;
        if ($this->hasTable('hotel_transactions')) {
            $hotelRevenueThisMonth = \DB::table('hotel_transactions')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
        } elseif ($this->hasTable('folio_items')) {
            $hotelRevenueThisMonth = \DB::table('folio_items')
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->where('type', 'charge')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        }

        $outstandingReceivables = 0;
        if ($this->hasTable('guest_folios')) {
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
            'service_ticketing' => 'ticketing',
            'service_minibar' => 'minibar',
            'service_laundry' => 'laundry',
            'service_room_service' => 'room_service',
        ];
        $selectedServiceCenter = $servicePanelMap[$panel] ?? (string) $request->query('service', 'all');
        $panelData = $this->panelData($panel, $selectedCompanyId, $hotelCompanyIds, $selectedServiceCenter);

        $hotelDemoSeedPresent = $this->hasTable('hotel_properties')
            && $this->hasColumn('hotel_properties', 'code')
            && \DB::table('hotel_properties')->where('code', 'like', 'SPB-DEMO-%')->exists();

        $serviceCenters = [
            'all' => ['label' => 'All Services', 'codes' => []],
            'restaurant' => ['label' => 'Restaurant', 'codes' => ['RESTAURANT', 'FOOD', 'POS']],
            'bar' => ['label' => 'Bar & Lounge', 'codes' => ['BAR']],
            'spa' => ['label' => 'Spa & Wellness', 'codes' => ['SPA', 'WELLNESS']],
            'gym' => ['label' => 'Gym & Fitness', 'codes' => ['GYM', 'FITNESS']],
            'ticketing' => ['label' => 'Ticketing & Events', 'codes' => ['TICKETING', 'EVENT']],
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
        if ($panel === 'progress') {
            return collect([
                (object) ['area' => 'Tenant Hotel Dashboard', 'status' => 'completed', 'evidence' => 'Live KPIs, service-center quick actions, revenue breakdowns'],
                (object) ['area' => 'Super Admin Hotel Monitor', 'status' => 'completed', 'evidence' => 'Global sidebar entries, progress panel, tenant/property/room/reservation/folio views'],
                (object) ['area' => 'Room Media Gallery', 'status' => $this->hasTable('hotel_room_images') ? 'completed' : 'migration_pending', 'evidence' => 'Multiple room images, cover image, panorama flag, room show carousel'],
                (object) ['area' => 'Live Availability', 'status' => 'completed', 'evidence' => 'AJAX JSON endpoint for date/type availability and reservation room dropdown refresh'],
                (object) ['area' => 'Reservation Workflow', 'status' => 'completed', 'evidence' => 'Existing reservation create/show, availability guard against double booking'],
                (object) ['area' => 'Check-In / Check-Out', 'status' => 'completed', 'evidence' => 'Existing stay creation, room status update, folio settlement, housekeeping task creation'],
                (object) ['area' => 'Guest Folios', 'status' => 'completed', 'evidence' => 'Room/service charges, payments, running balance, accounting ledger posting'],
                (object) ['area' => 'Housekeeping', 'status' => 'completed', 'evidence' => 'Task board, clean/dirty transitions, checkout-clean automation'],
                (object) ['area' => 'Maintenance', 'status' => 'completed', 'evidence' => 'Ticket creation, room maintenance state, resolution workflow'],
                (object) ['area' => 'Bar / Spa / Gym / Ticketing', 'status' => 'completed', 'evidence' => 'Department service-center charge form posts to open guest folios'],
                (object) ['area' => 'Accounting Integration', 'status' => 'completed', 'evidence' => 'Folio charges/payments reuse LedgerService and receivables/revenue accounts'],
            ]);
        }

        if (in_array($panel, ['reservations', 'room_calendar', 'availability', 'check_in'], true) && $this->hasTable('reservations')) {
            return \DB::table('reservations')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if (in_array($panel, ['stays', 'checkout'], true) && $this->hasTable('stays')) {
            return \DB::table('stays')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'folios' && $this->hasTable('guest_folios')) {
            return \DB::table('guest_folios')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'deposits' && $this->hasTable('reservations')) {
            return \DB::table('reservations')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'tenants' && $this->hasTable('companies')) {
            return Company::query()
                ->when(!empty($hotelCompanyIds), fn($q) => $q->whereIn('id', $hotelCompanyIds))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'properties' && $this->hasTable('hotel_properties')) {
            return \DB::table('hotel_properties')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if (in_array($panel, ['rooms', 'room_gallery'], true) && $this->hasTable('hotel_rooms')) {
            return \DB::table('hotel_rooms')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->when($this->hasColumn('hotel_rooms', 'is_active'), fn($q) => $q->where('is_active', 1))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'room_types' && $this->hasTable('hotel_room_types')) {
            return \DB::table('hotel_room_types')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'guests' && $this->hasTable('customers')) {
            $ids = collect();
            if ($this->hasTable('reservations')) {
                $ids = $ids->merge(\DB::table('reservations')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->whereNotNull('customer_id')->pluck('customer_id'));
            }
            if ($this->hasTable('stays')) {
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
                'ticketing' => ['TICKETING', 'EVENT'],
                'room_service' => ['ROOM_SERVICE'],
                'minibar' => ['MINIBAR'],
                'laundry' => ['LAUNDRY'],
                'conference' => ['CONFERENCE', 'EVENTS', 'BANQUET'],
            ];

            if ($this->hasTable('folio_items')) {
                return \DB::table('folio_items')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->when(!empty($serviceCodes[$selectedServiceCenter] ?? []), fn($q) => $q->whereIn('service_code', $serviceCodes[$selectedServiceCenter]))
                    ->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge'])
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }

            if ($this->hasTable('hotel_transactions')) {
                return \DB::table('hotel_transactions')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }

            return collect();
        }

        if ($panel === 'housekeeping' && $this->hasTable('hotel_housekeeping_tasks')) {
            return \DB::table('hotel_housekeeping_tasks')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'maintenance') {
            $maintenanceTable = $this->hasTable('hotel_maintenance_tickets')
                ? 'hotel_maintenance_tickets'
                : ($this->hasTable('hotel_maintenance_requests') ? 'hotel_maintenance_requests' : null);

            if ($maintenanceTable) {
                return \DB::table($maintenanceTable)
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
            }
        }

        if ($panel === 'night_audits' && $this->hasTable('hotel_night_audits')) {
            return \DB::table('hotel_night_audits')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        if ($panel === 'reports') {
            if ($this->hasTable('hotel_transactions')) {
                return \DB::table('hotel_transactions')
                    ->selectRaw('company_id, DATE(created_at) as business_date, SUM(amount) as total_amount, COUNT(*) as tx_count')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->groupBy('company_id', 'business_date')
                    ->orderByDesc('business_date')
                    ->paginate(20)
                    ->withQueryString();
            }

            if ($this->hasTable('folio_items')) {
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
                (object) ['setting' => 'hotel_properties_table', 'status' => $this->hasTable('hotel_properties') ? 'available' : 'missing'],
                (object) ['setting' => 'hotel_rooms_table', 'status' => $this->hasTable('hotel_rooms') ? 'available' : 'missing'],
                (object) ['setting' => 'hotel_room_images_table', 'status' => $this->hasTable('hotel_room_images') ? 'available' : 'missing'],
                (object) ['setting' => 'hotel_room_types_table', 'status' => $this->hasTable('hotel_room_types') ? 'available' : 'missing'],
                (object) ['setting' => 'reservations_table', 'status' => $this->hasTable('reservations') ? 'available' : 'missing'],
                (object) ['setting' => 'stays_table', 'status' => $this->hasTable('stays') ? 'available' : 'missing'],
                (object) ['setting' => 'guest_folios_table', 'status' => $this->hasTable('guest_folios') ? 'available' : 'missing'],
            ]);

            return $rows;
        }

        return collect();
    }

    private function hasTable(string $table): bool
    {
        static $tables = [];

        if (!array_key_exists($table, $tables)) {
            $tables[$table] = Schema::hasTable($table);
        }

        return $tables[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $columns = [];
        $key = $table.'.'.$column;

        if (!array_key_exists($key, $columns)) {
            $columns[$key] = Schema::hasColumn($table, $column);
        }

        return $columns[$key];
    }
}
