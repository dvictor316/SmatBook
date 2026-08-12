<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\GuestFolio;
use App\Models\HotelHousekeepingTask;
use App\Models\HotelRoom;
use App\Services\Hotel\HotelFolioService;
use App\Support\LedgerService;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function __construct(
        private readonly HotelFolioService $folioService
    ) {
    }

    public function checkin(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);

        DB::beginTransaction();
        try {
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

            if ($reservation->room_id) {
                HotelRoom::where('id', $reservation->room_id)
                    ->where('company_id', $reservation->company_id)
                    ->update(['operational_status' => 'occupied']);
            }

            $folio = GuestFolio::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'folio_number' => 'FOLIO-'.now()->format('Ymd').'-'. $stay->id,
                'opening_deposit' => $reservation->deposit_received ?? 0,
                'total_payments' => 0,
                'total_charges' => 0,
                'balance' => 0,
                'status' => 'open'
            ]);

            $reservation->update(['status' => 'checked_in','checkin_id' => $stay->id]);

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
            'allow_credit' => 'nullable|boolean',
            'split.cash' => 'nullable|numeric|min:0',
            'split.transfer' => 'nullable|numeric|min:0',
            'split.pos' => 'nullable|numeric|min:0',
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

                    LedgerService::postHotelFolioPayment(
                        $depositItem,
                        $folio,
                        (int) ($validated['deposit_account_id'] ?? 0),
                        $stay->branch_id ?? null,
                        $stay->branch_name ?? null
                    );

                    $folio = $this->folioService->recalculate($folio);
                    $balance = round((float) ($folio->balance ?? 0), 2);
                }
            }

            $settlementMethod = (string) ($validated['settlement_method'] ?? 'cash');
            $allowCredit = (bool) ($validated['allow_credit'] ?? false);
            $paidAmount = round((float) ($validated['paid_amount'] ?? 0), 2);

            if ($settlementMethod === 'split') {
                $split = $validated['split'] ?? [];
                $paidAmount = round(
                    (float) ($split['cash'] ?? 0)
                    + (float) ($split['transfer'] ?? 0)
                    + (float) ($split['pos'] ?? 0),
                    2
                );
            }

            if ($paidAmount > 0) {
                $cashItem = $this->folioService->postPayment($folio, [
                    'description' => 'Checkout settlement payment',
                    'amount' => $paidAmount,
                    'type' => 'payment',
                    'service_code' => strtoupper($settlementMethod),
                    'service_date' => now()->toDateString(),
                    'source_type' => self::class,
                    'source_id' => $stay->id,
                    'payment_account_id' => (int) ($validated['deposit_account_id'] ?? 0) ?: null,
                    'posting_key' => 'checkout:payment:' . $folio->id,
                    'posted_by' => auth()->id(),
                ]);

                LedgerService::postHotelFolioPayment(
                    $cashItem,
                    $folio,
                    (int) ($validated['deposit_account_id'] ?? 0),
                    $stay->branch_id ?? null,
                    $stay->branch_name ?? null
                );
            }

            $folio = $this->folioService->recalculate($folio);
            $balance = round((float) ($folio->balance ?? 0), 2);

            if ($balance > 0 && !$allowCredit && $settlementMethod !== 'corporate_credit') {
                throw new \RuntimeException('Outstanding folio balance remains. Complete settlement or enable credit checkout.');
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
            DB::commit();
            return back()->with('success','Checked out and folio settled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
