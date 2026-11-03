<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CustomerReceivables
{
    public function outstandingBalance(
        int $customerId,
        ?Customer $customer,
        Builder $salesQuery,
        ?int $excludeSaleId = null
    ): float {
        if ($customerId <= 0 || !Schema::hasTable('sales') || !Schema::hasColumn('sales', 'balance')) {
            return 0.0;
        }

        $customerSales = (clone $salesQuery)->where('customer_id', $customerId);
        if (Schema::hasColumn('sales', 'order_status')) {
            $customerSales->where(function ($query) {
                $query->whereNull('order_status')
                    ->orWhere('order_status', '!=', 'draft');
            });
        }

        $openingSale = null;
        $openingBalance = max(0, (float) ($customer?->balance ?? 0));
        if (Schema::hasColumn('sales', 'invoice_no')) {
            $openingSale = (clone $customerSales)
                ->where('invoice_no', 'OPENING-BAL-' . $customerId)
                ->latest('id')
                ->first(['id', 'balance']);

            if ($openingSale) {
                $openingBalance = max(0, (float) ($openingSale->balance ?? 0));
            }
        }

        $otherSales = (clone $customerSales)->where('balance', '>', 0);
        if ($openingSale) {
            $otherSales->where('id', '!=', $openingSale->id);
        }
        if ($excludeSaleId) {
            $otherSales->where('id', '!=', $excludeSaleId);
            if ($openingSale && (int) $openingSale->id === $excludeSaleId) {
                $openingBalance = 0.0;
            }
        }

        return round($openingBalance + (float) $otherSales->sum('balance'), 2);
    }
}
