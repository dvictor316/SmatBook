<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CustomerReceivables
{
    public function creditLimitWarning(Customer $customer, int $companyId, float $newCredit, ?int $excludeSaleId = null): ?array
    {
        $limit = round((float) $customer->credit_limit, 2);
        if ($limit <= 0 || $newCredit <= 0) {
            return null;
        }

        $sales = Sale::withoutGlobalScope('company_id')
            ->when($companyId > 0 && Schema::hasColumn('sales', 'company_id'),
                fn ($query) => $query->where(function ($owner) use ($companyId) {
                    $owner->where('sales.company_id', $companyId);
                    if (Schema::hasColumn('sales', 'user_id')) {
                        $owner->orWhere(fn ($legacy) => $legacy->whereNull('sales.company_id')
                            ->whereIn('sales.user_id', \App\Models\User::query()->select('id')->where('company_id', $companyId)));
                    }
                }),
                fn ($query) => $query->where('sales.user_id', auth()->id()));
        $outstanding = $this->outstandingBalance((int) $customer->id, $customer, $sales, $excludeSaleId, true);
        $projected = round($outstanding + $newCredit, 2);
        if ($projected <= $limit) {
            return null;
        }

        return [
            'customer' => $customer->customer_name ?? $customer->name ?? 'Selected customer',
            'credit_limit' => $limit,
            'current_outstanding' => $outstanding,
            'new_credit' => $newCredit,
            'projected_outstanding' => $projected,
            'excess' => round($projected - $limit, 2),
            'blocked' => true,
        ];
    }

    public function outstandingBalance(
        int $customerId,
        ?Customer $customer,
        Builder $salesQuery,
        ?int $excludeSaleId = null,
        bool $lockForUpdate = false
    ): float {
        if ($customerId <= 0) {
            return 0.0;
        }

        if (! $customer) {
            $customer = new Customer();
            $customer->setAttribute('id', $customerId);
        }

        return $this->outstandingBalances([$customer], $salesQuery, $excludeSaleId, $lockForUpdate)[$customerId] ?? 0.0;
    }

    /**
     * Return customer receivables in batches for invoice forms and other lists.
     *
     * @param iterable<Customer> $customers
     * @return array<int, float>
     */
    public function outstandingBalances(
        iterable $customers,
        Builder $salesQuery,
        ?int $excludeSaleId = null,
        bool $lockForUpdate = false
    ): array {
        $customersById = collect($customers)
            ->filter(fn ($customer) => $customer instanceof Customer && (int) $customer->id > 0)
            ->keyBy(fn (Customer $customer) => (int) $customer->id);

        if ($customersById->isEmpty()) {
            return [];
        }

        $balances = $customersById->mapWithKeys(fn (Customer $customer, $id) => [(int) $id => 0.0]);
        if (
            ! Schema::hasTable('sales')
            || ! Schema::hasColumn('sales', 'customer_id')
            || ! Schema::hasColumn('sales', 'balance')
        ) {
            return $balances->all();
        }

        $customerIds = $customersById->keys();
        $customerSales = (clone $salesQuery)->whereIn('customer_id', $customerIds);
        if (Schema::hasColumn('sales', 'order_status')) {
            $customerSales->where(function ($query) {
                $query->whereNull('order_status')
                    ->orWhere('order_status', '!=', 'draft');
            });
        }

        $openingSalesByCustomer = collect();
        if (Schema::hasColumn('sales', 'invoice_no')) {
            $openingSalesQuery = (clone $customerSales)
                ->where('invoice_no', 'like', 'OPENING-BAL-%')
                ->orderBy('id');
            if ($lockForUpdate) {
                $openingSalesQuery->lockForUpdate();
            }
            $openingSalesByCustomer = $openingSalesQuery
                ->get(['id', 'customer_id', 'balance'])
                ->groupBy('customer_id')
                ->map(fn ($openingSales) => $openingSales->last());
        }

        $otherSales = (clone $customerSales)->where('balance', '>', 0);
        $openingSaleIds = $openingSalesByCustomer->pluck('id')->all();
        if ($openingSaleIds !== []) {
            $otherSales->whereNotIn('id', $openingSaleIds);
        }
        if ($excludeSaleId) {
            $otherSales->where('id', '!=', $excludeSaleId);
        }

        if ($lockForUpdate) {
            $otherBalances = $otherSales
                ->lockForUpdate()
                ->get(['id', 'customer_id', 'balance'])
                ->groupBy('customer_id')
                ->map(fn ($sales) => (float) $sales->sum('balance'));
        } else {
            $otherBalances = $otherSales
                ->select('customer_id')
                ->selectRaw('SUM(balance) as outstanding_balance')
                ->groupBy('customer_id')
                ->pluck('outstanding_balance', 'customer_id');
        }

        foreach ($customersById as $customerId => $customer) {
            $openingSale = $openingSalesByCustomer->get((int) $customerId);
            $openingBalance = max(0, (float) ($openingSale->balance ?? $customer->balance ?? 0));
            if ($excludeSaleId && $openingSale && (int) $openingSale->id === $excludeSaleId) {
                $openingBalance = 0.0;
            }

            $balances[(int) $customerId] = round(
                $openingBalance + (float) ($otherBalances[$customerId] ?? 0),
                2
            );
        }

        return $balances->all();
    }
}
