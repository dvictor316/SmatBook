<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomImage;
use App\Models\HotelRoomType;
use App\Support\HotelAccess;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $maintenanceRooms = (int) ($roomStatusCounts['maintenance'] ?? 0);
        $outOfOrderRooms = (int) ($roomStatusCounts['out_of_order'] ?? 0);
        $dirtyRooms = $roomScope()->where('housekeeping_status', 'dirty')->count();

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
        $roomManagement = $this->roomManagementData($selectedCompanyId, $hotelCompanyIds);

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
            'totalHotelTenants', 'activeHotelSubscriptions', 'totalProperties', 'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'maintenanceRooms', 'outOfOrderRooms', 'dirtyRooms', 'todayReservations', 'currentInHouseGuests', 'hotelRevenueToday', 'hotelRevenueThisMonth', 'outstandingReceivables', 'panel', 'panels', 'panelData', 'hotelCompanies', 'selectedCompanyId', 'serviceCenters', 'selectedServiceCenter', 'hotelDemoSeedPresent', 'roomManagement'
        ));
    }

    public function storeRoom(Request $request)
    {
        $hotelCompanyIds = HotelAccess::hotelCompanyIds();
        $data = $this->validateRoomPayload($request, $hotelCompanyIds);

        if (HotelRoom::query()->where('property_id', $data['property_id'])->where('room_number', $data['room_number'])->exists()) {
            return back()->withErrors(['room_number' => 'Room number already exists for this property.'])->withInput();
        }

        $data['room_type_id'] = $this->resolveRoomType($request, $data);
        unset($data['new_room_type_name'], $data['new_room_type_base_rate'], $data['gallery_images']);
        $data['room_image'] = $request->hasFile('room_image') ? $request->file('room_image')->store('hotel/rooms', 'public') : null;
        $data['panorama_image'] = $request->hasFile('panorama_image') ? $request->file('panorama_image')->store('hotel/rooms/panoramas', 'public') : null;
        $data['is_active'] = $request->boolean('is_active', true);

        $room = HotelRoom::create($data);
        $this->syncLegacyMediaIntoGallery($room);
        $this->storeUploadedGalleryImages($request, $room);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id])
            ->with('success', 'Room '.$room->room_number.' created with pricing and media.');
    }

    public function updateRoom(Request $request, HotelRoom $room)
    {
        $hotelCompanyIds = HotelAccess::hotelCompanyIds();
        abort_unless(in_array((int) $room->company_id, $hotelCompanyIds, true), 404);

        $data = $this->validateRoomPayload($request, $hotelCompanyIds, $room);
        $data['room_type_id'] = $this->resolveRoomType($request, $data, $room);
        unset($data['new_room_type_name'], $data['new_room_type_base_rate'], $data['gallery_images']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('room_image')) {
            if ($room->room_image) {
                Storage::disk('public')->delete($room->room_image);
            }
            $data['room_image'] = $request->file('room_image')->store('hotel/rooms', 'public');
        } else {
            unset($data['room_image']);
        }

        if ($request->hasFile('panorama_image')) {
            if ($room->panorama_image) {
                Storage::disk('public')->delete($room->panorama_image);
            }
            $data['panorama_image'] = $request->file('panorama_image')->store('hotel/rooms/panoramas', 'public');
        } else {
            unset($data['panorama_image']);
        }

        $room->update($data);
        $room = $room->fresh();
        $this->syncLegacyMediaIntoGallery($room);
        $this->storeUploadedGalleryImages($request, $room);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id])
            ->with('success', 'Room '.$room->room_number.' updated.');
    }

    public function storeRoomImages(Request $request, HotelRoom $room)
    {
        $hotelCompanyIds = HotelAccess::hotelCompanyIds();
        abort_unless(in_array((int) $room->company_id, $hotelCompanyIds, true), 404);

        $request->validate([
            'room_image' => 'nullable|image|max:5120',
            'panorama_image' => 'nullable|image|max:8192',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:8192',
        ]);

        $updates = [];
        if ($request->hasFile('room_image')) {
            if ($room->room_image) {
                Storage::disk('public')->delete($room->room_image);
            }
            $updates['room_image'] = $request->file('room_image')->store('hotel/rooms', 'public');
        }
        if ($request->hasFile('panorama_image')) {
            if ($room->panorama_image) {
                Storage::disk('public')->delete($room->panorama_image);
            }
            $updates['panorama_image'] = $request->file('panorama_image')->store('hotel/rooms/panoramas', 'public');
        }

        if ($updates) {
            $room->update($updates);
            $room = $room->fresh();
            $this->syncLegacyMediaIntoGallery($room);
        }
        $this->storeUploadedGalleryImages($request, $room);

        return back()->with('success', 'Room media uploaded.');
    }

    public function destroyRoomImage(HotelRoom $room, HotelRoomImage $image)
    {
        $hotelCompanyIds = HotelAccess::hotelCompanyIds();
        abort_unless(in_array((int) $room->company_id, $hotelCompanyIds, true) && (int) $image->room_id === (int) $room->id, 404);

        Storage::disk('public')->delete($image->path);
        if ($room->room_image === $image->path) {
            $room->room_image = null;
        }
        if ($room->panorama_image === $image->path) {
            $room->panorama_image = null;
        }
        $room->save();
        $image->delete();

        return back()->with('success', 'Room image deleted.');
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

    private function roomManagementData(?int $companyId, array $hotelCompanyIds): array
    {
        $companyScope = fn($query) => $query
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds));

        $rooms = collect();
        if ($this->hasTable('hotel_rooms')) {
            $roomQuery = $companyScope(HotelRoom::query())->with(['type', 'property']);
            if ($this->hasTable('hotel_room_images')) {
                $roomQuery->with('images');
            }
            $rooms = $roomQuery->orderByDesc('id')->limit(40)->get();
        }

        $statusCounts = $rooms->groupBy(fn($room) => (string) ($room->operational_status ?: 'available'))->map->count();
        $housekeepingCounts = $rooms->groupBy(fn($room) => (string) ($room->housekeeping_status ?: 'clean'))->map->count();

        return [
            'companies' => Company::query()
                ->when(!empty($hotelCompanyIds), fn($q) => $q->whereIn('id', $hotelCompanyIds), fn($q) => $q->whereRaw('1 = 0'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'properties' => $this->hasTable('hotel_properties')
                ? $companyScope(HotelProperty::query())->orderBy('name')->get()
                : collect(),
            'roomTypes' => $this->hasTable('hotel_room_types')
                ? $companyScope(HotelRoomType::query())->orderBy('name')->get()
                : collect(),
            'rooms' => $rooms,
            'statusCounts' => $statusCounts,
            'housekeepingCounts' => $housekeepingCounts,
            'mediaCount' => $this->hasTable('hotel_room_images')
                ? $companyScope(HotelRoomImage::query())->count()
                : 0,
        ];
    }

    private function validateRoomPayload(Request $request, array $hotelCompanyIds, ?HotelRoom $room = null): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', Rule::in($hotelCompanyIds)],
            'property_id' => [
                'required',
                'integer',
                Rule::exists('hotel_properties', 'id')->where(fn($query) => $query->where('company_id', $request->integer('company_id'))),
            ],
            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hotel_rooms', 'room_number')->where(fn($query) => $query->where('property_id', $request->integer('property_id')))->ignore($room?->id),
            ],
            'room_type_id' => [
                'nullable',
                'integer',
                Rule::exists('hotel_room_types', 'id')->where(fn($query) => $query->where('company_id', $request->integer('company_id'))),
            ],
            'new_room_type_name' => 'nullable|string|max:120',
            'new_room_type_base_rate' => 'nullable|numeric|min:0',
            'floor' => 'nullable|string|max:50',
            'wing' => 'nullable|string|max:50',
            'base_rate_override' => 'nullable|numeric|min:0',
            'room_image' => 'nullable|image|max:5120',
            'panorama_image' => 'nullable|image|max:8192',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:8192',
            'operational_status' => ['required', Rule::in(['available', 'occupied', 'reserved', 'maintenance', 'out_of_order'])],
            'housekeeping_status' => ['required', Rule::in(['clean', 'dirty', 'inspection', 'cleaning'])],
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function resolveRoomType(Request $request, array $data, ?HotelRoom $room = null): ?int
    {
        if (!empty($data['room_type_id'])) {
            $roomType = HotelRoomType::query()->find((int) $data['room_type_id']);
            if ($roomType && $request->filled('new_room_type_base_rate')) {
                $roomType->update(['base_rate' => $request->input('new_room_type_base_rate')]);
            }
            return (int) $data['room_type_id'];
        }

        if (!$request->filled('new_room_type_name')) {
            return $room?->room_type_id;
        }

        $roomType = HotelRoomType::create([
            'company_id' => $data['company_id'],
            'property_id' => $data['property_id'],
            'name' => $request->input('new_room_type_name'),
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $request->input('new_room_type_name')), 0, 12)) ?: 'ROOM',
            'base_rate' => $request->input('new_room_type_base_rate', 0),
            'max_adults' => 2,
            'max_children' => 0,
            'max_occupancy' => 2,
            'is_active' => true,
        ]);

        return $roomType->id;
    }

    private function storeUploadedGalleryImages(Request $request, HotelRoom $room): void
    {
        if (!$this->hasTable('hotel_room_images') || !$request->hasFile('gallery_images')) {
            return;
        }

        $nextSort = (int) HotelRoomImage::query()->where('room_id', $room->id)->max('sort_order');
        foreach ($request->file('gallery_images', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $nextSort++;
            $path = $file->store('hotel/rooms/gallery', 'public');
            HotelRoomImage::create([
                'company_id' => $room->company_id,
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'path' => $path,
                'caption' => 'Super Admin upload',
                'sort_order' => $nextSort,
                'is_cover' => !$room->room_image && $nextSort === 1,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $firstImage = HotelRoomImage::query()->where('room_id', $room->id)->orderBy('sort_order')->first();
        if (!$room->room_image && $firstImage) {
            $room->update(['room_image' => $firstImage->path]);
        }
    }

    private function syncLegacyMediaIntoGallery(HotelRoom $room): void
    {
        if (!$this->hasTable('hotel_room_images')) {
            return;
        }

        foreach ([
            ['path' => $room->room_image, 'is_cover' => true, 'is_panorama' => false, 'caption' => 'Cover image'],
            ['path' => $room->panorama_image, 'is_cover' => false, 'is_panorama' => true, 'caption' => 'Panorama preview'],
        ] as $media) {
            if (!$media['path']) {
                continue;
            }

            HotelRoomImage::query()->firstOrCreate(
                ['room_id' => $room->id, 'path' => $media['path']],
                [
                    'company_id' => $room->company_id,
                    'property_id' => $room->property_id,
                    'caption' => $media['caption'],
                    'is_cover' => $media['is_cover'],
                    'is_panorama' => $media['is_panorama'],
                    'sort_order' => (int) HotelRoomImage::query()->where('room_id', $room->id)->max('sort_order') + 1,
                    'uploaded_by' => auth()->id(),
                ]
            );
        }
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
