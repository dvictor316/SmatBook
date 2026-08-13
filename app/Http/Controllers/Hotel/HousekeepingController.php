<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Http\Request;

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
            ->where('priority', 'high')
            ->whereIn('status', ['open', 'assigned', 'cleaning'])
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

        HotelHousekeepingTask::create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'task_type' => 'ad_hoc_clean',
            'status' => 'open',
            'priority' => 'normal',
            'created_by' => auth()->id(),
            'note' => 'Room marked dirty manually.',
        ]);

        return back()->with('success', 'Room marked dirty and housekeeping task opened.');
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
            ->where('status', 'open')
            ->update([
                'status' => 'completed',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

        return back()->with('success', 'Room marked clean.');
    }

    public function completeTask(HotelHousekeepingTask $task)
    {
        abort_unless((int) $task->company_id === (int) auth()->user()->company_id, 404);

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

    private function assertRoomScope(HotelRoom $room): void
    {
        abort_unless((int) $room->company_id === (int) auth()->user()->company_id, 404);
    }
}
