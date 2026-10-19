<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentDeletion
{
    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $sale = $payment->sale()->lockForUpdate()->first();
            Transaction::where('related_id', $payment->id)
                ->where('related_type', Payment::class)
                ->delete();

            $payment->delete();

            if ($sale) {
                $paid = (float) $sale->payments()
                    ->whereRaw("LOWER(COALESCE(status, '')) NOT IN ('failed', 'error', 'cancelled', 'canceled', 'refunded', 'pending', 'pending approval', 'rejected', 'void')")
                    ->sum('amount');
                $balance = max(0, (float) ($sale->total ?? 0) - $paid);
                $saleUpdate = [
                    'paid' => $paid,
                    'amount_paid' => $paid,
                    'balance' => $balance,
                    'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ];
                if (Schema::hasColumn('sales', 'order_status')) {
                    $saleUpdate['order_status'] = $balance <= 0 ? 'completed' : 'pending';
                }
                $sale->update($saleUpdate);

                if (
                    Schema::hasColumn('sales', 'invoice_no')
                    && Schema::hasColumn('customers', 'balance')
                    && str_starts_with((string) ($sale->invoice_no ?? ''), 'OPENING-BAL-')
                    && ! empty($sale->customer_id)
                ) {
                    $customer = Customer::query()->find($sale->customer_id);
                    if ($customer) {
                        $customer->update(['balance' => $balance]);
                    }
                }
            } elseif (! empty($payment->customer_id) && Schema::hasColumn('customers', 'balance')) {
                $customer = Customer::query()->find($payment->customer_id);
                if ($customer) {
                    $customer->update([
                        'balance' => round((float) ($customer->balance ?? 0) + (float) ($payment->amount ?? 0), 2),
                    ]);
                }
            }
        });
    }
}
