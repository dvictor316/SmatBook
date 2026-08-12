<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelRoom;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    public function index()
    {
        $tasks = HotelHousekeepingTask::query()
            ->where('company_id', auth()->user()->company_id)
            ->latest('id')
            ->paginate(30);

        if (view()->exists('hotel.housekeeping.index')) {
            return view('hotel.housekeeping.index', compact('tasks'));
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
