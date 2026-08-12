<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelRoom;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $tickets = HotelMaintenanceTicket::query()
            ->where('company_id', auth()->user()->company_id)
            ->latest('id')
            ->paginate(30);

        if (view()->exists('hotel.maintenance.index')) {
            return view('hotel.maintenance.index', compact('tickets'));
        }

        return response()->json(['data' => $tickets]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
            'assigned_to' => 'nullable|integer',
        ]);

        $room = HotelRoom::query()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail((int) $validated['room_id']);

        $ticket = HotelMaintenanceTicket::create([
            'company_id' => $room->company_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'ticket_no' => 'MNT-' . now()->format('YmdHis') . '-' . $room->id,
            'status' => 'open',
            'severity' => $validated['severity'] ?? 'medium',
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'reported_by' => auth()->id(),
            'assigned_to' => $validated['assigned_to'] ?? null,
        ]);

        $room->update([
            'operational_status' => 'maintenance',
        ]);

        return back()->with('success', 'Maintenance ticket created: ' . $ticket->ticket_no);
    }

    public function updateStatus(Request $request, HotelMaintenanceTicket $ticket)
    {
        abort_unless((int) $ticket->company_id === (int) auth()->user()->company_id, 404);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,cancelled',
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $updates = [
            'status' => $validated['status'],
        ];

        if ($validated['status'] === 'resolved') {
            $updates['resolved_by'] = auth()->id();
            $updates['resolved_at'] = now();
            $updates['resolution_note'] = $validated['resolution_note'] ?? null;
        }

        $ticket->update($updates);

        if ($validated['status'] === 'resolved') {
            HotelRoom::query()
                ->where('company_id', $ticket->company_id)
                ->where('id', $ticket->room_id)
                ->update([
                    'operational_status' => 'available',
                    'housekeeping_status' => 'dirty',
                ]);
        }

        return back()->with('success', 'Maintenance ticket updated.');
    }
}
