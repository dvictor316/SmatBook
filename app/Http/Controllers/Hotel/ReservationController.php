<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelOperationalEvent;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\Stay;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\HotelPropertyContext;
use App\Models\Account;
use App\Models\HotelBookingSource;
use App\Models\HotelCorporateAccount;
use App\Models\HotelDepositTransaction;
use App\Services\Hotel\HotelDepositService;
use App\Models\HotelRatePlan;
use App\Models\HotelRateRestriction;
use App\Models\HotelGuestProfile;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : $this->currentPropertyId();

        $reservations = Reservation::where('company_id', $companyId)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('arrival_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('departure_date', '<=', $request->to_date))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $query->where(function ($sub) use ($term) {
                    $sub->where('reservation_number', 'like', '%'.$term.'%')
                        ->orWhere('source', 'like', '%'.$term.'%')
                        ->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('customer_name', 'like', '%'.$term.'%')
                                ->orWhere('phone', 'like', '%'.$term.'%')
                                ->orWhere('email', 'like', '%'.$term.'%');
                        });
                });
            })
            ->with(['customer', 'roomType', 'room'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $properties = HotelProperty::where('company_id', $companyId)->orderBy('name')->get();

        return view('hotel.reservations.index', compact('reservations', 'properties', 'propertyId'));
    }

    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $property = HotelPropertyContext::query((int) $companyId)->first();
        $roomTypes = $property ? HotelRoomType::where('company_id', $companyId)->where('property_id', $property->id)->where('is_active', true)->get() : collect();

        $arrivalDate = (string) $request->query('arrival_date', now()->toDateString());
        $departureDate = (string) $request->query('departure_date', now()->addDay()->toDateString());
        $prefilledRoomTypeId = (int) $request->query('room_type_id', 0);
        $prefilledRoomId = (int) $request->query('room_id', 0);

        $availableRooms = collect();
        if ($property && $request->filled('arrival_date') && $request->filled('departure_date')) {
            $availableRooms = RoomAvailabilityService::availableRoomsForProperty((int) $property->id, $arrivalDate, $departureDate);
        }
        $bookingSources = HotelBookingSource::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $corporateAccounts = HotelCorporateAccount::query()->where('company_id', $companyId)->where('status', 'active')->orderBy('company_name')->get();
        $paymentAccounts = Account::query()->where('company_id', $companyId)->where('type', Account::TYPE_ASSET)->where('is_active', true)->orderBy('name')->get();
        $ratePlans = $property ? HotelRatePlan::query()->where('company_id', $companyId)->where('property_id', $property->id)->where('is_active', true)->orderBy('name')->get() : collect();

        return view('hotel.reservations.create', compact('property', 'roomTypes', 'availableRooms', 'arrivalDate', 'departureDate', 'prefilledRoomTypeId', 'prefilledRoomId', 'bookingSources', 'corporateAccounts', 'paymentAccounts', 'ratePlans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'room_id' => 'nullable|exists:hotel_rooms,id',
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id))],
            'rate_plan_id' => ['nullable', Rule::exists('hotel_rate_plans', 'id')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id)->where('is_active', true))],
            'nights' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'nightly_rate' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'deposit_received' => 'nullable|numeric|min:0',
            'deposit_payment_method' => 'nullable|in:cash,transfer,pos,card,other',
            'deposit_account_id' => ['nullable', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id)->where('is_active', true))],
            'deposit_reference' => 'nullable|string|max:100',
            'booking_source_id' => ['nullable', Rule::exists('hotel_booking_sources', 'id')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id)->where('is_active', true))],
            'corporate_account_id' => ['nullable', Rule::exists('hotel_corporate_accounts', 'id')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id)->where('status', 'active'))],
            'source' => 'nullable|string|max:120',
            'special_requests' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:1000',
        ]);

        $propertyId = $this->currentPropertyId();
        if (! $propertyId) {
            return back()->withErrors(['error' => 'No active hotel property found for current branch.'])->withInput();
        }

        $arrival = \Illuminate\Support\Carbon::parse($data['arrival_date']);
        $departure = \Illuminate\Support\Carbon::parse($data['departure_date']);
        $nights = max(1, (int) ($data['nights'] ?? $arrival->diffInDays($departure)));
        $nightlyRate = (float) ($data['nightly_rate'] ?? 0);
        $subtotal = $nightlyRate * $nights;
        $depositRequired = (float) ($data['deposit_required'] ?? 0);
        $depositReceived = (float) ($data['deposit_received'] ?? 0);
        if (! empty($data['customer_id']) && HotelGuestProfile::query()->where('company_id', auth()->user()->company_id)->where('customer_id', $data['customer_id'])->where('do_not_rent', true)->exists()) {
            return back()->withErrors(['customer_id' => 'This guest profile is marked Do Not Rent. A manager must review the profile before booking.'])->withInput();
        }
        if (! empty($data['rate_plan_id'])) {
            $restrictionError = $this->rateRestrictionError((int) $data['rate_plan_id'], $arrival, $departure, $nights);
            if ($restrictionError) {
                return back()->withErrors(['rate_plan_id' => $restrictionError])->withInput();
            }
        }
        $source = ! empty($data['booking_source_id'])
            ? HotelBookingSource::query()->where('company_id', auth()->user()->company_id)->findOrFail((int) $data['booking_source_id'])->name
            : ($data['source'] ?? 'direct');

        $roomId = isset($data['room_id']) && (int) $data['room_id'] > 0 ? (int) $data['room_id'] : null;
        if ($roomId) {
            $room = HotelRoom::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('property_id', $propertyId)
                ->findOrFail($roomId);

            if (! RoomAvailabilityService::isRoomAvailable($room->id, $arrival->toDateString(), $departure->toDateString())) {
                return back()->withErrors(['error' => 'Selected room is unavailable for those dates.'])->withInput();
            }
        }

        $reservation = DB::transaction(function () use ($data, $propertyId, $roomId, $nights, $nightlyRate, $subtotal, $depositRequired, $depositReceived, $source) {
            $reservation = Reservation::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
            'property_id' => $propertyId,
            'reservation_number' => strtoupper(Str::random(8)),
            'room_id' => $roomId,
            'nights' => $nights,
            'adults' => (int) ($data['adults'] ?? 1),
            'children' => (int) ($data['children'] ?? 0),
            'nightly_rate' => $nightlyRate,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'deposit_required' => $depositRequired,
            'deposit_received' => 0,
            'balance' => $subtotal,
            'status' => 'reserved',
            'source' => $source,
            'special_requests' => $data['special_requests'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'created_by' => auth()->id(),
            ]));

            if ($depositReceived > 0) {
                app(HotelDepositService::class)->receive($reservation, $depositReceived, $data['deposit_payment_method'] ?? 'cash', $data['deposit_account_id'] ?? null, $data['deposit_reference'] ?? null, 'Deposit received during reservation creation.');
            }

            return $reservation->fresh();
        });

        HotelOperationalEvent::create([
            'company_id' => $reservation->company_id,
            'property_id' => $reservation->property_id,
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'room_id' => $reservation->room_id,
            'event_type' => 'reservation.created',
            'title' => 'Reservation created',
            'description' => 'Reservation '.$reservation->reservation_number.' created.',
            'meta' => ['arrival_date' => $reservation->arrival_date?->toDateString(), 'departure_date' => $reservation->departure_date?->toDateString()],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('hotel.reservations.show', $reservation)->with('success', 'Reservation created');
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);

        $reservation->load(['customer', 'room', 'roomType', 'bookingSource', 'corporateAccount', 'groupBooking']);
        $stay = Stay::query()
            ->where('company_id', $reservation->company_id)
            ->where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        $events = HotelOperationalEvent::query()
            ->where('company_id', $reservation->company_id)
            ->where(function ($query) use ($reservation, $stay) {
                $query->where('reservation_id', $reservation->id);
                if ($stay) {
                    $query->orWhere('stay_id', $stay->id);
                }
            })
            ->latest('id')
            ->limit(30)
            ->get();

        $availableRooms = HotelRoom::query()
            ->where('company_id', $reservation->company_id)
            ->where('property_id', $reservation->property_id)
            ->where('is_active', true)
            ->orderByRaw('CAST(room_number AS UNSIGNED), room_number')
            ->get();

        $deposits = HotelDepositTransaction::query()->where('company_id', $reservation->company_id)->where('reservation_id', $reservation->id)->latest('id')->get();
        $paymentAccounts = Account::query()->where('company_id', $reservation->company_id)->where('type', Account::TYPE_ASSET)->where('is_active', true)->orderBy('name')->get();

        return view('hotel.reservations.show', compact('reservation', 'stay', 'events', 'availableRooms', 'deposits', 'paymentAccounts'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->assertReservationScope($reservation);
        abort_if(in_array((string) $reservation->status, ['checked_in', 'completed', 'cancelled', 'no_show'], true), 422, 'This reservation can no longer be amended.');

        $data = $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'arrival_time' => 'nullable|date_format:H:i',
            'departure_time' => 'nullable|date_format:H:i',
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $reservation->company_id)->where('property_id', $reservation->property_id))],
            'adults' => 'required|integer|min:1|max:50',
            'children' => 'required|integer|min:0|max:50',
            'nightly_rate' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'deposit_received' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:120',
            'special_requests' => 'nullable|string|max:2000',
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $arrival = \Illuminate\Support\Carbon::parse($data['arrival_date']);
        $departure = \Illuminate\Support\Carbon::parse($data['departure_date']);
        $roomId = ! empty($data['room_id']) ? (int) $data['room_id'] : null;
        $room = null;
        if ($roomId && ! RoomAvailabilityService::isRoomAvailable($roomId, $arrival->toDateString(), $departure->toDateString(), $reservation->id)) {
            return back()->withErrors(['room_id' => 'The selected room is not available for the amended dates.'])->withInput();
        }
        if ($roomId) {
            $room = HotelRoom::query()->where('company_id', $reservation->company_id)->where('property_id', $reservation->property_id)->findOrFail($roomId);
        }

        $nights = max(1, $arrival->diffInDays($departure));
        $subtotal = round($nights * (float) $data['nightly_rate'], 2);
        $total = round($subtotal - (float) ($data['discount'] ?? 0) + (float) ($data['tax'] ?? 0) + (float) ($data['service_charge'] ?? 0) + (float) ($data['other_charges'] ?? 0), 2);
        $targetDeposit = array_key_exists('deposit_received', $data) ? (float) $data['deposit_received'] : (float) $reservation->deposit_received;
        unset($data['deposit_received']);

        DB::transaction(function () use ($reservation, $data, $roomId, $room, $nights, $subtotal, $total, $targetDeposit) {
            $reservation->update(array_merge($data, [
                'room_id' => $roomId,
                'room_type_id' => $room?->room_type_id ?? $reservation->room_type_id,
                'nights' => $nights,
                'subtotal' => $subtotal,
                'total' => max(0, $total),
                'balance' => max(0, $total - (float) $reservation->deposit_received),
            ]));

            $depositDelta = round($targetDeposit - (float) $reservation->deposit_received, 2);
            if ($depositDelta > 0) {
                app(HotelDepositService::class)->receive($reservation, $depositDelta, 'cash', null, null, 'Deposit adjustment during reservation amendment.');
            } elseif ($depositDelta < 0) {
                app(HotelDepositService::class)->refund($reservation, abs($depositDelta), null, 'AMEND-'.$reservation->reservation_number, 'Deposit reduction during reservation amendment.');
            }

            $this->recordEvent($reservation, 'reservation.amended', 'Reservation amended', 'Dates, rates or guest requirements were updated.');
        });

        return back()->with('success', 'Reservation updated and availability revalidated.');
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        $this->assertReservationScope($reservation);
        $data = $request->validate([
            'status' => 'required|in:reserved,confirmed,cancelled,no_show',
            'reason' => 'nullable|string|max:1000|required_if:status,cancelled,no_show',
        ]);

        $current = (string) $reservation->status;
        abort_if(in_array($current, ['checked_in', 'completed'], true), 422, 'Checked-in or completed reservations cannot be changed here.');
        abort_if($data['status'] === 'no_show' && $reservation->arrival_date?->isFuture(), 422, 'A future arrival cannot be marked as no-show.');

        $payload = [
            'status' => $data['status'],
            'confirmed_by' => $data['status'] === 'confirmed' ? auth()->id() : $reservation->confirmed_by,
            'cancelled_by' => $data['status'] === 'cancelled' ? auth()->id() : null,
            'cancellation_reason' => $data['status'] === 'cancelled' ? $data['reason'] : null,
            'cancelled_at' => $data['status'] === 'cancelled' ? now() : null,
            'no_show_at' => $data['status'] === 'no_show' ? now() : null,
        ];

        $reservation->update($payload);
        $this->recordEvent(
            $reservation,
            'reservation.status_changed',
            'Reservation status changed',
            ucfirst(str_replace('_', ' ', $current)).' to '.ucfirst(str_replace('_', ' ', $data['status'])).(! empty($data['reason']) ? ': '.$data['reason'] : '.')
        );

        return back()->with('success', 'Reservation status updated.');
    }

    private function rateRestrictionError(int $ratePlanId, $arrival, $departure, int $nights): ?string
    {
        $day = strtolower($arrival->format('D'));
        $restrictions = HotelRateRestriction::query()->where('company_id', auth()->user()->company_id)->where('rate_plan_id', $ratePlanId)
            ->whereDate('start_date', '<=', $departure->toDateString())->whereDate('end_date', '>=', $arrival->toDateString())->get();
        foreach ($restrictions as $restriction) {
            $days = array_filter(array_map('trim', explode(',', strtolower((string) $restriction->applicable_days))));
            if ($days && ! in_array($day, $days, true)) { continue; }
            if ($restriction->stop_sell) { return 'This rate plan is closed for sale during the selected dates.'; }
            if ($restriction->closed_to_arrival && $arrival->betweenIncluded($restriction->start_date, $restriction->end_date)) { return 'Arrival is closed for this rate plan on the selected date.'; }
            if ($restriction->closed_to_departure && $departure->betweenIncluded($restriction->start_date, $restriction->end_date)) { return 'Departure is closed for this rate plan on the selected date.'; }
            if ($restriction->min_stay && $nights < $restriction->min_stay) { return 'This rate plan requires at least '.$restriction->min_stay.' nights.'; }
            if ($restriction->max_stay && $nights > $restriction->max_stay) { return 'This rate plan permits no more than '.$restriction->max_stay.' nights.'; }
        }
        return null;
    }

    private function assertReservationScope(Reservation $reservation): void
    {
        abort_unless((int) $reservation->company_id === (int) auth()->user()->company_id, 404);
        $propertyId = $this->currentPropertyId();
        abort_unless(! $propertyId || (int) $reservation->property_id === $propertyId, 404);
    }

    private function recordEvent(Reservation $reservation, string $eventType, string $title, string $description): void
    {
        HotelOperationalEvent::create([
            'company_id' => $reservation->company_id,
            'property_id' => $reservation->property_id,
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'room_id' => $reservation->room_id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'meta' => ['status' => $reservation->status],
            'created_by' => auth()->id(),
        ]);
    }

    private function currentPropertyId(): ?int
    {
        $companyId = auth()->user()->company_id;
        return HotelPropertyContext::propertyId((int) $companyId);
    }
}
