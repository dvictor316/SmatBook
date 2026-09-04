<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\Subscription;
use App\Models\HotelProperty;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelRoom;
use App\Models\HotelRoomBlock;
use App\Models\HotelRoomImage;
use App\Models\HotelRoomType;
use App\Models\Transaction;
use App\Services\Hotel\HotelFolioService;
use App\Support\HotelAccess;
use App\Support\LedgerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'service_conference' => 'Conference & Events',
            'services' => 'Service Centers',
            'night_audits' => 'Night Audits',
            'reports' => 'Hotel Reports',
            'settings' => 'Hotel Settings / Feature Status',
        ];
        if (!array_key_exists($panel, $panels)) {
            $panel = 'overview';
        }
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
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

        $totalProperties = $hotelScope(HotelProperty::withoutGlobalScopes())->count();
        $roomHasActiveColumn = $this->hasColumn('hotel_rooms', 'is_active');
        $roomScope = fn() => $hotelScope(HotelRoom::withoutGlobalScopes())
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
            'service_conference' => 'conference',
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

        $revenueTrend = $this->hotelRevenueTrend($selectedCompanyId, $hotelCompanyIds);
        $serviceSummary = $this->hotelServiceSummary($selectedCompanyId, $hotelCompanyIds, $serviceCenters);
        $roomCalendarPulse = $this->hotelCalendarPulse($selectedCompanyId, $hotelCompanyIds);

        return view('SuperAdmin.hotels.overview', compact(
            'totalHotelTenants', 'activeHotelSubscriptions', 'totalProperties', 'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'maintenanceRooms', 'outOfOrderRooms', 'dirtyRooms', 'todayReservations', 'currentInHouseGuests', 'hotelRevenueToday', 'hotelRevenueThisMonth', 'outstandingReceivables', 'panel', 'panels', 'panelData', 'hotelCompanies', 'selectedCompanyId', 'serviceCenters', 'selectedServiceCenter', 'hotelDemoSeedPresent', 'roomManagement', 'revenueTrend', 'serviceSummary', 'roomCalendarPulse'
        ));
    }

    public function storeProperty(Request $request)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::in($hotelCompanyIds)],
            'name' => 'required|string|max:160',
            'code' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:160',
        ]);

        $data['code'] = $data['code'] ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $data['name']), 0, 14));
        $data['currency_code'] = 'NGN';
        $data['timezone'] = 'Africa/Lagos';
        $data['is_active'] = true;

        $property = HotelProperty::withoutGlobalScopes()->create($data);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $property->company_id])
            ->with('success', 'Hotel property '.$property->name.' created. You can now assign rooms to it.');
    }

    public function storeRoom(Request $request)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $data = $this->validateRoomPayload($request, $hotelCompanyIds);

        if (HotelRoom::withoutGlobalScopes()->where('property_id', $data['property_id'])->where('room_number', $data['room_number'])->exists()) {
            return back()->withErrors(['room_number' => 'Room number already exists for this property.'])->withInput();
        }

        $data['room_type_id'] = $this->resolveRoomType($request, $data);
        unset($data['new_room_type_name'], $data['new_room_type_base_rate'], $data['gallery_images']);
        $data['room_image'] = $request->hasFile('room_image') ? $this->storeHotelMedia($request->file('room_image'), 'hotel/rooms') : null;
        $data['panorama_image'] = $request->hasFile('panorama_image') ? $this->storeHotelMedia($request->file('panorama_image'), 'hotel/rooms/panoramas') : null;
        $data['is_active'] = $request->boolean('is_active', true);

        $room = HotelRoom::withoutGlobalScopes()->create($data);
        $this->syncLegacyMediaIntoGallery($room);
        $this->storeUploadedGalleryImages($request, $room);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id])
            ->with('success', 'Room '.$room->room_number.' created with pricing and media.');
    }

    public function updateRoom(Request $request, $room)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);

        $data = $this->validateRoomPayload($request, $hotelCompanyIds, $room);
        $data['room_type_id'] = $this->resolveRoomType($request, $data, $room);
        unset($data['new_room_type_name'], $data['new_room_type_base_rate'], $data['gallery_images']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('room_image')) {
            if ($room->room_image) {
                Storage::disk('public')->delete($room->room_image);
            }
            $data['room_image'] = $this->storeHotelMedia($request->file('room_image'), 'hotel/rooms');
        } else {
            unset($data['room_image']);
        }

        if ($request->hasFile('panorama_image')) {
            if ($room->panorama_image) {
                Storage::disk('public')->delete($room->panorama_image);
            }
            $data['panorama_image'] = $this->storeHotelMedia($request->file('panorama_image'), 'hotel/rooms/panoramas');
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

    public function storeRoomImages(Request $request, $room)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);

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
            $updates['room_image'] = $this->storeHotelMedia($request->file('room_image'), 'hotel/rooms');
        }
        if ($request->hasFile('panorama_image')) {
            if ($room->panorama_image) {
                Storage::disk('public')->delete($room->panorama_image);
            }
            $updates['panorama_image'] = $this->storeHotelMedia($request->file('panorama_image'), 'hotel/rooms/panoramas');
        }

        if ($updates) {
            $room->update($updates);
            $room = $room->fresh();
            $this->syncLegacyMediaIntoGallery($room);
        }
        $this->storeUploadedGalleryImages($request, $room);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id])
            ->with('success', 'Room media uploaded.');
    }

    public function room($room)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id]);
    }

    public function roomImages($room)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);

        return redirect()->route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id]);
    }

    public function destroyRoomImage($room, $image)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);
        $image = HotelRoomImage::withoutGlobalScopes()
            ->where('room_id', $room->id)
            ->findOrFail((int) $image);

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

    public function storeRoomBlock(Request $request, $room)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $room = $this->findSuperAdminHotelRoom((int) $room, $hotelCompanyIds);

        if (!$this->hasTable('hotel_room_blocks')) {
            return back()->withErrors(['room_block' => 'Hotel room block table is not available yet.']);
        }

        $data = $request->validate([
            'block_type' => ['required', Rule::in(['maintenance', 'out_of_order', 'housekeeping_hold', 'room_service_hold', 'vip_hold', 'admin_hold'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        HotelRoomBlock::withoutGlobalScopes()->where('room_id', $room->id)->where('status', 'active')->update(['status' => 'released']);

        HotelRoomBlock::withoutGlobalScopes()->create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'block_type' => $data['block_type'],
            'reason' => $data['reason'] ?: ucfirst(str_replace('_', ' ', $data['block_type'])),
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        $room->update([
            'operational_status' => in_array($data['block_type'], ['maintenance', 'out_of_order'], true) ? $data['block_type'] : 'maintenance',
        ]);

        return back()->with('success', 'Room '.$room->room_number.' has been locked for '.str_replace('_', ' ', $data['block_type']).'.');
    }

    public function releaseRoomBlock($block)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $block = HotelRoomBlock::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $block);

        $block->update(['status' => 'released']);

        $room = HotelRoom::withoutGlobalScopes()->find($block->room_id);
        if ($room && in_array((string) $room->operational_status, ['maintenance', 'out_of_order'], true)) {
            $room->update(['operational_status' => 'available']);
        }

        return back()->with('success', 'Room lock released.');
    }

    public function storeHousekeepingTask(Request $request)
    {
        if (!$this->hasTable('hotel_housekeeping_tasks')) {
            return back()->withErrors(['housekeeping' => 'Hotel housekeeping task table is not available yet.']);
        }

        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
            'task_type' => ['required', Rule::in(['departure_clean', 'stayover', 'deep_clean', 'inspection', 'rush_clean', 'room_service_cleanup'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = $this->findSuperAdminHotelRoom((int) $data['room_id'], $hotelCompanyIds);

        HotelHousekeepingTask::withoutGlobalScopes()->create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'task_type' => $data['task_type'],
            'status' => 'open',
            'priority' => $data['priority'],
            'note' => $data['note'],
            'created_by' => auth()->id(),
        ]);

        $room->update(['housekeeping_status' => in_array($data['task_type'], ['inspection'], true) ? 'inspection' : 'dirty']);

        return back()->with('success', 'Housekeeping task opened for Room '.$room->room_number.'.');
    }

    public function updateHousekeepingTaskStatus(Request $request, $task)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $task = HotelHousekeepingTask::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $task);

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'assigned', 'cleaning', 'inspection', 'completed'])],
        ]);

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'completed') {
            $updates['completed_by'] = auth()->id();
            $updates['completed_at'] = now();
        }
        $task->update($updates);

        $room = HotelRoom::withoutGlobalScopes()->find($task->room_id);
        if ($room) {
            $room->update(['housekeeping_status' => $data['status'] === 'completed' ? 'clean' : ($data['status'] === 'assigned' ? 'dirty' : $data['status'])]);
        }

        return back()->with('success', 'Housekeeping task updated.');
    }

    public function storeMaintenanceTicket(Request $request)
    {
        if (!$this->hasTable('hotel_maintenance_tickets')) {
            return back()->withErrors(['maintenance' => 'Hotel maintenance ticket table is not available yet.']);
        }

        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $room = $this->findSuperAdminHotelRoom((int) $data['room_id'], $hotelCompanyIds);
        $ticketNo = 'MT-'.$room->company_id.'-'.now()->format('ymdHis');

        HotelMaintenanceTicket::withoutGlobalScopes()->create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'ticket_no' => $ticketNo,
            'status' => 'open',
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'reported_by' => auth()->id(),
        ]);

        $room->update(['operational_status' => in_array($data['severity'], ['high', 'critical'], true) ? 'out_of_order' : 'maintenance']);

        return back()->with('success', 'Maintenance ticket '.$ticketNo.' opened for Room '.$room->room_number.'.');
    }

    public function updateMaintenanceTicketStatus(Request $request, $ticket)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $ticket = HotelMaintenanceTicket::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $ticket);

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $updates = ['status' => $data['status']];
        if (in_array($data['status'], ['resolved', 'closed'], true)) {
            $updates['resolved_by'] = auth()->id();
            $updates['resolved_at'] = now();
            $updates['resolution_note'] = $data['resolution_note'];
        }
        $ticket->update($updates);

        $room = HotelRoom::withoutGlobalScopes()->find($ticket->room_id);
        if ($room && in_array($data['status'], ['resolved', 'closed'], true)) {
            $hasOpenTicket = HotelMaintenanceTicket::withoutGlobalScopes()
                ->where('room_id', $room->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->exists();
            if (!$hasOpenTicket) {
                $room->update(['operational_status' => 'available']);
            }
        }

        return back()->with('success', 'Maintenance ticket updated.');
    }

    public function storeRoomType(Request $request)
    {
        if (!$this->hasTable('hotel_room_types')) {
            return back()->withErrors(['room_type' => 'Hotel room type table is not available yet.']);
        }

        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::in($hotelCompanyIds)],
            'property_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'max_adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!empty($data['property_id'])) {
            $property = HotelProperty::withoutGlobalScopes()
                ->where('company_id', $data['company_id'])
                ->findOrFail((int) $data['property_id']);
            $data['property_id'] = $property->id;
        } else {
            $data['property_id'] = HotelProperty::withoutGlobalScopes()->where('company_id', $data['company_id'])->value('id');
        }

        $data['code'] = $data['code'] ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $data['name']), 0, 12));
        $data['max_adults'] = $data['max_adults'] ?? 2;
        $data['max_children'] = $data['max_children'] ?? 0;
        $data['max_occupancy'] = $data['max_occupancy'] ?? max(1, (int) $data['max_adults'] + (int) $data['max_children']);
        $data['is_active'] = true;

        HotelRoomType::withoutGlobalScopes()->create($data);

        return back()->with('success', 'Room type '.$data['name'].' created.');
    }

    public function updateRoomType(Request $request, $type)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $type = HotelRoomType::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $type);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'max_adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = $data['code'] ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $data['name']), 0, 12));
        $data['max_adults'] = $data['max_adults'] ?? 2;
        $data['max_children'] = $data['max_children'] ?? 0;
        $data['max_occupancy'] = $data['max_occupancy'] ?? max(1, (int) $data['max_adults'] + (int) $data['max_children']);
        $data['is_active'] = $request->boolean('is_active');

        $type->update($data);

        return back()->with('success', 'Room type '.$type->name.' updated.');
    }

    public function storeServiceCharge(Request $request)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $centers = $this->hotelServiceCenterMap();

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', Rule::in($hotelCompanyIds)],
            'folio_id' => ['required', 'integer'],
            'service_center' => ['required', 'string', Rule::in(array_keys($centers))],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['required', 'string', Rule::in(['charge_to_room', 'cash', 'card', 'transfer', 'other'])],
            'service_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $folio = GuestFolio::withoutGlobalScopes()
            ->with('stay')
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->where('status', 'open')
            ->findOrFail((int) $data['folio_id']);

        $data['company_id'] = (int) $folio->company_id;

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) $data['unit_price'];
        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $amount = max(0.01, ($quantity * $unitPrice) + $tax - $discount);
        $center = $centers[$data['service_center']];
        $createdItem = null;

        DB::transaction(function () use ($folio, $data, $quantity, $unitPrice, $discount, $tax, $amount, $center, &$createdItem) {
            $createdItem = app(HotelFolioService::class)->postCharge($folio, [
                'description' => $data['description'],
                'amount' => $amount,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'type' => 'service',
                'service_code' => $center['code'],
                'service_date' => $data['service_date'] ?? now()->toDateString(),
                'source_type' => self::class,
                'source_id' => $folio->id,
                'posted_by' => auth()->id(),
                'meta' => [
                    'center' => $data['service_center'],
                    'payment_mode' => $data['payment_mode'],
                    'discount' => $discount,
                    'tax' => $tax,
                    'note' => $data['note'] ?? null,
                    'posted_from' => 'super_admin_hotel_monitor',
                ],
            ]);

            LedgerService::postHotelFolioCharge(
                $createdItem,
                $folio,
                $folio->stay?->branch_id,
                $folio->stay?->branch_name
            );

            if ($data['payment_mode'] !== 'charge_to_room') {
                $payment = app(HotelFolioService::class)->postPayment($folio, [
                    'description' => ucfirst(str_replace('_', ' ', $data['payment_mode'])) . ' payment for ' . strtolower($data['description']),
                    'amount' => $amount,
                    'type' => 'payment',
                    'service_code' => strtoupper($data['payment_mode']),
                    'service_date' => $data['service_date'] ?? now()->toDateString(),
                    'source_type' => self::class,
                    'source_id' => $folio->id,
                    'posted_by' => auth()->id(),
                    'meta' => [
                        'center' => $data['service_center'],
                        'payment_mode' => $data['payment_mode'],
                        'settles_folio_item_id' => $createdItem->id,
                        'posted_from' => 'super_admin_hotel_monitor',
                    ],
                ]);

                LedgerService::postHotelFolioPayment(
                    $payment,
                    $folio,
                    0,
                    $folio->stay?->branch_id,
                    $folio->stay?->branch_name
                );
            }
        });

        return redirect()
            ->route('super_admin.hotels.receipts.show', ['item' => $createdItem->id, 'print' => 1])
            ->with('success', $center['label'].' posted and receipt opened.');
    }

    public function serviceReceipt($item)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $item = FolioItem::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $item);
        $folio = GuestFolio::withoutGlobalScopes()
            ->with(['customer', 'stay.room'])
            ->find($item->folio_id);
        $item->setRelation('folio', $folio);

        return view('SuperAdmin.hotels.receipt', ['item' => $item, 'folio' => $folio, 'isSuperAdminReceipt' => true]);
    }

    public function updateServiceCharge(Request $request, $item)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $centers = $this->hotelServiceCenterMap();

        $folioItem = FolioItem::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $item);

        if (!$this->isManageableHotelServiceSale($folioItem)) {
            return back()->with('error', 'Only posted hotel service sales can be edited here. Room charges remain controlled by stays and room-rate workflows.');
        }

        $data = $request->validate([
            'service_center' => ['required', 'string', Rule::in(array_keys($centers))],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'service_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $folio = GuestFolio::withoutGlobalScopes()
            ->with('stay')
            ->findOrFail((int) $folioItem->folio_id);

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) $data['unit_price'];
        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $amount = max(0.01, ($quantity * $unitPrice) + $tax - $discount);
        $center = $centers[$data['service_center']];
        $meta = is_array($folioItem->meta) ? $folioItem->meta : [];

        DB::transaction(function () use ($folioItem, $folio, $data, $quantity, $unitPrice, $discount, $tax, $amount, $center, $meta) {
            $folioItem->forceFill([
                'description' => $data['description'],
                'amount' => $amount,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'type' => 'service',
                'service_code' => $center['code'],
                'service_date' => $data['service_date'] ?? now()->toDateString(),
                'meta' => array_merge($meta, [
                    'center' => $data['service_center'],
                    'discount' => $discount,
                    'tax' => $tax,
                    'note' => $data['note'] ?? null,
                    'edited_from' => 'super_admin_hotel_monitor',
                    'edited_at' => now()->toDateTimeString(),
                ]),
            ])->save();

            LedgerService::postHotelFolioCharge(
                $folioItem->fresh(),
                $folio,
                $folio->stay?->branch_id,
                $folio->stay?->branch_name
            );

            foreach ($this->linkedServicePayments($folioItem) as $payment) {
                $paymentMeta = is_array($payment->meta) ? $payment->meta : [];
                $paymentMode = (string) ($paymentMeta['payment_mode'] ?? $paymentMeta['center'] ?? $payment->service_code ?? 'payment');
                $payment->forceFill([
                    'description' => ucfirst(str_replace('_', ' ', $paymentMode)) . ' payment for ' . strtolower($data['description']),
                    'amount' => $amount,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'service_date' => $data['service_date'] ?? now()->toDateString(),
                    'meta' => array_merge($paymentMeta, [
                        'center' => $data['service_center'],
                        'settles_folio_item_id' => $folioItem->id,
                        'edited_from' => 'super_admin_hotel_monitor',
                        'edited_at' => now()->toDateTimeString(),
                    ]),
                ])->save();

                LedgerService::postHotelFolioPayment(
                    $payment->fresh(),
                    $folio,
                    0,
                    $folio->stay?->branch_id,
                    $folio->stay?->branch_name
                );
            }

            app(HotelFolioService::class)->recalculate($folio->fresh());
        });

        return back()->with('success', $center['label'].' updated.');
    }

    public function destroyServiceCharge($item)
    {
        $hotelCompanyIds = $this->superAdminHotelCompanyIds();
        $folioItem = FolioItem::withoutGlobalScopes()
            ->when(!empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds))
            ->findOrFail((int) $item);

        if (!$this->isManageableHotelServiceSale($folioItem)) {
            return back()->with('error', 'Only posted hotel service sales can be deleted here. Room charges remain controlled by stays and room-rate workflows.');
        }

        $folio = GuestFolio::withoutGlobalScopes()->find($folioItem->folio_id);

        DB::transaction(function () use ($folioItem, $folio) {
            foreach ($this->linkedServicePayments($folioItem) as $payment) {
                Transaction::query()
                    ->where('related_id', $payment->id)
                    ->where('related_type', FolioItem::class)
                    ->delete();
                $payment->delete();
            }

            Transaction::query()
                ->where('related_id', $folioItem->id)
                ->where('related_type', FolioItem::class)
                ->delete();
            $folioItem->delete();

            if ($folio) {
                app(HotelFolioService::class)->recalculate($folio->fresh());
            }
        });

        return back()->with('success', 'Hotel service sale deleted and folio totals refreshed.');
    }

    private function isManageableHotelServiceSale(FolioItem $item): bool
    {
        $serviceCode = strtoupper((string) ($item->service_code ?? ''));

        return !empty($item->folio_id)
            && in_array((string) ($item->type ?? ''), ['service', 'pos_charge', 'charge'], true)
            && !in_array($serviceCode, ['ROOM', 'ROOM_NIGHT'], true);
    }

    private function linkedServicePayments(FolioItem $charge)
    {
        return FolioItem::withoutGlobalScopes()
            ->where('folio_id', $charge->folio_id)
            ->whereIn('type', ['payment', 'deposit_applied'])
            ->get()
            ->filter(function (FolioItem $payment) use ($charge) {
                $meta = is_array($payment->meta) ? $payment->meta : [];
                return (int) ($meta['settles_folio_item_id'] ?? 0) === (int) $charge->id;
            })
            ->values();
    }

    private function hotelServiceCenterMap(): array
    {
        return [
            'restaurant' => ['code' => 'RESTAURANT', 'label' => 'Restaurant sale'],
            'bar' => ['code' => 'BAR', 'label' => 'Bar sale'],
            'gym' => ['code' => 'GYM', 'label' => 'Gym sale'],
            'spa' => ['code' => 'SPA', 'label' => 'Spa sale'],
            'ticketing' => ['code' => 'TICKETING', 'label' => 'Ticket sale'],
            'room_service' => ['code' => 'ROOM_SERVICE', 'label' => 'Room service sale'],
            'minibar' => ['code' => 'MINIBAR', 'label' => 'Minibar sale'],
            'laundry' => ['code' => 'LAUNDRY', 'label' => 'Laundry sale'],
            'conference' => ['code' => 'CONFERENCE', 'label' => 'Conference sale'],
        ];
    }

    private function hotelRevenueTrend(?int $companyId, array $hotelCompanyIds): array
    {
        $days = collect(range(6, 0))->mapWithKeys(function ($offset) {
            $date = now()->subDays($offset)->toDateString();
            return [$date => ['date' => $date, 'label' => now()->subDays($offset)->format('D'), 'amount' => 0.0]];
        });
        $today = now()->toDateString();
        $days[$today] = ['date' => $today, 'label' => now()->format('D'), 'amount' => 0.0];

        $source = null;
        if ($this->hasTable('hotel_transactions') && $this->hasColumn('hotel_transactions', 'amount')) {
            $source = \DB::table('hotel_transactions');
        } elseif ($this->hasTable('folio_items') && $this->hasColumn('folio_items', 'amount')) {
            $source = \DB::table('folio_items')
                ->when($this->hasColumn('folio_items', 'type'), fn($q) => $q->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge']));
        }

        if (!$source) {
            return $days->values()->all();
        }

        $rows = $source
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(created_at) as revenue_date, SUM(amount) as total')
            ->groupBy('revenue_date')
            ->pluck('total', 'revenue_date');

        foreach ($rows as $date => $amount) {
            if (isset($days[$date])) {
                $day = $days[$date];
                $day['amount'] = (float) $amount;
                $days[$date] = $day;
            }
        }

        return $days->values()->all();
    }

    private function hotelServiceSummary(?int $companyId, array $hotelCompanyIds, array $serviceCenters): array
    {
        $summary = collect($serviceCenters)
            ->reject(fn($meta, $key) => $key === 'all')
            ->map(fn($meta) => ['label' => $meta['label'], 'count' => 0, 'total' => 0.0])
            ->all();

        if (!$this->hasTable('folio_items') || !$this->hasColumn('folio_items', 'service_code') || !$this->hasColumn('folio_items', 'amount')) {
            return $summary;
        }

        $rows = \DB::table('folio_items')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
            ->when($this->hasColumn('folio_items', 'type'), fn($q) => $q->whereIn('type', ['charge', 'room_night', 'service', 'pos_charge']))
            ->selectRaw('UPPER(COALESCE(service_code, "")) as service_code, COUNT(*) as line_count, SUM(amount) as total')
            ->groupBy('service_code')
            ->get();

        foreach ($rows as $row) {
            foreach ($serviceCenters as $key => $meta) {
                if ($key === 'all') {
                    continue;
                }

                if (in_array((string) $row->service_code, $meta['codes'] ?? [], true)) {
                    $summary[$key]['count'] += (int) $row->line_count;
                    $summary[$key]['total'] += (float) $row->total;
                    break;
                }
            }
        }

        return $summary;
    }

    private function hotelCalendarPulse(?int $companyId, array $hotelCompanyIds): array
    {
        return collect(range(0, 6))->map(function ($offset) use ($companyId, $hotelCompanyIds) {
            $date = now()->addDays($offset)->toDateString();
            $arrivals = 0;
            $departures = 0;
            $stays = 0;
            $locks = 0;

            if ($this->hasTable('reservations') && $this->hasColumn('reservations', 'arrival_date') && $this->hasColumn('reservations', 'departure_date')) {
                $reservationScope = fn($q) => $q
                    ->when($companyId, fn($query) => $query->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($query) => $query->whereIn('company_id', $hotelCompanyIds));

                $arrivals = (clone $reservationScope(\DB::table('reservations')))->whereDate('arrival_date', $date)->count();
                $departures = (clone $reservationScope(\DB::table('reservations')))->whereDate('departure_date', $date)->count();
                $stays = (clone $reservationScope(\DB::table('reservations')))
                    ->whereDate('arrival_date', '<=', $date)
                    ->whereDate('departure_date', '>=', $date)
                    ->count();
            }

            if ($this->hasTable('hotel_room_blocks')) {
                $locks = \DB::table('hotel_room_blocks')
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date)
                    ->count();
            }

            return [
                'date' => $date,
                'label' => now()->addDays($offset)->format('D, M j'),
                'arrivals' => $arrivals,
                'departures' => $departures,
                'stays' => $stays,
                'locks' => $locks,
            ];
        })->all();
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

        if ($panel === 'availability' && $this->hasTable('hotel_rooms')) {
            return HotelRoom::withoutGlobalScopes()
                ->with([
                    'type' => fn($query) => $query->withoutGlobalScopes(),
                    'property' => fn($query) => $query->withoutGlobalScopes(),
                ])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when(!$companyId && !empty($hotelCompanyIds), fn($q) => $q->whereIn('company_id', $hotelCompanyIds))
                ->when($this->hasColumn('hotel_rooms', 'is_active'), fn($q) => $q->where('is_active', 1))
                ->where('operational_status', 'available')
                ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
                ->paginate(20)
                ->withQueryString();
        }

        if (in_array($panel, ['reservations', 'room_calendar', 'check_in'], true) && $this->hasTable('reservations')) {
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
            $roomQuery = $companyScope(HotelRoom::withoutGlobalScopes())->with([
                'type' => fn($query) => $query->withoutGlobalScopes(),
                'property' => fn($query) => $query->withoutGlobalScopes(),
            ]);
            if ($this->hasTable('hotel_room_images')) {
                $roomQuery->with(['images' => fn($query) => $query->withoutGlobalScopes()]);
            }
            $rooms = $roomQuery->orderByDesc('id')->limit(40)->get();
        }

        $statusCounts = $rooms->groupBy(fn($room) => (string) ($room->operational_status ?: 'available'))->map->count();
        $housekeepingCounts = $rooms->groupBy(fn($room) => (string) ($room->housekeeping_status ?: 'clean'))->map->count();
        $activeBlocks = collect();
        if ($this->hasTable('hotel_room_blocks') && $rooms->isNotEmpty()) {
            $activeBlocks = HotelRoomBlock::withoutGlobalScopes()
                ->whereIn('room_id', $rooms->pluck('id'))
                ->where('status', 'active')
                ->whereDate('start_date', '<=', now()->toDateString())
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderByDesc('id')
                ->get()
                ->keyBy('room_id');
        }

        return [
            'companies' => Company::query()
                ->when(!empty($hotelCompanyIds), fn($q) => $q->whereIn('id', $hotelCompanyIds), fn($q) => $q->whereRaw('1 = 0'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'properties' => $this->hasTable('hotel_properties')
                ? $companyScope(HotelProperty::withoutGlobalScopes())->orderBy('name')->get()
                : collect(),
            'roomTypes' => $this->hasTable('hotel_room_types')
                ? $companyScope(HotelRoomType::withoutGlobalScopes())->orderBy('name')->get()
                : collect(),
            'rooms' => $rooms,
            'statusCounts' => $statusCounts,
            'housekeepingCounts' => $housekeepingCounts,
            'activeBlocks' => $activeBlocks,
            'openFolios' => $this->hasTable('guest_folios')
                ? $companyScope(GuestFolio::withoutGlobalScopes())
                    ->with(['customer', 'stay.room'])
                    ->where('status', 'open')
                    ->latest('id')
                    ->limit(80)
                    ->get()
                : collect(),
            'mediaCount' => $this->hasTable('hotel_room_images')
                ? $companyScope(HotelRoomImage::withoutGlobalScopes())->count()
                : 0,
        ];
    }

    private function superAdminHotelCompanyIds(): array
    {
        $ids = collect(HotelAccess::hotelCompanyIds());

        foreach ([
            'hotel_properties',
            'hotel_room_types',
            'hotel_rooms',
            'reservations',
            'stays',
            'guest_folios',
            'hotel_housekeeping_tasks',
            'hotel_maintenance_tickets',
            'hotel_room_blocks',
        ] as $table) {
            if ($this->hasTable($table) && $this->hasColumn($table, 'company_id')) {
                $ids = $ids->merge(\DB::table($table)->whereNotNull('company_id')->pluck('company_id'));
            }
        }

        return $ids
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function findSuperAdminHotelRoom(int $roomId, array $hotelCompanyIds): HotelRoom
    {
        $query = HotelRoom::withoutGlobalScopes();

        if (!empty($hotelCompanyIds)) {
            $query->whereIn('company_id', $hotelCompanyIds);
        }

        return $query
            ->findOrFail($roomId);
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
            $roomType = HotelRoomType::withoutGlobalScopes()->find((int) $data['room_type_id']);
            if ($roomType && $request->filled('new_room_type_base_rate')) {
                $roomType->update(['base_rate' => $request->input('new_room_type_base_rate')]);
            }
            return (int) $data['room_type_id'];
        }

        if (!$request->filled('new_room_type_name')) {
            return $room?->room_type_id;
        }

        $roomType = HotelRoomType::withoutGlobalScopes()->create([
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

        $nextSort = (int) HotelRoomImage::withoutGlobalScopes()->where('room_id', $room->id)->max('sort_order');
        foreach ($request->file('gallery_images', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $nextSort++;
            $path = $this->storeHotelMedia($file, 'hotel/rooms/gallery');
            HotelRoomImage::withoutGlobalScopes()->create([
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

        $firstImage = HotelRoomImage::withoutGlobalScopes()->where('room_id', $room->id)->orderBy('sort_order')->first();
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

            HotelRoomImage::withoutGlobalScopes()->firstOrCreate(
                ['room_id' => $room->id, 'path' => $media['path']],
                [
                    'company_id' => $room->company_id,
                    'property_id' => $room->property_id,
                    'caption' => $media['caption'],
                    'is_cover' => $media['is_cover'],
                    'is_panorama' => $media['is_panorama'],
                    'sort_order' => (int) HotelRoomImage::withoutGlobalScopes()->where('room_id', $room->id)->max('sort_order') + 1,
                    'uploaded_by' => auth()->id(),
                ]
            );
        }
    }

    private function storeHotelMedia(UploadedFile $file, string $directory): string
    {
        $this->ensureHotelMediaDirectory($directory);

        $path = $file->store($directory, 'public');
        if (!$path) {
            throw ValidationException::withMessages([
                'room_image' => 'Hotel media upload failed. Confirm storage/app/public is writable by the web server.',
            ]);
        }

        return $path;
    }

    private function ensureHotelMediaDirectory(string $directory): void
    {
        $directory = trim($directory, '/');
        $path = storage_path('app/public/'.$directory);

        try {
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0775, true, true);
            }

            if (!File::isWritable($path)) {
                throw new \RuntimeException('Directory is not writable.');
            }
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'room_image' => 'Hotel media folder is not writable: storage/app/public/'.$directory.'. Run the server storage permission commands, then try again.',
            ]);
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
