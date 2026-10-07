<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelGuestRequest;
use App\Models\HotelMaintenanceTicket;
use App\Models\HotelOperationalEvent;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HotelOperationsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $base = HotelGuestRequest::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId));

        $requests = (clone $base)
            ->with(['customer', 'room', 'stay', 'assignee'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('department'), fn ($query) => $query->where('department', $request->query('department')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $query->where(function ($sub) use ($term) {
                    $sub->where('request_number', 'like', "%{$term}%")
                        ->orWhere('subject', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('customer_name', 'like', "%{$term}%"))
                        ->orWhereHas('room', fn ($room) => $room->where('room_number', 'like', "%{$term}%"));
                });
            })
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('due_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $activeStatuses = ['open', 'assigned', 'in_progress'];
        $summary = [
            'open' => (clone $base)->whereIn('status', $activeStatuses)->count(),
            'overdue' => (clone $base)->whereIn('status', $activeStatuses)->where('due_at', '<', now())->count(),
            'urgent' => (clone $base)->whereIn('status', $activeStatuses)->whereIn('priority', ['high', 'urgent'])->count(),
            'resolved_today' => (clone $base)->where('status', 'resolved')->whereDate('resolved_at', today())->count(),
            'arrivals' => Reservation::query()->where('company_id', $companyId)->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->whereDate('arrival_date', today())->whereIn('status', ['reserved', 'confirmed'])->count(),
            'departures' => Stay::query()->where('company_id', $companyId)->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->whereDate('expected_checkout_at', today())->where('status', 'checked_in')->count(),
            'dirty_rooms' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->where('housekeeping_status', 'dirty')->count(),
            'maintenance' => HotelMaintenanceTicket::query()->where('company_id', $companyId)->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $stays = Stay::query()
            ->with(['customer', 'room'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->orderBy('room_id')
            ->get();

        $staff = User::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        return view('hotel.operations.index', compact('requests', 'summary', 'stays', 'staff'));
    }

    public function store(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();
        abort_unless($propertyId, 422, 'No active hotel property is configured for this branch.');

        $validated = $request->validate([
            'stay_id' => ['nullable', 'integer', Rule::exists('stays', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('property_id', $propertyId))],
            'category' => 'required|in:concierge,housekeeping,maintenance,room_service,laundry,transport,wakeup_call,complaint,amenity,other',
            'department' => 'required|in:front_office,concierge,housekeeping,engineering,food_beverage,security,transport,management',
            'priority' => 'required|in:low,normal,high,urgent',
            'subject' => 'required|string|max:160',
            'details' => 'nullable|string|max:2000',
            'due_at' => 'nullable|date',
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
        ]);

        $stay = ! empty($validated['stay_id'])
            ? Stay::with(['reservation', 'customer'])->where('company_id', $companyId)->findOrFail((int) $validated['stay_id'])
            : null;

        $guestRequest = DB::transaction(function () use ($validated, $stay, $companyId, $propertyId) {
            $guestRequest = HotelGuestRequest::create([
                ...$validated,
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'reservation_id' => $stay?->reservation_id,
                'customer_id' => $stay?->customer_id,
                'room_id' => $stay?->room_id,
                'request_number' => 'REQ-'.now()->format('ymd-His').'-'.random_int(10, 99),
                'status' => ! empty($validated['assigned_to']) ? 'assigned' : 'open',
                'requested_at' => now(),
                'due_at' => $validated['due_at'] ?? $this->defaultDueAt($validated['priority']),
                'created_by' => auth()->id(),
            ]);

            $this->recordEvent($guestRequest, 'guest_request.created', 'Guest request opened');

            return $guestRequest;
        });

        return back()->with('success', "Request {$guestRequest->request_number} opened.");
    }

    public function update(Request $request, HotelGuestRequest $guestRequest)
    {
        $this->assertScope($guestRequest);
        $companyId = (int) auth()->user()->company_id;

        $validated = $request->validate([
            'status' => 'required|in:open,assigned,in_progress,on_hold,resolved,cancelled',
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'priority' => 'nullable|in:low,normal,high,urgent',
            'due_at' => 'nullable|date',
            'resolution_note' => 'nullable|string|max:2000|required_if:status,resolved',
        ]);

        $oldStatus = (string) $guestRequest->status;
        $payload = $validated;
        if (in_array($validated['status'], ['assigned', 'in_progress'], true) && ! $guestRequest->acknowledged_at) {
            $payload['acknowledged_at'] = now();
        }
        if ($validated['status'] === 'resolved') {
            $payload['resolved_at'] = now();
            $payload['resolved_by'] = auth()->id();
        } elseif ($oldStatus === 'resolved') {
            $payload['resolved_at'] = null;
            $payload['resolved_by'] = null;
        }

        $guestRequest->update($payload);
        $this->recordEvent($guestRequest, 'guest_request.updated', 'Guest request moved from '.$oldStatus.' to '.$validated['status']);

        return back()->with('success', "Request {$guestRequest->request_number} updated.");
    }

    private function currentPropertyId(): ?int
    {
        return HotelProperty::query()
            ->where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->where('is_active', true)
            ->value('id');
    }

    private function defaultDueAt(string $priority)
    {
        return now()->addMinutes(match ($priority) {
            'urgent' => 15,
            'high' => 30,
            'normal' => 60,
            default => 180,
        });
    }

    private function assertScope(HotelGuestRequest $guestRequest): void
    {
        abort_unless((int) $guestRequest->company_id === (int) auth()->user()->company_id, 404);
        $propertyId = $this->currentPropertyId();
        abort_unless(! $propertyId || (int) $guestRequest->property_id === $propertyId, 404);
    }

    private function recordEvent(HotelGuestRequest $guestRequest, string $type, string $title): void
    {
        HotelOperationalEvent::create([
            'company_id' => $guestRequest->company_id,
            'property_id' => $guestRequest->property_id,
            'reservation_id' => $guestRequest->reservation_id,
            'stay_id' => $guestRequest->stay_id,
            'customer_id' => $guestRequest->customer_id,
            'room_id' => $guestRequest->room_id,
            'event_type' => $type,
            'title' => $title,
            'description' => $guestRequest->request_number.': '.$guestRequest->subject,
            'meta' => ['status' => $guestRequest->status, 'priority' => $guestRequest->priority],
            'created_by' => auth()->id(),
        ]);
    }
}
