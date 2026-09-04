<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GuestFolio;
use App\Models\FolioItem;
use App\Services\Hotel\HotelFolioService;
use App\Support\LedgerService;

class FolioController extends Controller
{
    public function __construct(
        private readonly HotelFolioService $folioService
    ) {
    }

    public function index()
    {
        $folios = GuestFolio::with(['customer', 'stay.room'])
            ->where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function ($q) {
                $propertyId = \App\Models\HotelProperty::where('company_id', auth()->user()->company_id)
                    ->where('branch_id', auth()->user()->branch_id)
                    ->value('id');
                if ($propertyId) {
                    $q->where('property_id', $propertyId);
                }
            })
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

        return view('hotel.folios.show', ['folio' => $folio, 'items' => $ledgerItems]);
    }

    public function storeItem(Request $request, GuestFolio $folio)
    {
        abort_unless($folio->company_id == auth()->user()->company_id, 404);

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
        abort_unless($folio->company_id == auth()->user()->company_id, 404);

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
            'description' => $data['description'] ?: ('Hotel service: ' . str_replace('_', ' ', $data['service_type'])),
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

    public function receipt($item)
    {
        $item = FolioItem::query()
            ->with(['folio.customer', 'folio.stay.room'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail((int) $item);

        return view('SuperAdmin.hotels.receipt', ['item' => $item, 'folio' => $item->folio, 'isSuperAdminReceipt' => false]);
    }
}
