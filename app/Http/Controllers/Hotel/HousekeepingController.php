<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');

        $tasks = HotelHousekeepingTask::query()
            ->with(['room.type', 'stay.customer'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')
            ->get()
            ->groupBy(fn ($task) => (string) $task->status);

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereHas('property')
            ->orderBy('room_number')
            ->get();

        $departedDirtyRooms = $rooms
            ->filter(fn ($room) => (string) $room->housekeeping_status === 'dirty')
            ->values();

        $arrivalsWaitingForRoom = Reservation::query()
            ->with(['customer', 'roomType', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('room_id')
                    ->orWhereHas('room', fn ($roomQuery) => $roomQuery->where('housekeeping_status', 'dirty'));
            })
            ->orderBy('arrival_date')
            ->limit(12)
            ->get();

        $priorityTasks = HotelHousekeepingTask::query()
            ->with(['room.type', 'stay.customer'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereIn('priority', ['high', 'urgent'])
            ->whereIn('status', ['open', 'assigned', 'cleaning', 'inspection'])
            ->latest('id')
            ->limit(12)
            ->get();

        $summary = [
            'vacant_clean' => $rooms->filter(fn ($room) => (string) $room->operational_status === 'available' && (string) $room->housekeeping_status === 'clean')->count(),
            'occupied' => $rooms->filter(fn ($room) => (string) $room->operational_status === 'occupied')->count(),
            'dirty' => $rooms->filter(fn ($room) => (string) $room->housekeeping_status === 'dirty')->count(),
            'maintenance' => $rooms->filter(fn ($room) => in_array((string) $room->operational_status, ['maintenance', 'out_of_order'], true))->count(),
            'arriving' => $arrivalsWaitingForRoom->count(),
            'assigned' => (int) ($tasks->get('assigned')?->count() ?? 0),
            'cleaning' => (int) ($tasks->get('cleaning')?->count() ?? 0),
            'clean' => $rooms->filter(fn ($room) => (string) $room->housekeeping_status === 'clean')->count(),
            'inspection' => (int) ($tasks->get('inspection')?->count() ?? 0),
        ];

        if (view()->exists('hotel.housekeeping.index')) {
            return view('hotel.housekeeping.index', compact('tasks', 'summary', 'rooms', 'departedDirtyRooms', 'arrivalsWaitingForRoom', 'priorityTasks'));
        }

        return response()->json(['data' => $tasks]);
    }

    public function markDirty(HotelRoom $room)
    {
        $this->assertRoomScope($room);

        $room->update([
            'housekeeping_status' => 'dirty',
        ]);

        $this->openTaskForRoom($room, [
            'task_type' => 'ad_hoc_clean',
            'priority' => 'normal',
            'note' => 'Room marked dirty manually.',
        ]);

        return back()->with('success', 'Room marked dirty and housekeeping task opened.');
    }

    public function storeTask(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $validated = $request->validate([
            'room_id' => [
                'required',
                'integer',
                Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'task_type' => 'required|string|max:40',
            'priority' => 'required|in:low,normal,high,urgent',
            'note' => 'nullable|string|max:1000',
        ]);

        $room = HotelRoom::query()
            ->where('company_id', $companyId)
            ->findOrFail((int) $validated['room_id']);
        $this->assertRoomScope($room);

        $room->update(['housekeeping_status' => 'dirty']);
        $this->openTaskForRoom($room, $validated);

        return back()->with('success', 'Housekeeping task opened for room ' . $room->room_number . '.');
    }

    public function updateTaskStatus(Request $request, HotelHousekeepingTask $task)
    {
        $this->assertTaskScope($task);

        $validated = $request->validate([
            'status' => 'required|in:open,assigned,cleaning,inspection,completed',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validated['status'] === 'completed') {
            return $this->completeTask($task);
        }

        $payload = [
            'status' => $validated['status'],
            'completed_by' => null,
            'completed_at' => null,
        ];

        if (!empty($validated['note'])) {
            $payload['note'] = $validated['note'];
        }

        if ($validated['status'] === 'assigned' && empty($task->assigned_to)) {
            $payload['assigned_to'] = auth()->id();
        }

        $task->update($payload);

        $roomStatus = in_array($validated['status'], ['cleaning', 'inspection'], true)
            ? $validated['status']
            : 'dirty';

        HotelRoom::query()
            ->where('company_id', $task->company_id)
            ->where('id', $task->room_id)
            ->update(['housekeeping_status' => $roomStatus]);

        return back()->with('success', 'Housekeeping task moved to ' . str_replace('_', ' ', $validated['status']) . '.');
    }

    public function markClean(HotelRoom $room)
    {
        $this->assertRoomScope($room);

        $updates = [
            'housekeeping_status' => 'clean',
        ];

        if ((string) $room->operational_status !== 'occupied') {
            $updates['operational_status'] = 'available';
        }

        $room->update($updates);

        HotelHousekeepingTask::query()
            ->where('company_id', $room->company_id)
            ->where('room_id', $room->id)
            ->whereIn('status', ['open', 'assigned', 'cleaning', 'inspection'])
            ->update([
                'status' => 'completed',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

        return back()->with('success', 'Room marked clean.');
    }

    public function completeTask(HotelHousekeepingTask $task)
    {
        $this->assertTaskScope($task);

        $task->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        HotelRoom::query()
            ->where('company_id', $task->company_id)
            ->where('id', $task->room_id)
            ->where('operational_status', '!=', 'occupied')
            ->update([
                'housekeeping_status' => 'clean',
                'operational_status' => 'available',
            ]);

        return back()->with('success', 'Housekeeping task completed.');
    }

    private function openTaskForRoom(HotelRoom $room, array $attributes): HotelHousekeepingTask
    {
        $activeTask = HotelHousekeepingTask::query()
            ->where('company_id', $room->company_id)
            ->where('room_id', $room->id)
            ->whereIn('status', ['open', 'assigned', 'cleaning', 'inspection'])
            ->latest('id')
            ->first();

        if ($activeTask) {
            $activeTask->update([
                'task_type' => $attributes['task_type'] ?? $activeTask->task_type,
                'priority' => $attributes['priority'] ?? $activeTask->priority,
                'note' => $attributes['note'] ?? $activeTask->note,
            ]);

            return $activeTask;
        }

        return HotelHousekeepingTask::create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'task_type' => $attributes['task_type'] ?? 'ad_hoc_clean',
            'status' => 'open',
            'priority' => $attributes['priority'] ?? 'normal',
            'created_by' => auth()->id(),
            'note' => $attributes['note'] ?? null,
        ]);
    }

    private function assertRoomScope(HotelRoom $room): void
    {
        abort_unless((int) $room->company_id === (int) auth()->user()->company_id, 404);

        $branchId = auth()->user()->branch_id ?? null;
        if (!$branchId) {
            return;
        }

        $propertyBranchId = HotelProperty::query()
            ->where('company_id', $room->company_id)
            ->where('id', $room->property_id)
            ->value('branch_id');

        abort_unless((string) $propertyBranchId === (string) $branchId, 404);
    }

    private function assertTaskScope(HotelHousekeepingTask $task): void
    {
        abort_unless((int) $task->company_id === (int) auth()->user()->company_id, 404);

        $branchId = auth()->user()->branch_id ?? null;
        if (!$branchId) {
            return;
        }

        $propertyBranchId = HotelProperty::query()
            ->where('company_id', $task->company_id)
            ->where('id', $task->property_id)
            ->value('branch_id');

        abort_unless((string) $propertyBranchId === (string) $branchId, 404);
    }
}
