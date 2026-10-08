<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\Transaction;
use App\Services\Hotel\HotelFolioService;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FolioController extends Controller
{
    public function __construct(
        private readonly HotelFolioService $folioService
    ) {}

    public function index()
    {
        $propertyId = \App\Support\HotelPropertyContext::propertyId((int) auth()->user()->company_id);
        $folios = GuestFolio::with(['customer', 'stay.room'])
            ->where('company_id', auth()->user()->company_id)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->latest('id')
            ->paginate(20);

        return view('hotel.folios.index', compact('folios'));
    }

    public function show(GuestFolio $folio)
    {
        abort_unless($folio->company_id == auth()->user()->company_id, 404);
        $folio->load(['customer', 'stay.room', 'reservation']);
        $items = FolioItem::where('folio_id', $folio->id)
            ->orderBy('service_date')
            ->orderBy('id')
            ->get();

        $runningBalance = 0;
        $ledgerItems = $items->map(function ($item) use (&$runningBalance) {
            $isPayment = in_array((string) $item->type, ['payment', 'deposit_applied'], true);
            $charge = $isPayment ? 0 : (float) $item->amount;
            $payment = $isPayment ? (float) $item->amount : 0;
            $runningBalance += $charge;
            $runningBalance -= $payment;
            $item->ledger_charge = $charge;
            $item->ledger_payment = $payment;
            $item->ledger_running_balance = $runningBalance;

            return $item;
        });

        $paymentAccounts = Account::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->where('type', Account::TYPE_ASSET)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $relatedFolios = GuestFolio::query()->where('company_id', $folio->company_id)->where('stay_id', $folio->stay_id)->where('id', '!=', $folio->id)->whereIn('status', ['open', 'city_ledger'])->orderBy('id')->get();

        return view('hotel.folios.show', compact('folio', 'paymentAccounts', 'relatedFolios') + ['items' => $ledgerItems]);
    }

    public function storeItem(Request $request, GuestFolio $folio)
    {
        $this->assertOpenFolio($folio);

        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'service_code' => 'nullable|string|max:60',
            'service_date' => 'nullable|date',
        ]);

        $item = $this->folioService->postCharge($folio, [
            'description' => $data['description'],
            'amount' => (float) $data['amount'],
            'type' => 'charge',
            'service_code' => $data['service_code'] ?? 'OTHER_SERVICE',
            'service_date' => $data['service_date'] ?? now()->toDateString(),
            'source_type' => self::class,
            'source_id' => $folio->id,
            'posted_by' => auth()->id(),
        ]);

        $stay = $folio->stay;
        LedgerService::postHotelFolioCharge(
            $item,
            $folio,
            $stay?->branch_id,
            $stay?->branch_name
        );

        return redirect()
            ->route('hotel.folios.items.receipt', ['item' => $item->id, 'print' => 1])
            ->with('success', 'Item posted.');
    }

    public function postService(Request $request, GuestFolio $folio)
    {
        $this->assertOpenFolio($folio);

        $data = $request->validate([
            'service_type' => 'required|string|in:restaurant,bar,gym,spa,ticketing,room_service,laundry,minibar,conference,other',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'quantity' => 'nullable|numeric|gt:0',
            'unit_price' => 'nullable|numeric|min:0',
            'service_date' => 'nullable|date',
        ]);

        $serviceCode = strtoupper((string) $data['service_type']);
        $item = $this->folioService->postCharge($folio, [
            'description' => $data['description'] ?: ('Hotel service: '.str_replace('_', ' ', $data['service_type'])),
            'amount' => (float) $data['amount'],
            'quantity' => (float) ($data['quantity'] ?? 1),
            'unit_price' => (float) ($data['unit_price'] ?? $data['amount']),
            'type' => 'service',
            'service_code' => $serviceCode,
            'service_date' => $data['service_date'] ?? now()->toDateString(),
            'source_type' => self::class,
            'source_id' => $folio->id,
            'posted_by' => auth()->id(),
        ]);

        $stay = $folio->stay;
        LedgerService::postHotelFolioCharge(
            $item,
            $folio,
            $stay?->branch_id,
            $stay?->branch_name
        );

        return redirect()
            ->route('hotel.folios.items.receipt', ['item' => $item->id, 'print' => 1])
            ->with('success', 'Service charge posted to folio.');
    }

    public function postPayment(Request $request, GuestFolio $folio)
    {
        $this->assertOpenFolio($folio);
        $companyId = (int) auth()->user()->company_id;
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,pos,bank_transfer,mobile_money,cheque,other',
            'payment_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true)->where('type', Account::TYPE_ASSET))],
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
            'service_date' => 'nullable|date',
        ]);

        $amount = round((float) $data['amount'], 2);
        if ((float) $folio->balance > 0 && $amount - (float) $folio->balance > 0.01) {
            return back()->withErrors(['amount' => 'Payment cannot exceed the outstanding folio balance.'])->withInput();
        }

        $item = DB::transaction(function () use ($folio, $data, $amount) {
            $item = $this->folioService->postPayment($folio, [
                'description' => ($data['note'] ?? null) ?: ('Guest payment - '.str_replace('_', ' ', $data['payment_method'])),
                'amount' => $amount,
                'service_code' => 'PAYMENT_'.strtoupper($data['payment_method']),
                'service_date' => $data['service_date'] ?? now()->toDateString(),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'posting_key' => ! empty($data['reference']) ? 'payment:'.$folio->id.':'.$data['reference'] : null,
                'source_type' => self::class,
                'source_id' => $folio->id,
                'posted_by' => auth()->id(),
                'meta' => ['method' => $data['payment_method'], 'reference' => $data['reference'] ?? null],
            ]);

            $stay = $folio->stay;
            LedgerService::postHotelFolioPayment($item, $folio, $data['payment_account_id'] ?? null, $stay?->branch_id, $stay?->branch_name);

            return $item;
        });

        return redirect()->route('hotel.folios.items.receipt', ['item' => $item->id, 'print' => 1])->with('success', 'Payment posted and ledger updated.');
    }

    public function postRefund(Request $request, GuestFolio $folio)
    {
        $this->assertOpenFolio($folio);
        $companyId = (int) auth()->user()->company_id;
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true)->where('type', Account::TYPE_ASSET))],
            'reference' => 'required|string|max:120', 'reason' => 'required|string|max:500',
        ]);
        $refundable = max(0, (float) $folio->total_payments);
        abort_if((float) $data['amount'] > $refundable, 422, 'Refund cannot exceed total payments on this folio.');

        DB::transaction(function () use ($folio, $data, $companyId) {
            $item = FolioItem::create([
                'company_id' => $folio->company_id, 'property_id' => $folio->property_id, 'folio_id' => $folio->id,
                'stay_id' => $folio->stay_id, 'reservation_id' => $folio->reservation_id, 'description' => 'Refund: '.$data['reason'],
                'amount' => round((float) $data['amount'], 2), 'quantity' => 1, 'unit_price' => round((float) $data['amount'], 2),
                'type' => 'refund', 'service_code' => 'REFUND', 'service_date' => now()->toDateString(), 'payment_account_id' => $data['payment_account_id'],
                'source_type' => self::class, 'source_id' => $folio->id, 'posting_key' => 'refund:'.$folio->id.':'.$data['reference'],
                'posted_by' => auth()->id(), 'meta' => ['reference' => $data['reference'], 'reason' => $data['reason']],
            ]);
            $cash = Account::withoutGlobalScopes()->where('company_id', $companyId)->findOrFail((int) $data['payment_account_id']);
            $receivable = Account::withoutGlobalScopes()->firstOrCreate(['company_id' => $companyId, 'code' => 'AUTO-AST-AR'], ['name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET, 'sub_type' => 'Accounts Receivable', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'user_id' => auth()->id()]);
            $base = ['company_id' => $companyId, 'branch_id' => \App\Support\HotelPropertyContext::activeBranchId(), 'branch_name' => \App\Support\HotelPropertyContext::activeBranchName(), 'transaction_date' => now()->toDateString(), 'reference' => $data['reference'], 'description' => $item->description, 'transaction_type' => Transaction::TYPE_PAYMENT, 'related_id' => $item->id, 'related_type' => FolioItem::class, 'user_id' => auth()->id()];
            Transaction::create([...$base, 'account_id' => $receivable->id, 'debit' => $item->amount, 'credit' => 0]);
            Transaction::create([...$base, 'account_id' => $cash->id, 'debit' => 0, 'credit' => $item->amount]);
            $this->folioService->recalculate($folio->fresh());
        });

        return back()->with('success', 'Guest refund posted and folio balance updated.');
    }

    public function createSubFolio(Request $request, GuestFolio $folio)
    {
        $this->assertOpenFolio($folio);
        $data = $request->validate(['folio_label' => 'required|string|max:80']);
        $sequence = GuestFolio::query()->where('company_id', $folio->company_id)->where('stay_id', $folio->stay_id)->count() + 1;
        $child = GuestFolio::create([
            'company_id' => $folio->company_id, 'property_id' => $folio->property_id, 'stay_id' => $folio->stay_id,
            'reservation_id' => $folio->reservation_id, 'customer_id' => $folio->customer_id,
            'corporate_account_id' => $folio->corporate_account_id, 'group_booking_id' => $folio->group_booking_id,
            'folio_number' => $folio->folio_number.'-'.$sequence, 'folio_label' => $data['folio_label'], 'status' => 'open',
        ]);

        return redirect()->route('hotel.folios.show', $child)->with('success', 'Additional folio window created.');
    }

    public function transferItem(Request $request, FolioItem $item)
    {
        abort_unless((int) $item->company_id === (int) auth()->user()->company_id, 404);
        abort_if(in_array((string) $item->type, ['payment', 'deposit_applied', 'refund'], true), 422, 'Payments and refunds cannot be moved between folios.');
        $source = $item->folio;
        $data = $request->validate(['target_folio_id' => ['required', 'integer', Rule::exists('guest_folios', 'id')->where(fn ($query) => $query->where('company_id', $item->company_id)->where('stay_id', $source->stay_id)->whereIn('status', ['open', 'city_ledger']))]]);
        abort_if((int) $data['target_folio_id'] === (int) $source->id, 422, 'Select a different folio.');
        DB::transaction(function () use ($item, $source, $data) {
            $target = GuestFolio::query()->where('company_id', $item->company_id)->lockForUpdate()->findOrFail((int) $data['target_folio_id']);
            $item->update(['folio_id' => $target->id]);
            $this->folioService->recalculate($source->fresh());
            $this->folioService->recalculate($target->fresh());
        });

        return back()->with('success', 'Charge transferred to the selected folio window.');
    }

    public function close(GuestFolio $folio)
    {
        abort_unless((int) $folio->company_id === (int) auth()->user()->company_id, 404);
        $folio = $this->folioService->recalculate($folio);
        if (abs((float) $folio->balance) > 0.01) {
            return back()->withErrors(['folio' => 'The folio must have a zero balance before it can be closed.']);
        }
        if ((string) $folio->stay?->status === 'checked_in') {
            return back()->withErrors(['folio' => 'Check the guest out before closing the final folio.']);
        }

        $folio->update(['status' => 'closed']);

        return back()->with('success', 'Folio closed successfully.');
    }

    public function reopen(GuestFolio $folio)
    {
        abort_unless((int) $folio->company_id === (int) auth()->user()->company_id, 404);
        abort_unless((string) $folio->status === 'closed', 422, 'Only a closed folio can be reopened.');
        $folio->update(['status' => 'open']);

        return back()->with('success', 'Folio reopened for authorised corrections.');
    }

    private function assertOpenFolio(GuestFolio $folio): void
    {
        abort_unless((int) $folio->company_id === (int) auth()->user()->company_id, 404);
        abort_unless(in_array((string) $folio->status, ['open', 'city_ledger'], true), 422, 'This folio is closed. Reopen it before posting.');
    }

    public function receipt($item)
    {
        $item = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail((int) $item);

        return view('SuperAdmin.hotels.receipt', ['item' => $item, 'folio' => $item->folio, 'isSuperAdminReceipt' => false]);
    }
}
