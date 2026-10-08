<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelBookingSource;
use App\Models\HotelCorporateAccount;
use App\Models\HotelGroupBooking;
use App\Models\HotelGroupRoomAllocation;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Services\RoomAvailabilityService;
use App\Support\HotelPropertyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HotelCommercialController extends Controller
{
    public function corporateAccounts(Request $request)
    {
        [$companyId, $propertyId] = $this->context();
        $accounts = HotelCorporateAccount::query()
            ->withSum(['folios as outstanding_balance' => fn ($query) => $query->whereIn('status', ['open', 'city_ledger'])], 'balance')
            ->withCount(['reservations', 'folios'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where(fn ($scope) => $scope->where('property_id', $propertyId)->orWhereNull('property_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $query->where(fn ($scope) => $scope->where('company_name', 'like', "%{$term}%")->orWhere('account_code', 'like', "%{$term}%")->orWhere('billing_email', 'like', "%{$term}%"));
            })
            ->orderBy('company_name')
            ->paginate(25)
            ->withQueryString();

        $cityLedgers = \App\Models\GuestFolio::query()
            ->with(['customer', 'corporateAccount'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->where('status', 'city_ledger')
            ->latest('id')->limit(50)->get();

        return view('hotel.business.corporate_accounts', compact('accounts', 'cityLedgers'));
    }

    public function storeCorporateAccount(Request $request)
    {
        [$companyId, $propertyId] = $this->context(true);
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:40', Rule::unique('hotel_corporate_accounts')->where(fn ($query) => $query->where('company_id', $companyId))],
            'company_name' => 'required|string|max:191', 'tax_id' => 'nullable|string|max:80',
            'billing_contact' => 'nullable|string|max:191', 'billing_email' => 'nullable|email|max:191', 'billing_phone' => 'nullable|string|max:60',
            'billing_address' => 'nullable|string|max:1000', 'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0|max:365', 'negotiated_discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);
        HotelCorporateAccount::create([...$data, 'company_id' => $companyId, 'property_id' => $propertyId, 'status' => 'active', 'created_by' => auth()->id()]);

        return back()->with('success', 'Corporate account created with credit controls.');
    }

    public function toggleCorporateAccount(HotelCorporateAccount $account)
    {
        $this->assertCompany($account);
        $account->update(['status' => $account->status === 'active' ? 'suspended' : 'active']);

        return back()->with('success', 'Corporate account status updated.');
    }

    public function bookingSources(Request $request)
    {
        [$companyId, $propertyId] = $this->context();
        $sources = HotelBookingSource::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where(fn ($scope) => $scope->where('property_id', $propertyId)->orWhereNull('property_id')))
            ->orderBy('name')->get();

        $performance = Reservation::query()
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->selectRaw('booking_source_id, COALESCE(source, "direct") as legacy_source, COUNT(*) as reservations_count, SUM(COALESCE(total,0)) as gross_value')
            ->groupBy('booking_source_id', 'legacy_source')->get();

        return view('hotel.business.booking_sources', compact('sources', 'performance'));
    }

    public function storeBookingSource(Request $request)
    {
        [$companyId, $propertyId] = $this->context(true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('hotel_booking_sources')->where(fn ($query) => $query->where('company_id', $companyId))],
            'name' => 'required|string|max:191', 'source_type' => 'required|in:direct,ota,agent,gds,corporate,government,event,other',
            'commission_type' => 'required|in:none,percent,fixed', 'commission_value' => 'nullable|numeric|min:0',
            'settlement_days' => 'nullable|integer|min:0|max:365', 'contact_name' => 'nullable|string|max:191',
            'contact_email' => 'nullable|email|max:191', 'contact_phone' => 'nullable|string|max:60',
        ]);
        HotelBookingSource::create([...$data, 'company_id' => $companyId, 'property_id' => $propertyId, 'is_active' => true, 'created_by' => auth()->id()]);

        return back()->with('success', 'Managed booking source added.');
    }

    public function toggleBookingSource(HotelBookingSource $source)
    {
        $this->assertCompany($source);
        $source->update(['is_active' => ! $source->is_active]);

        return back()->with('success', 'Booking source status updated.');
    }

    public function groupBookings(Request $request)
    {
        [$companyId, $propertyId] = $this->context();
        $groups = HotelGroupBooking::query()
            ->with(['allocations.roomType', 'allocations.room', 'corporateAccount', 'bookingSource'])
            ->withCount('allocations')
            ->where('company_id', $companyId)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('arrival_date')->paginate(20)->withQueryString();
        $corporateAccounts = HotelCorporateAccount::query()->where('company_id', $companyId)->where('status', 'active')->orderBy('company_name')->get();
        $bookingSources = HotelBookingSource::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $roomTypes = HotelRoomType::query()->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('is_active', true)->orderBy('name')->get();
        $rooms = HotelRoom::query()->with('type')->where('company_id', $companyId)->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))->where('is_active', true)->orderBy('room_number')->get();

        return view('hotel.business.group_bookings', compact('groups', 'corporateAccounts', 'bookingSources', 'roomTypes', 'rooms'));
    }

    public function storeGroupBooking(Request $request)
    {
        [$companyId, $propertyId] = $this->context(true);
        $data = $request->validate([
            'group_name' => 'required|string|max:191', 'arrival_date' => 'required|date', 'departure_date' => 'required|date|after:arrival_date',
            'rooms_requested' => 'required|integer|min:1|max:1000', 'adults' => 'required|integer|min:1|max:5000', 'children' => 'nullable|integer|min:0|max:5000',
            'corporate_account_id' => ['nullable', Rule::exists('hotel_corporate_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('status', 'active'))],
            'booking_source_id' => ['nullable', Rule::exists('hotel_booking_sources', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true))],
            'estimated_total' => 'nullable|numeric|min:0', 'deposit_required' => 'nullable|numeric|min:0', 'release_date' => 'nullable|date|before_or_equal:arrival_date', 'notes' => 'nullable|string|max:2000',
        ]);
        HotelGroupBooking::create([...$data, 'company_id' => $companyId, 'property_id' => $propertyId, 'group_code' => 'GRP-'.now()->format('ymd').'-'.strtoupper(Str::random(4)), 'status' => 'tentative', 'created_by' => auth()->id()]);

        return back()->with('success', 'Group master created. Add room-type allocations and rooming-list details below.');
    }

    public function storeGroupAllocation(Request $request, HotelGroupBooking $group)
    {
        $this->assertCompany($group);
        abort_unless((int) $group->property_id === (int) HotelPropertyContext::propertyId((int) $group->company_id), 404);
        $data = $request->validate([
            'room_type_id' => ['required', Rule::exists('hotel_room_types', 'id')->where(fn ($query) => $query->where('company_id', $group->company_id)->where('property_id', $group->property_id))],
            'room_id' => ['nullable', Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $group->company_id)->where('property_id', $group->property_id))],
            'guest_name' => 'nullable|string|max:191', 'rooms' => 'required|integer|min:1|max:500', 'nightly_rate' => 'required|numeric|min:0', 'notes' => 'nullable|string|max:1000',
        ]);

        $alreadyAllocated = (int) $group->allocations()->sum('rooms');
        $requestedRooms = ! empty($data['room_id']) ? 1 : (int) $data['rooms'];
        abort_if($alreadyAllocated + $requestedRooms > (int) $group->rooms_requested, 422, 'This allocation exceeds the group master room commitment.');

        DB::transaction(function () use ($data, $group) {
            $reservation = null;
            if (! empty($data['room_id'])) {
                $room = HotelRoom::query()->where('company_id', $group->company_id)->findOrFail((int) $data['room_id']);
                abort_unless((int) $room->room_type_id === (int) $data['room_type_id'], 422, 'Selected room does not match the allocated room type.');
                abort_unless(RoomAvailabilityService::isRoomAvailable($room->id, $group->arrival_date->toDateString(), $group->departure_date->toDateString()), 422, 'Selected room is unavailable for the group dates.');
                $nights = max(1, $group->arrival_date->diffInDays($group->departure_date));
                $reservation = Reservation::create([
                    'company_id' => $group->company_id, 'property_id' => $group->property_id, 'reservation_number' => strtoupper(Str::random(8)),
                    'room_type_id' => $data['room_type_id'], 'room_id' => $room->id, 'arrival_date' => $group->arrival_date,
                    'departure_date' => $group->departure_date, 'nights' => $nights, 'adults' => 1, 'children' => 0,
                    'nightly_rate' => $data['nightly_rate'], 'subtotal' => $nights * $data['nightly_rate'], 'total' => $nights * $data['nightly_rate'],
                    'balance' => $nights * $data['nightly_rate'], 'status' => 'reserved', 'source' => $group->bookingSource?->name ?? 'group',
                    'booking_source_id' => $group->booking_source_id, 'corporate_account_id' => $group->corporate_account_id, 'group_booking_id' => $group->id,
                    'internal_notes' => trim('Group '.$group->group_code.' '.($data['guest_name'] ?? '')), 'created_by' => auth()->id(),
                ]);
            }

            HotelGroupRoomAllocation::create([...$data, 'rooms' => $reservation ? 1 : $data['rooms'], 'company_id' => $group->company_id, 'property_id' => $group->property_id, 'group_booking_id' => $group->id, 'reservation_id' => $reservation?->id, 'status' => $reservation ? 'reserved' : 'blocked', 'created_by' => auth()->id()]);
        });

        return back()->with('success', 'Group room allocation added.');
    }

    public function updateGroupStatus(Request $request, HotelGroupBooking $group)
    {
        $this->assertCompany($group);
        abort_unless((int) $group->property_id === (int) HotelPropertyContext::propertyId((int) $group->company_id), 404);
        $data = $request->validate(['status' => 'required|in:tentative,confirmed,in_house,completed,cancelled']);
        $group->update($data);

        return back()->with('success', 'Group status updated.');
    }

    private function context(bool $required = false): array
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = HotelPropertyContext::propertyId($companyId);
        abort_if($required && ! $propertyId, 422, 'No active hotel property is configured for this branch.');

        return [$companyId, $propertyId];
    }

    private function assertCompany(object $model): void
    {
        abort_unless((int) $model->company_id === (int) auth()->user()->company_id, 404);
    }
}
