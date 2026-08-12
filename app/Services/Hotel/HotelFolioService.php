<?php

namespace App\Services\Hotel;

use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\Stay;
use Illuminate\Support\Facades\Schema;

class HotelFolioService
{
    public function getOrCreateOpenFolioForStay(Stay $stay): GuestFolio
    {
        $folio = GuestFolio::query()
            ->where('company_id', $stay->company_id)
            ->where('stay_id', $stay->id)
            ->whereIn('status', ['open', 'city_ledger'])
            ->latest('id')
            ->first();

        if ($folio) {
            return $folio;
        }

        return GuestFolio::create([
            'company_id' => $stay->company_id,
            'property_id' => $stay->property_id,
            'stay_id' => $stay->id,
            'reservation_id' => $stay->reservation_id,
            'customer_id' => $stay->customer_id,
            'folio_number' => 'FOLIO-' . now()->format('Ymd') . '-' . $stay->id,
            'opening_deposit' => 0,
            'status' => 'open',
        ]);
    }

    public function postCharge(GuestFolio $folio, array $payload): FolioItem
    {
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Charge amount must be greater than zero.');
        }

        $postingKey = trim((string) ($payload['posting_key'] ?? ''));
        if ($postingKey !== '' && Schema::hasColumn('folio_items', 'posting_key')) {
            $existing = FolioItem::query()
                ->where('company_id', $folio->company_id)
                ->where('folio_id', $folio->id)
                ->where('posting_key', $postingKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $quantity = max(0.001, (float) ($payload['quantity'] ?? 1));
        $item = FolioItem::create([
            'company_id' => $folio->company_id,
            'property_id' => $folio->property_id,
            'folio_id' => $folio->id,
            'stay_id' => $folio->stay_id,
            'reservation_id' => $folio->reservation_id,
            'description' => (string) ($payload['description'] ?? 'Hotel charge'),
            'amount' => $amount,
            'quantity' => $quantity,
            'unit_price' => round((float) ($payload['unit_price'] ?? ($amount / $quantity)), 2),
            'type' => (string) ($payload['type'] ?? 'charge'),
            'service_code' => (string) ($payload['service_code'] ?? 'OTHER_SERVICE'),
            'service_date' => $payload['service_date'] ?? now()->toDateString(),
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'payment_account_id' => $payload['payment_account_id'] ?? null,
            'posting_key' => $postingKey !== '' ? $postingKey : null,
            'posted_by' => $payload['posted_by'] ?? auth()->id(),
            'meta' => $payload['meta'] ?? null,
        ]);

        $this->recalculate($folio->fresh());

        return $item;
    }

    public function postPayment(GuestFolio $folio, array $payload): FolioItem
    {
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $postingKey = trim((string) ($payload['posting_key'] ?? ''));
        if ($postingKey !== '' && Schema::hasColumn('folio_items', 'posting_key')) {
            $existing = FolioItem::query()
                ->where('company_id', $folio->company_id)
                ->where('folio_id', $folio->id)
                ->where('posting_key', $postingKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $item = FolioItem::create([
            'company_id' => $folio->company_id,
            'property_id' => $folio->property_id,
            'folio_id' => $folio->id,
            'stay_id' => $folio->stay_id,
            'reservation_id' => $folio->reservation_id,
            'description' => (string) ($payload['description'] ?? 'Guest payment'),
            'amount' => $amount,
            'quantity' => 1,
            'unit_price' => $amount,
            'type' => (string) ($payload['type'] ?? 'payment'),
            'service_code' => (string) ($payload['service_code'] ?? 'PAYMENT'),
            'service_date' => $payload['service_date'] ?? now()->toDateString(),
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'payment_account_id' => $payload['payment_account_id'] ?? null,
            'posting_key' => $postingKey !== '' ? $postingKey : null,
            'posted_by' => $payload['posted_by'] ?? auth()->id(),
            'meta' => $payload['meta'] ?? null,
        ]);

        $this->recalculate($folio->fresh());

        return $item;
    }

    public function recalculate(GuestFolio $folio): GuestFolio
    {
        $items = FolioItem::query()->where('folio_id', $folio->id)->get();

        $chargeTypes = ['charge', 'room_night', 'service', 'pos_charge', 'adjustment'];
        $paymentTypes = ['payment', 'deposit_applied', 'refund_reversal'];

        $totalCharges = round((float) $items
            ->whereIn('type', $chargeTypes)
            ->sum('amount'), 2);

        $totalPayments = round((float) $items
            ->whereIn('type', $paymentTypes)
            ->sum('amount'), 2);

        $balance = round($totalCharges - $totalPayments, 2);

        $folio->forceFill([
            'total_charges' => $totalCharges,
            'total_payments' => $totalPayments,
            'balance' => $balance,
        ])->save();

        return $folio->fresh();
    }
}
