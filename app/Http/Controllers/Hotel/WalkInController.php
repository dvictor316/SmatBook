<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stay;
use App\Models\GuestFolio;
use App\Models\Customer;
use App\Models\HotelOperationalEvent;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Services\Hotel\HotelFolioService;
use App\Support\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalkInController extends Controller
{
    public function create()
    {
        $companyId = (int) auth()->user()->company_id;
        $property = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->first();

        $rooms = HotelRoom::query()
            ->with('type')
            ->where('company_id', $companyId)
            ->when($property?->id, fn ($q) => $q->where('property_id', $property->id))
            ->where('is_active', true)
            ->where('operational_status', 'available')
            ->orderBy('room_number')
            ->get();

        $roomTypes = HotelRoomType::query()
            ->where('company_id', $companyId)
            ->when($property?->id, fn ($q) => $q->where('property_id', $property->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $guests = Customer::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'customer_name', 'phone', 'email']);

        return view('hotel.walkin.create', compact('property', 'rooms', 'roomTypes', 'guests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:hotel_rooms,id',
            'customer_id' => 'nullable|exists:customers,id',
            'guest_name' => 'nullable|required_without:customer_id|string|max:255',
            'guest_phone' => 'nullable|string|max:60',
            'guest_email' => 'nullable|email|max:255',
            'guest_address' => 'nullable|string|max:500',
            'expected_checkout_at' => 'required|date|after:now',
            'adults' => 'nullable|integer|min:1|max:20',
            'children' => 'nullable|integer|min:0|max:20',
            'agreed_rate' => 'nullable|numeric|min:0',
            'opening_deposit' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,pos,card,other',
            'note' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $companyId = (int) auth()->user()->company_id;
            $propertyId = HotelProperty::where('company_id', $companyId)
                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->value('id');

            if (!$propertyId) {
                throw new \RuntimeException('No active hotel property found for current branch.');
            }

            $room = HotelRoom::query()
                ->with('type')
                ->where('company_id', $companyId)
                ->where('property_id', $propertyId)
                ->where('is_active', true)
                ->where('operational_status', 'available')
                ->lockForUpdate()
                ->findOrFail((int) $data['room_id']);

            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                $customerPayload = [
                    'company_id' => $companyId,
                    'customer_name' => $data['guest_name'],
                    'email' => $data['guest_email'] ?? null,
                    'phone' => $data['guest_phone'] ?? null,
                    'address' => $data['guest_address'] ?? null,
                    'user_id' => auth()->id(),
                ];

                if (Schema::hasColumn('customers', 'status')) {
                    $customerPayload['status'] = 'active';
                }
                if (Schema::hasColumn('customers', 'branch_id')) {
                    $customerPayload['branch_id'] = auth()->user()->branch_id;
                }
                if (Schema::hasColumn('customers', 'branch_name')) {
                    $customerPayload['branch_name'] = auth()->user()->branch_name ?? null;
                }

                $customerId = Customer::create($customerPayload)->id;
            }

            $stay = Stay::create([
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'customer_id' => $customerId,
                'room_id' => $room->id,
                'checkin_at' => now(),
                'expected_checkout_at' => $data['expected_checkout_at'],
                'agreed_rate' => (float) ($data['agreed_rate'] ?? $room->type?->base_rate ?? 0),
                'adults' => (int) ($data['adults'] ?? 1),
                'children' => (int) ($data['children'] ?? 0),
                'status' => 'checked_in',
                'checked_in_by' => auth()->id(),
            ]);

            HotelRoom::where('id', $room->id)->where('company_id', $companyId)->update([
                'operational_status' => 'occupied'
            ]);

            $folio = GuestFolio::create([
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'stay_id' => $stay->id,
                'customer_id' => $customerId,
                'folio_number' => 'FOLIO-'.now()->format('Ymd').'-'. $stay->id,
                'opening_deposit' => (float) ($data['opening_deposit'] ?? 0),
                'status' => 'open'
            ]);

            if ((float) ($data['opening_deposit'] ?? 0) > 0) {
                $deposit = app(HotelFolioService::class)->postPayment($folio, [
                    'description' => 'Walk-in opening deposit',
                    'amount' => (float) $data['opening_deposit'],
                    'type' => 'deposit_applied',
                    'service_code' => 'WALKIN_DEPOSIT',
                    'service_date' => now()->toDateString(),
                    'source_type' => self::class,
                    'source_id' => $stay->id,
                    'posting_key' => 'walkin:deposit:' . $folio->id,
                    'posted_by' => auth()->id(),
                    'meta' => [
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'note' => $data['note'] ?? null,
                    ],
                ]);

                LedgerService::postHotelFolioPayment(
                    $deposit,
                    $folio,
                    null,
                    auth()->user()->branch_id,
                    auth()->user()->branch_name ?? null
                );
            }

            HotelOperationalEvent::create([
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'stay_id' => $stay->id,
                'customer_id' => $customerId,
                'room_id' => $room->id,
                'event_type' => 'walkin.checked_in',
                'title' => 'Walk-in checked in',
                'description' => 'Walk-in guest checked in from reception.',
                'meta' => ['note' => $data['note'] ?? null, 'deposit' => (float) ($data['opening_deposit'] ?? 0)],
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('hotel.folios.show', $folio)->with('success','Walk-in created and checked-in');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
