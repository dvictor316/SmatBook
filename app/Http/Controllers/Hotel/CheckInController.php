<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\GuestFolio;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelOperationalEvent;
use App\Models\HotelRoom;
use App\Services\Hotel\HotelFolioService;
use App\Support\LedgerService;
use App\Services\Hotel\HotelDepositService;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function __construct(
        private readonly HotelFolioService $folioService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $query = Reservation::query()
            ->with(['customer', 'room', 'roomType'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->orderBy('arrival_date');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('reservation_number', 'like', '%' . $term . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($term) {
                        $customerQuery->where('customer_name', 'like', '%' . $term . '%')
                            ->orWhere('phone', 'like', '%' . $term . '%');
                    });
            });
        }

        if ($request->filled('arrival')) {
            $query->whereDate('arrival_date', (string) $request->query('arrival'));
        }

        $reservations = $query->paginate(20)->withQueryString();
        return view('hotel.checkin.index', compact('reservations'));
    }

    public function checkoutDesk(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $stays = Stay::query()
            ->with(['customer', 'room', 'reservation'])
            ->where('company_id', $companyId)
            ->where('status', 'checked_in')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $selectedStay = null;
        if ($request->filled('stay_id')) {
            $selectedStay = Stay::query()
                ->with(['customer', 'room', 'reservation'])
                ->where('company_id', $companyId)
                ->where('status', 'checked_in')
                ->find((int) $request->query('stay_id'));
        }

        $selectedFolio = $selectedStay
            ? GuestFolio::query()
                ->where('company_id', $companyId)
                ->where('stay_id', $selectedStay->id)
                ->latest('id')
                ->first()
            : null;

        $folioItems = $selectedFolio
            ? \App\Models\FolioItem::query()->where('folio_id', $selectedFolio->id)->latest('id')->get()
            : collect();
        $paymentAccounts = Account::query()->where('company_id', $companyId)->where('type', Account::TYPE_ASSET)->where('is_active', true)->orderBy('name')->get();

        return view('hotel.checkout.index', compact('stays', 'selectedStay', 'selectedFolio', 'folioItems', 'paymentAccounts'));
    }

    public function checkin(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);

        DB::beginTransaction();
        try {
            $reservation = Reservation::query()
                ->where('company_id', auth()->user()->company_id)
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            if (! in_array((string) $reservation->status, ['reserved', 'confirmed'], true)) {
                throw new \RuntimeException('Only a reserved or confirmed booking can be checked in.');
            }
            if (! $reservation->room_id) {
                throw new \RuntimeException('Assign an available room before check-in.');
            }
            if (Stay::query()->where('company_id', $reservation->company_id)->where('reservation_id', $reservation->id)->where('status', 'checked_in')->exists()) {
                throw new \RuntimeException('This reservation already has an active stay.');
            }

            $room = HotelRoom::query()
                ->where('company_id', $reservation->company_id)
                ->where('property_id', $reservation->property_id)
                ->lockForUpdate()
                ->findOrFail((int) $reservation->room_id);

            if (! \App\Services\RoomAvailabilityService::isRoomAvailable(
                $room->id,
                $reservation->arrival_date->toDateString(),
                $reservation->departure_date->toDateString(),
                $reservation->id
            )) {
                throw new \RuntimeException('The assigned room is no longer available for this stay.');
            }
            if ((string) $room->housekeeping_status !== 'clean' && ! $this->canOverrideDirtyCheckin()) {
                throw new \RuntimeException('Room Not Ready: housekeeping must mark the room clean before check-in.');
            }

            $stay = Stay::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'room_id' => $reservation->room_id,
                'checkin_at' => now(),
                'expected_checkout_at' => $reservation->departure_date,
                'agreed_rate' => $reservation->nightly_rate,
                'status' => 'checked_in'
            ]);

            $room->update(['operational_status' => 'occupied']);

            $folio = GuestFolio::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'folio_number' => 'FOLIO-'.now()->format('Ymd').'-'. $stay->id,
                'opening_deposit' => $reservation->deposit_received ?? 0,
                'corporate_account_id' => $reservation->corporate_account_id,
                'group_booking_id' => $reservation->group_booking_id,
                'due_date' => $reservation->corporateAccount ? now()->addDays((int) $reservation->corporateAccount->payment_terms_days)->toDateString() : null,
                'total_payments' => 0,
                'total_charges' => 0,
                'balance' => 0,
                'status' => 'open'
            ]);

            $reservation->update(['status' => 'checked_in','checkin_id' => $stay->id]);

            HotelOperationalEvent::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'reservation_id' => $reservation->id,
                'stay_id' => $stay->id,
                'customer_id' => $reservation->customer_id,
                'room_id' => $reservation->room_id,
                'event_type' => 'stay.checked_in',
                'title' => 'Checked in',
                'description' => 'Guest checked in successfully.',
                'meta' => ['reservation_number' => $reservation->reservation_number],
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('hotel.folios.show', $folio)->with('success','Checked in successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function checkout(Stay $stay)
    {
        abort_unless($stay->company_id == auth()->user()->company_id, 404);

        $validated = request()->validate([
            'settlement_method' => 'nullable|in:cash,transfer,pos,split,corporate_credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'deposit_account_id' => 'nullable|integer',
            'payment_account_id' => 'nullable|integer',
            'split.cash' => 'nullable|numeric|min:0',
            'split.transfer' => 'nullable|numeric|min:0',
            'split.pos' => 'nullable|numeric|min:0',
            'split_accounts.cash' => 'nullable|integer',
            'split_accounts.transfer' => 'nullable|integer',
            'split_accounts.pos' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $folio = GuestFolio::query()
                ->where('company_id', $stay->company_id)
                ->where('stay_id', $stay->id)
                ->whereIn('status', ['open', 'city_ledger'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$folio) {
                throw new \RuntimeException('No open folio found for this stay.');
            }

            $folio = $this->folioService->recalculate($folio);
            $balance = round((float) ($folio->balance ?? 0), 2);

            $depositAlreadyApplied = (bool) \App\Models\FolioItem::query()
                ->where('folio_id', $folio->id)
                ->where('type', 'deposit_applied')
                ->exists();

            if (!$depositAlreadyApplied && $balance > 0 && (float) ($folio->opening_deposit ?? 0) > 0) {
                $depositApply = round(min((float) $folio->opening_deposit, $balance), 2);

                if ($depositApply > 0) {
                    $depositItem = $this->folioService->postPayment($folio, [
                        'description' => 'Deposit applied at checkout',
                        'amount' => $depositApply,
                        'type' => 'deposit_applied',
                        'service_code' => 'DEPOSIT_APPLIED',
                        'service_date' => now()->toDateString(),
                        'source_type' => self::class,
                        'source_id' => $stay->id,
                        'posting_key' => 'checkout:deposit:' . $folio->id,
                        'posted_by' => auth()->id(),
                    ]);

                    [$branchId, $branchName] = $this->resolveBranchContext();

                    app(HotelDepositService::class)->postApplication($depositItem, $folio);

                    $folio = $this->folioService->recalculate($folio);
                    $balance = round((float) ($folio->balance ?? 0), 2);
                }
            }

            $settlementMethod = (string) ($validated['settlement_method'] ?? 'cash');
            $paidAmount = round((float) ($validated['paid_amount'] ?? 0), 2);

            if ($settlementMethod === 'corporate_credit') {
                $paidAmount = 0.0;
            }

            if ($settlementMethod === 'split') {
                $split = $validated['split'] ?? [];
                $paidAmount = round(
                    (float) ($split['cash'] ?? 0)
                    + (float) ($split['transfer'] ?? 0)
                    + (float) ($split['pos'] ?? 0),
                    2
                );
            }

            if ($paidAmount > $balance) {
                throw new \RuntimeException('Payment cannot exceed the outstanding folio balance. Record any excess separately as a guest advance.');
            }

            if ($paidAmount > 0) {
                [$branchId, $branchName] = $this->resolveBranchContext();
                $payments = $settlementMethod === 'split'
                    ? collect($validated['split'] ?? [])->filter(fn ($amount) => (float) $amount > 0)
                    : collect([$settlementMethod => $paidAmount]);
                foreach ($payments as $method => $amount) {
                    $accountId = $settlementMethod === 'split'
                        ? (int) data_get($validated, 'split_accounts.'.$method, 0)
                        : (int) ($validated['payment_account_id'] ?? $validated['deposit_account_id'] ?? 0);
                    $cashItem = $this->folioService->postPayment($folio, [
                        'description' => 'Checkout settlement - '.strtoupper((string) $method), 'amount' => (float) $amount,
                        'type' => 'payment', 'service_code' => strtoupper((string) $method), 'service_date' => now()->toDateString(),
                        'source_type' => self::class, 'source_id' => $stay->id, 'payment_account_id' => $accountId ?: null,
                        'posting_key' => 'checkout:payment:'.$folio->id.':'.$method, 'posted_by' => auth()->id(),
                    ]);
                    LedgerService::postHotelFolioPayment($cashItem, $folio, $accountId ?: null, $branchId, $branchName);
                }
            }

            $folio = $this->folioService->recalculate($folio);
            $balance = round((float) ($folio->balance ?? 0), 2);

            if ($balance > 0 && $settlementMethod !== 'corporate_credit') {
                throw new \RuntimeException('Outstanding folio balance remains. Complete settlement or use an approved corporate city-ledger account.');
            }

            if ($balance > 0 && $settlementMethod === 'corporate_credit') {
                $corporate = $folio->corporateAccount;
                if (! $corporate || $corporate->status !== 'active') {
                    throw new \RuntimeException('Select an active corporate account before transferring a balance to city ledger.');
                }
                $existingExposure = (float) GuestFolio::query()->where('company_id', $folio->company_id)->where('corporate_account_id', $corporate->id)->where('id', '!=', $folio->id)->whereIn('status', ['open', 'city_ledger'])->sum('balance');
                if ((float) $corporate->credit_limit > 0 && $existingExposure + $balance > (float) $corporate->credit_limit) {
                    throw new \RuntimeException('Corporate credit limit would be exceeded by this checkout.');
                }
                $folio->due_date = now()->addDays((int) $corporate->payment_terms_days)->toDateString();
            }

            $folio->update([
                'status' => $balance <= 0 ? 'closed' : 'city_ledger',
            ]);

            $stay->update(['status' => 'checked_out', 'actual_checkout_at' => now()]);
            if ($stay->room_id) {
                HotelRoom::where('id', $stay->room_id)
                    ->where('company_id', $stay->company_id)
                    ->update([
                        'operational_status' => 'available',
                        'housekeeping_status' => 'dirty',
                    ]);

                HotelHousekeepingTask::create([
                    'company_id' => $stay->company_id,
                    'property_id' => $stay->property_id,
                    'room_id' => $stay->room_id,
                    'stay_id' => $stay->id,
                    'task_type' => 'checkout_clean',
                    'status' => 'open',
                    'priority' => 'high',
                    'created_by' => auth()->id(),
                    'note' => 'Auto-created on checkout.',
                ]);
            }
            if ($stay->reservation) {
                $stay->reservation->update(['status' => 'completed','checkout_id' => $stay->id]);
            }

            HotelOperationalEvent::create([
                'company_id' => $stay->company_id,
                'property_id' => $stay->property_id,
                'reservation_id' => $stay->reservation_id,
                'stay_id' => $stay->id,
                'customer_id' => $stay->customer_id,
                'room_id' => $stay->room_id,
                'event_type' => 'stay.checked_out',
                'title' => 'Checked out',
                'description' => 'Guest checked out and room marked dirty for housekeeping.',
                'meta' => ['folio_id' => $folio->id, 'remaining_balance' => $balance],
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return back()->with('success','Checked out and folio settled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function resolveBranchContext(): array
    {
        $branchId = \App\Support\HotelPropertyContext::activeBranchId();
        $branchName = \App\Support\HotelPropertyContext::activeBranchName();

        return [$branchId, $branchName];
    }

    private function canOverrideDirtyCheckin(): bool
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        return in_array($role, ['super_admin', 'administrator', 'admin', 'manager'], true);
    }
}
