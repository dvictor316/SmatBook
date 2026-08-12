<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->value('id');

        $tickets = HotelMaintenanceTicket::query()
            ->with('room.type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderBy('room_number')
            ->get();

        $summary = [
            'open' => HotelMaintenanceTicket::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'open')->count(),
            'urgent' => HotelMaintenanceTicket::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->whereIn('severity', ['high', 'critical'])->whereIn('status', ['open', 'in_progress'])->count(),
            'in_progress' => HotelMaintenanceTicket::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'in_progress')->count(),
            'completed_today' => HotelMaintenanceTicket::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('status', 'resolved')->whereDate('resolved_at', now()->toDateString())->count(),
        ];

        $reservationConflicts = Reservation::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('arrival_date', '>=', now()->toDateString())
            ->whereHas('room', fn ($query) => $query->whereIn('operational_status', ['maintenance', 'out_of_order']))
            ->limit(10)
            ->get();

        if (view()->exists('hotel.maintenance.index')) {
            return view('hotel.maintenance.index', compact('tickets', 'rooms', 'summary', 'reservationConflicts'));
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
