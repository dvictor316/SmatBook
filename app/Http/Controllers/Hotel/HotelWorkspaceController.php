<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelOperationalEvent;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomBlock;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\Stay;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HotelWorkspaceController extends Controller
{
    public function roomStatus(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $status = (string) $request->query('status', '');
        $housekeeping = (string) $request->query('housekeeping', '');
        $roomTypeId = (int) $request->query('room_type_id', 0);
        $floor = trim((string) $request->query('floor', ''));

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($status !== '', fn ($query) => $query->where('operational_status', $status))
            ->when($housekeeping !== '', fn ($query) => $query->where('housekeeping_status', $housekeeping))
            ->when($roomTypeId > 0, fn ($query) => $query->where('room_type_id', $roomTypeId))
            ->when($floor !== '', fn ($query) => $query->where('floor', $floor))
            ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
            ->paginate(36)
            ->withQueryString();

        $statusTotals = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->selectRaw('operational_status, COUNT(*) as total_count')
            ->groupBy('operational_status')
            ->pluck('total_count', 'operational_status');

        $floors = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('floor')
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor');

        $roomTypes = HotelRoomType::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderBy('name')
            ->get();

        return view('hotel.rooms.status', compact('rooms', 'status', 'housekeeping', 'statusTotals', 'floors', 'floor', 'roomTypes', 'roomTypeId'));
    }

    public function roomCalendar(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $viewPreset = (string) $request->query('view', '14d');
        $days = match ($viewPreset) {
            '7d' => 7,
            '30d' => 30,
            default => 14,
        };

        $today = now()->startOfDay();
        $start = $request->filled('start_date')
            ? Carbon::parse((string) $request->query('start_date'))->startOfDay()
            : $today->copy();

        if ((string) $request->query('nav') === 'prev') {
            $start = $start->copy()->subDays($days);
        }
        if ((string) $request->query('nav') === 'next') {
            $start = $start->copy()->addDays($days);
        }
        if ((string) $request->query('nav') === 'today') {
            $start = $today->copy();
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $customStart = Carbon::parse((string) $request->query('from_date'))->startOfDay();
            $customEnd = Carbon::parse((string) $request->query('to_date'))->endOfDay();
            $diff = max(1, $customStart->diffInDays($customEnd) + 1);
            $days = min(45, $diff);
            $start = $customStart;
        }

        $end = $start->copy()->addDays($days - 1)->endOfDay();

        $roomTypeId = (int) $request->query('room_type_id', 0);
        $floor = trim((string) $request->query('floor', ''));
        $roomStatus = trim((string) $request->query('room_status', ''));
        $reservationStatus = trim((string) $request->query('reservation_status', ''));

        $rooms = HotelRoom::query()
            ->with(['type', 'property'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($roomTypeId > 0, fn ($query) => $query->where('room_type_id', $roomTypeId))
            ->when($floor !== '', fn ($query) => $query->where('floor', $floor))
            ->when($roomStatus !== '', fn ($query) => $query->where('operational_status', $roomStatus))
            ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
            ->limit(140)
            ->get();

        $roomIds = $rooms->pluck('id');

        $reservations = Reservation::query()
            ->with(['customer', 'roomType', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($reservationStatus !== '', fn ($query) => $query->where('status', $reservationStatus))
            ->whereNotNull('room_id')
            ->whereIn('room_id', $roomIds)
            ->whereDate('arrival_date', '<=', $end->toDateString())
            ->whereDate('departure_date', '>=', $start->toDateString())
            ->get();

        $stays = Stay::query()
            ->with(['customer', 'reservation', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('room_id')
            ->whereIn('room_id', $roomIds)
            ->where('checkin_at', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('actual_checkout_at')
                    ->orWhere('actual_checkout_at', '>=', $start);
            })
            ->get();

        $roomBlocks = HotelRoomBlock::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('room_id', $roomIds)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $openMaintenanceRoomIds = HotelMaintenanceTicket::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('status', ['open', 'in_progress'])
            ->pluck('room_id')
            ->unique();

        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dates[] = $cursor->copy();
        }

        $calendarRows = $rooms->map(function (HotelRoom $room) use ($dates, $start, $days, $stays, $reservations, $roomBlocks, $openMaintenanceRoomIds) {
            $cells = array_fill(0, $days, null);

            $placeMarker = function (array $marker, int $startIdx, int $endIdx, int $priority) use (&$cells) {
                for ($i = max(0, $startIdx); $i <= min(count($cells) - 1, $endIdx); $i++) {
                    if (!isset($cells[$i]) || $cells[$i] === null || ($cells[$i]['priority'] ?? 0) <= $priority) {
                        $cells[$i] = ['key' => $marker['key'], 'priority' => $priority, 'marker' => $marker];
                    }
                }
            };

            foreach ($reservations->where('room_id', $room->id) as $reservation) {
                $startIdx = max(0, $start->diffInDays($reservation->arrival_date, false));
                $endIdx = min($days - 1, $start->diffInDays($reservation->departure_date, false));
                $status = strtolower((string) $reservation->status);
                $visualStatus = match ($status) {
                    'inquiry' => 'tentative',
                    'reserved' => 'confirmed',
                    'confirmed' => 'guaranteed',
                    'checked_in' => 'checked_in',
                    'completed' => 'checked_out',
                    'cancelled' => 'cancelled',
                    'no_show' => 'no_show',
                    default => 'confirmed',
                };

                $placeMarker([
                    'key' => 'res_' . $reservation->id,
                    'kind' => 'reservation',
                    'id' => $reservation->id,
                    'status' => $visualStatus,
                    'label' => (string) ($reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest'),
                    'sub' => (string) ($reservation->reservation_number ?? 'RES'),
                    'room_type' => (string) ($reservation->roomType?->name ?? ''),
                    'arrival' => optional($reservation->arrival_date)->toDateString(),
                    'departure' => optional($reservation->departure_date)->toDateString(),
                    'deposit' => (float) ($reservation->deposit_received ?? 0),
                    'deposit_required' => (float) ($reservation->deposit_required ?? 0),
                    'total' => (float) ($reservation->total ?? 0),
                    'balance' => (float) ($reservation->balance ?? 0),
                    'special_requests' => (string) ($reservation->special_requests ?? ''),
                ], $startIdx, $endIdx, 60);
            }

            foreach ($stays->where('room_id', $room->id) as $stay) {
                $stayStart = optional($stay->checkin_at)?->copy()->startOfDay();
                $stayEnd = optional($stay->actual_checkout_at)?->copy()->startOfDay()
                    ?? optional($stay->expected_checkout_at)?->copy()->startOfDay()
                    ?? now()->copy()->addDay()->startOfDay();
                if (!$stayStart) {
                    continue;
                }

                $startIdx = max(0, $start->diffInDays($stayStart, false));
                $endIdx = min($days - 1, $start->diffInDays($stayEnd, false));
                $placeMarker([
                    'key' => 'stay_' . $stay->id,
                    'kind' => 'stay',
                    'id' => $stay->id,
                    'reservation_id' => (int) ($stay->reservation_id ?? 0),
                    'status' => strtolower((string) ($stay->status ?? 'checked_in')),
                    'label' => (string) ($stay->customer?->customer_name ?? $stay->customer?->name ?? 'In-House'),
                    'sub' => 'Stay #' . $stay->id,
                    'room_type' => (string) ($room->type?->name ?? ''),
                    'arrival' => optional($stay->checkin_at)?->toDateString(),
                    'departure' => optional($stay->expected_checkout_at)?->toDateString(),
                ], $startIdx, $endIdx, 80);
            }

            foreach ($roomBlocks->where('room_id', $room->id) as $block) {
                $startIdx = max(0, $start->diffInDays($block->start_date, false));
                $endIdx = min($days - 1, $start->diffInDays($block->end_date, false));
                $placeMarker([
                    'key' => 'blk_' . $block->id,
                    'kind' => 'block',
                    'id' => $block->id,
                    'status' => strtolower((string) $block->block_type),
                    'label' => 'Blocked',
                    'sub' => (string) ($block->reason ?: ucfirst((string) $block->block_type)),
                    'arrival' => optional($block->start_date)->toDateString(),
                    'departure' => optional($block->end_date)->toDateString(),
                ], $startIdx, $endIdx, 95);
            }

            if ($openMaintenanceRoomIds->contains($room->id) || in_array((string) $room->operational_status, ['maintenance', 'out_of_order'], true)) {
                $placeMarker([
                    'key' => 'mnt_room_' . $room->id,
                    'kind' => 'maintenance',
                    'id' => $room->id,
                    'status' => (string) ($room->operational_status === 'out_of_order' ? 'out_of_order' : 'maintenance'),
                    'label' => $room->operational_status === 'out_of_order' ? 'Out of Order' : 'Maintenance',
                    'sub' => 'Room unavailable',
                    'arrival' => null,
                    'departure' => null,
                ], 0, $days - 1, 100);
            }

            $segments = [];
            $idx = 0;
            while ($idx < $days) {
                $cell = $cells[$idx];
                if ($cell === null) {
                    $segments[] = [
                        'kind' => 'empty',
                        'colspan' => 1,
                        'date' => $dates[$idx]->toDateString(),
                    ];
                    $idx++;
                    continue;
                }

                $key = $cell['key'];
                $span = 1;
                $next = $idx + 1;
                while ($next < $days && isset($cells[$next]) && $cells[$next] !== null && ($cells[$next]['key'] ?? null) === $key) {
                    $span++;
                    $next++;
                }

                $segments[] = array_merge($cell['marker'], [
                    'kind' => (string) $cell['marker']['kind'],
                    'colspan' => $span,
                    'date' => $dates[$idx]->toDateString(),
                ]);
                $idx = $next;
            }

            return [
                'room' => $room,
                'segments' => $segments,
            ];
        })->values();

        $unassignedReservations = Reservation::query()
            ->with(['customer', 'roomType'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereNull('room_id')
            ->whereDate('arrival_date', '<=', $end->toDateString())
            ->orderBy('arrival_date')
            ->limit(30)
            ->get();

        $properties = HotelProperty::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $floors = HotelRoom::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereNotNull('floor')
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor');

        $roomTypes = HotelRoomType::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderBy('name')
            ->get();

        return view('hotel.rooms.calendar', compact(
            'calendarRows',
            'dates',
            'start',
            'end',
            'days',
            'viewPreset',
            'properties',
            'propertyId',
            'floors',
            'floor',
            'roomTypes',
            'roomTypeId',
            'roomStatus',
            'reservationStatus',
            'unassignedReservations'
        ));
    }

    public function quickCreateFromCalendar(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
            'arrival_date' => 'required|date',
            'departure_date' => 'nullable|date|after:arrival_date',
        ]);

        $room = HotelRoom::query()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail((int) $validated['room_id']);

        $departureDate = $validated['departure_date'] ?? Carbon::parse((string) $validated['arrival_date'])->addDay()->toDateString();

        return redirect()->route('hotel.reservations.create', [
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'property_id' => $room->property_id,
            'arrival_date' => $validated['arrival_date'],
            'departure_date' => $departureDate,
        ]);
    }

    public function assignRoom(Request $request, Reservation $reservation)
    {
        abort_unless((int) $reservation->company_id === (int) auth()->user()->company_id, 404);

        $validated = $request->validate([
            'room_id' => 'required|integer|exists:hotel_rooms,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $room = HotelRoom::query()
            ->where('company_id', $reservation->company_id)
            ->findOrFail((int) $validated['room_id']);

        if ((int) $room->property_id !== (int) $reservation->property_id) {
            return back()->withErrors(['error' => 'Selected room does not belong to reservation property.']);
        }

        $arrivalDate = optional($reservation->arrival_date)->toDateString();
        $departureDate = optional($reservation->departure_date)->toDateString();
        if (!$arrivalDate || !$departureDate) {
            return back()->withErrors(['error' => 'Reservation dates are required before assigning room.']);
        }

        if ((int) $reservation->room_id !== (int) $room->id) {
            $isAvailable = RoomAvailabilityService::isRoomAvailable($room->id, $arrivalDate, $departureDate);
            if (!$isAvailable) {
                return back()->withErrors(['error' => 'Selected room is unavailable for reservation dates.']);
            }
        }

        DB::transaction(function () use ($reservation, $room, $validated) {
            $oldRoomId = (int) ($reservation->room_id ?? 0);
            $reservation->update(['room_id' => $room->id]);

            $stay = Stay::query()
                ->where('company_id', $reservation->company_id)
                ->where('reservation_id', $reservation->id)
                ->where('status', 'checked_in')
                ->latest('id')
                ->first();

            if ($stay) {
                $stay->update(['room_id' => $room->id]);
                HotelRoom::query()->where('company_id', $reservation->company_id)->where('id', $room->id)->update(['operational_status' => 'occupied']);
                if ($oldRoomId > 0 && $oldRoomId !== (int) $room->id) {
                    HotelRoom::query()->where('company_id', $reservation->company_id)->where('id', $oldRoomId)->update(['operational_status' => 'available', 'housekeeping_status' => 'dirty']);
                }
            }

            $this->event($reservation->company_id, $reservation->property_id, [
                'reservation_id' => $reservation->id,
                'stay_id' => $stay?->id,
                'customer_id' => $reservation->customer_id,
                'room_id' => $room->id,
                'event_type' => 'room.assigned',
                'title' => 'Room assigned',
                'description' => 'Reservation assigned to room ' . $room->room_number,
                'meta' => ['reason' => $validated['reason'] ?? null, 'previous_room_id' => $oldRoomId],
            ]);
        });

        return back()->with('success', 'Room assigned successfully.');
    }

    public function changeRoom(Request $request, Stay $stay)
    {
        abort_unless((int) $stay->company_id === (int) auth()->user()->company_id, 404);

        $validated = $request->validate([
            'room_id' => 'required|integer|exists:hotel_rooms,id',
            'reason' => 'required|string|max:255',
        ]);

        $newRoom = HotelRoom::query()->where('company_id', $stay->company_id)->findOrFail((int) $validated['room_id']);
        if ((int) $newRoom->property_id !== (int) $stay->property_id) {
            return back()->withErrors(['error' => 'New room does not belong to the same property.']);
        }

        $fromDate = now()->toDateString();
        $toDate = optional($stay->expected_checkout_at)?->toDateString() ?? now()->addDay()->toDateString();
        if ((int) $stay->room_id !== (int) $newRoom->id && !RoomAvailabilityService::isRoomAvailable($newRoom->id, $fromDate, $toDate)) {
            return back()->withErrors(['error' => 'Selected room is not available for the remaining stay dates.']);
        }

        DB::transaction(function () use ($stay, $newRoom, $validated) {
            $oldRoomId = (int) $stay->room_id;
            $stay->update(['room_id' => $newRoom->id]);

            if ($stay->reservation_id) {
                Reservation::query()
                    ->where('company_id', $stay->company_id)
                    ->where('id', $stay->reservation_id)
                    ->update(['room_id' => $newRoom->id]);
            }

            HotelRoom::query()->where('company_id', $stay->company_id)->where('id', $newRoom->id)->update(['operational_status' => 'occupied']);
            HotelRoom::query()->where('company_id', $stay->company_id)->where('id', $oldRoomId)->update(['operational_status' => 'available', 'housekeeping_status' => 'dirty']);

            HotelHousekeepingTask::create([
                'company_id' => $stay->company_id,
                'property_id' => $stay->property_id,
                'room_id' => $oldRoomId,
                'stay_id' => $stay->id,
                'task_type' => 'room_change_clean',
                'status' => 'open',
                'priority' => 'high',
                'note' => 'Room change clean-up required.',
                'created_by' => auth()->id(),
            ]);

            $this->event($stay->company_id, $stay->property_id, [
                'reservation_id' => $stay->reservation_id,
                'stay_id' => $stay->id,
                'customer_id' => $stay->customer_id,
                'room_id' => $newRoom->id,
                'event_type' => 'stay.room_changed',
                'title' => 'Room changed',
                'description' => 'Guest moved to room ' . $newRoom->room_number,
                'meta' => ['from_room_id' => $oldRoomId, 'reason' => $validated['reason']],
            ]);
        });

        return back()->with('success', 'Room changed successfully.');
    }

    public function extendReservation(Request $request, Reservation $reservation)
    {
        abort_unless((int) $reservation->company_id === (int) auth()->user()->company_id, 404);

        $validated = $request->validate([
            'new_departure_date' => 'required|date|after:today',
        ]);

        $currentDeparture = optional($reservation->departure_date);
        if (!$currentDeparture) {
            return back()->withErrors(['error' => 'Reservation has no departure date.']);
        }

        $newDeparture = Carbon::parse((string) $validated['new_departure_date']);
        if ($newDeparture->lte($currentDeparture)) {
            return back()->withErrors(['error' => 'New checkout must be after current checkout.']);
        }

        if (!$reservation->room_id) {
            return back()->withErrors(['error' => 'Assign room first before extending stay.']);
        }

        $extensionStart = $currentDeparture->toDateString();
        $extensionEnd = $newDeparture->toDateString();
        $canExtend = RoomAvailabilityService::isRoomAvailable((int) $reservation->room_id, $extensionStart, $extensionEnd);

        if (!$canExtend) {
            $alternatives = RoomAvailabilityService::availableRoomsForProperty((int) $reservation->property_id, $extensionStart, $extensionEnd)
                ->where('room_type_id', $reservation->room_type_id)
                ->take(8);

            $msg = 'Current room is unavailable for extension.';
            if ($alternatives->isNotEmpty()) {
                $msg .= ' Alternative rooms: ' . $alternatives->pluck('room_number')->implode(', ');
            }

            return back()->withErrors(['error' => $msg]);
        }

        DB::transaction(function () use ($reservation, $currentDeparture, $newDeparture) {
            $additionalNights = $currentDeparture->diffInDays($newDeparture);
            $nightlyRate = (float) ($reservation->nightly_rate ?? 0);
            $additionalAmount = $additionalNights * $nightlyRate;

            $reservation->update([
                'departure_date' => $newDeparture->toDateString(),
                'nights' => (int) ($reservation->nights ?? 0) + $additionalNights,
                'subtotal' => (float) ($reservation->subtotal ?? 0) + $additionalAmount,
                'total' => (float) ($reservation->total ?? 0) + $additionalAmount,
                'balance' => (float) ($reservation->balance ?? 0) + $additionalAmount,
            ]);

            Stay::query()
                ->where('company_id', $reservation->company_id)
                ->where('reservation_id', $reservation->id)
                ->where('status', 'checked_in')
                ->update(['expected_checkout_at' => $newDeparture->toDateString() . ' 12:00:00']);

            $this->event($reservation->company_id, $reservation->property_id, [
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'room_id' => $reservation->room_id,
                'event_type' => 'stay.extended',
                'title' => 'Stay extended',
                'description' => 'Checkout moved to ' . $newDeparture->toDateString(),
                'meta' => [
                    'old_departure' => $currentDeparture->toDateString(),
                    'new_departure' => $newDeparture->toDateString(),
                    'additional_nights' => $additionalNights,
                ],
            ]);
        });

        return back()->with('success', 'Stay extended successfully.');
    }

    public function blockRoom(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer|exists:hotel_rooms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'block_type' => 'required|in:maintenance,renovation,vip_hold,management_hold,other,out_of_order,blocked',
            'reason' => 'nullable|string|max:255',
        ]);

        $room = HotelRoom::query()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail((int) $validated['room_id']);

        $block = HotelRoomBlock::create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'block_type' => $validated['block_type'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        if (in_array($validated['block_type'], ['maintenance', 'out_of_order'], true)) {
            $room->update([
                'operational_status' => $validated['block_type'] === 'out_of_order' ? 'out_of_order' : 'maintenance',
            ]);
        }

        $this->event($room->company_id, $room->property_id, [
            'room_id' => $room->id,
            'event_type' => 'room.blocked',
            'title' => 'Room blocked',
            'description' => 'Room ' . $room->room_number . ' blocked from ' . $validated['start_date'] . ' to ' . $validated['end_date'],
            'meta' => ['block_id' => $block->id, 'block_type' => $validated['block_type'], 'reason' => $validated['reason'] ?? null],
        ]);

        return back()->with('success', 'Room block saved successfully.');
    }

    public function search(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);
        $term = trim((string) $request->query('q', ''));

        $results = [
            'guests' => collect(),
            'reservations' => collect(),
            'rooms' => collect(),
            'folios' => collect(),
            'receipts' => collect(),
        ];

        if ($term !== '') {
            $results['guests'] = DB::table('customers')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($term) {
                    $query->where('customer_name', 'like', '%' . $term . '%')
                        ->orWhere('phone', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%');
                })
                ->limit(12)
                ->get();

            $results['reservations'] = Reservation::query()
                ->with(['customer', 'room'])
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->where(function ($query) use ($term) {
                    $query->where('reservation_number', 'like', '%' . $term . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('customer_name', 'like', '%' . $term . '%')
                                ->orWhere('phone', 'like', '%' . $term . '%')
                                ->orWhere('email', 'like', '%' . $term . '%');
                        });
                })
                ->limit(12)
                ->get();

            $results['rooms'] = HotelRoom::query()
                ->with('type')
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->where('room_number', 'like', '%' . $term . '%')
                ->limit(12)
                ->get();

            $results['folios'] = GuestFolio::query()
                ->with('customer')
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->where('folio_number', 'like', '%' . $term . '%')
                ->limit(12)
                ->get();

            $results['receipts'] = FolioItem::query()
                ->where('company_id', $companyId)
                ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
                ->whereIn('type', ['payment', 'deposit_applied'])
                ->where(function ($query) use ($term) {
                    $query->where('posting_key', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                })
                ->latest('id')
                ->limit(12)
                ->get();
        }

        return view('hotel.search.index', compact('term', 'results'));
    }

    public function laundry(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $orders = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
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
        $propertyId = $this->resolvePropertyId($request);

        $entries = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
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
        $propertyId = $this->resolvePropertyId($request);

        $items = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
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
        $propertyId = $this->resolvePropertyId($request);

        $bookings = Reservation::query()
            ->with(['customer', 'property'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereRaw('LOWER(COALESCE(source, "")) in (?, ?)', ['conference', 'event'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.operations.conference', compact('bookings'));
    }


    public function serviceCenter(Request $request, string $center)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $centers = [
            'bar' => ['title' => 'Bar Sales', 'codes' => ['BAR'], 'description' => 'Bar orders, drinks, lounge bills and guest-room postings.'],
            'gym' => ['title' => 'Gym & Fitness', 'codes' => ['GYM', 'FITNESS'], 'description' => 'Gym day passes, membership charges and in-house guest postings.'],
            'spa' => ['title' => 'Spa & Wellness', 'codes' => ['SPA', 'WELLNESS'], 'description' => 'Spa treatments, wellness packages and guest folio charges.'],
        ];

        abort_unless(array_key_exists($center, $centers), 404);
        $meta = $centers[$center];

        $items = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('service_code', $meta['codes'])
            ->latest('service_date')
            ->paginate(20)
            ->withQueryString();

        $total = (float) FolioItem::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('service_code', $meta['codes'])
            ->sum('amount');

        return view('hotel.operations.service_center', compact('items', 'meta', 'center', 'total'));
    }

    public function corporateAccounts(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

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

    public function groupBookings(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $groups = Reservation::query()
            ->with(['customer', 'roomType', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('adults', '>=', 4)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('hotel.business.group_bookings', compact('groups'));
    }

    public function bookingSources(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $sources = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->selectRaw('COALESCE(source, "direct") as booking_source, COUNT(*) as reservations_count, SUM(COALESCE(total,0)) as gross_value')
            ->groupBy('booking_source')
            ->orderByDesc('reservations_count')
            ->get();

        return view('hotel.business.booking_sources', compact('sources'));
    }

    public function reports(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->resolvePropertyId($request);

        $kpis = [
            'arrivals_today' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('arrival_date', now()->toDateString())->count(),
            'departures_today' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('departure_date', now()->toDateString())->count(),
            'occupancy' => Stay::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'checked_in')->count(),
            'room_revenue_today' => FolioItem::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereDate('service_date', now()->toDateString())->where('service_code', 'ROOM_NIGHT')->sum('amount'),
        ];

        return view('hotel.reports.index', compact('kpis'));
    }

    private function resolvePropertyId(Request $request): ?int
    {
        $companyId = (int) auth()->user()->company_id;

        if ($request->has('property_id')) {
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

        return HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');
    }

    private function event(int $companyId, ?int $propertyId, array $payload): void
    {
        HotelOperationalEvent::create([
            'company_id' => $companyId,
            'property_id' => $propertyId,
            'reservation_id' => $payload['reservation_id'] ?? null,
            'stay_id' => $payload['stay_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'room_id' => $payload['room_id'] ?? null,
            'event_type' => $payload['event_type'],
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }
}
