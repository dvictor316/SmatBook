<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CollectionsHubController extends Controller
{
    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        return $query;
    }

    private function applyBranchScope($query, string $table)
    {
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        $receivables = $this->buildReceivables($request, $search, $month, $fromDate, $toDate);
        $payables = $this->buildPayables($request, $search, $month, $fromDate, $toDate);

        $customerPage = max(1, (int) $request->integer('customer_page', 1));
        $supplierPage = max(1, (int) $request->integer('supplier_page', 1));

        $customerPaginator = $this->paginateCollection($receivables, 10, $customerPage, 'customer_page', $request);
        $supplierPaginator = $this->paginateCollection($payables, 10, $supplierPage, 'supplier_page', $request);

        $summary = [
            'receivable_total' => round((float) $receivables->sum('total_due'), 2),
            'payable_total' => round((float) $payables->sum('total_due'), 2),
            'receivable_accounts' => $receivables->count(),
            'payable_accounts' => $payables->count(),
        ];

        return view('Finance.collections', [
            'customerAccounts' => $customerPaginator,
            'supplierAccounts' => $supplierPaginator,
            'summary' => $summary,
            'search' => $search,
            'month' => $month,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    private function buildReceivables(Request $request, string $search, string $month, string $fromDate, string $toDate): Collection
    {
        $query = Sale::query()->with('customer');
        $this->applyTenantScope($query, 'sales');
        $this->applyBranchScope($query, 'sales');
        $this->applyDateFilters($query, 'sales', Schema::hasColumn('sales', 'order_date') ? 'order_date' : 'created_at', $month, $fromDate, $toDate);

        if ($search !== '') {
            $hasCustomerName = Schema::hasTable('customers') && Schema::hasColumn('customers', 'customer_name');
            $hasCustomerPlainName = Schema::hasTable('customers') && Schema::hasColumn('customers', 'name');
            $query->where(function ($sub) use ($search, $hasCustomerName, $hasCustomerPlainName) {
                $sub->where('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('invoice_no', 'like', '%' . $search . '%')
                    ->orWhere('order_number', 'like', '%' . $search . '%');
                if ($hasCustomerName || $hasCustomerPlainName) {
                    $sub->orWhereHas('customer', function ($customerQuery) use ($search, $hasCustomerName, $hasCustomerPlainName) {
                        if ($hasCustomerName) {
                            $customerQuery->where('customer_name', 'like', '%' . $search . '%');
                        }
                        if ($hasCustomerPlainName) {
                            $method = $hasCustomerName ? 'orWhere' : 'where';
                            $customerQuery->{$method}('name', 'like', '%' . $search . '%');
                        }
                    });
                }
            });
        }

        return $query->get()
            ->map(function (Sale $sale) {
                $balance = round(max(0, (float) ($sale->balance ?? ((float) ($sale->total ?? 0) - (float) ($sale->amount_paid ?? 0)))), 2);
                $ageDate = $sale->order_date ?: $sale->created_at;

                return [
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer?->customer_name
                        ?? $sale->customer?->name
                        ?? $sale->customer_name
                        ?? 'Walk-in Customer',
                    'reference' => $sale->invoice_no ?: $sale->order_number ?: ('SALE-' . $sale->id),
                    'amount_due' => $balance,
                    'age_days' => $ageDate ? Carbon::parse($ageDate)->startOfDay()->diffInDays(now()->startOfDay()) : 0,
                    'transaction_date' => $ageDate ? Carbon::parse($ageDate) : null,
                ];
            })
            ->filter(fn (array $row) => $row['amount_due'] > 0)
            ->groupBy(fn (array $row) => ($row['customer_id'] ?: 'guest') . '|' . $row['customer_name'])
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'id' => $first['customer_id'],
                    'name' => $first['customer_name'],
                    'documents' => $items->count(),
                    'latest_reference' => $items->sortByDesc('transaction_date')->first()['reference'] ?? null,
                    'oldest_date' => optional($items->pluck('transaction_date')->filter()->sort()->first())->format('d M Y'),
                    'total_due' => round((float) $items->sum('amount_due'), 2),
                    'bucket_current' => round((float) $items->where('age_days', '<=', 30)->sum('amount_due'), 2),
                    'bucket_31_60' => round((float) $items->filter(fn ($row) => $row['age_days'] >= 31 && $row['age_days'] <= 60)->sum('amount_due'), 2),
                    'bucket_61_90' => round((float) $items->filter(fn ($row) => $row['age_days'] >= 61 && $row['age_days'] <= 90)->sum('amount_due'), 2),
                    'bucket_90_plus' => round((float) $items->filter(fn ($row) => $row['age_days'] > 90)->sum('amount_due'), 2),
                ];
            })
            ->sortByDesc('total_due')
            ->values();
    }

    private function buildPayables(Request $request, string $search, string $month, string $fromDate, string $toDate): Collection
    {
        $query = Purchase::query()->with(['supplier', 'vendor']);
        $this->applyTenantScope($query, 'purchases');
        $this->applyBranchScope($query, 'purchases');
        $dateColumn = Schema::hasColumn('purchases', 'purchase_date') ? 'purchase_date' : 'created_at';
        $this->applyDateFilters($query, 'purchases', $dateColumn, $month, $fromDate, $toDate);

        if ($search !== '') {
            $hasSupplierName = Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'name');
            $hasVendorName = Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'name');
            $query->where(function ($sub) use ($search, $hasSupplierName, $hasVendorName) {
                $sub->where('purchase_no', 'like', '%' . $search . '%');
                if ($hasSupplierName) {
                    $sub->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', '%' . $search . '%'));
                }
                if ($hasVendorName) {
                    $sub->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', '%' . $search . '%'));
                }
            });
        }

        return $query->get()
            ->map(function (Purchase $purchase) use ($dateColumn) {
                $total = (float) ($purchase->total_amount ?? 0);
                $paid = (float) ($purchase->paid_amount ?? 0);
                $balance = round(max(0, $total - $paid), 2);
                $ageDate = $purchase->{$dateColumn} ?: $purchase->created_at;
                $supplierId = $purchase->supplier_id ?: null;
                $supplierName = $purchase->supplier?->name ?? $purchase->vendor?->name ?? 'Supplier';

                return [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplierName,
                    'reference' => $purchase->purchase_no ?: ('PUR-' . $purchase->id),
                    'amount_due' => $balance,
                    'age_days' => $ageDate ? Carbon::parse($ageDate)->startOfDay()->diffInDays(now()->startOfDay()) : 0,
                    'transaction_date' => $ageDate ? Carbon::parse($ageDate) : null,
                ];
            })
            ->filter(fn (array $row) => $row['amount_due'] > 0)
            ->groupBy(fn (array $row) => ($row['supplier_id'] ?: 'supplier') . '|' . $row['supplier_name'])
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'id' => $first['supplier_id'],
                    'name' => $first['supplier_name'],
                    'documents' => $items->count(),
                    'latest_reference' => $items->sortByDesc('transaction_date')->first()['reference'] ?? null,
                    'oldest_date' => optional($items->pluck('transaction_date')->filter()->sort()->first())->format('d M Y'),
                    'total_due' => round((float) $items->sum('amount_due'), 2),
                    'bucket_current' => round((float) $items->where('age_days', '<=', 30)->sum('amount_due'), 2),
                    'bucket_31_60' => round((float) $items->filter(fn ($row) => $row['age_days'] >= 31 && $row['age_days'] <= 60)->sum('amount_due'), 2),
                    'bucket_61_90' => round((float) $items->filter(fn ($row) => $row['age_days'] >= 61 && $row['age_days'] <= 90)->sum('amount_due'), 2),
                    'bucket_90_plus' => round((float) $items->filter(fn ($row) => $row['age_days'] > 90)->sum('amount_due'), 2),
                ];
            })
            ->sortByDesc('total_due')
            ->values();
    }

    private function applyDateFilters($query, string $table, string $column, string $month, string $fromDate, string $toDate): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        if ($month !== '') {
            $start = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
            $end = Carbon::parse($month . '-01')->endOfMonth()->toDateString();
            $query->whereBetween("{$table}.{$column}", [$start, $end]);

            return;
        }

        if ($fromDate !== '') {
            $query->whereDate("{$table}.{$column}", '>=', $fromDate);
        }
        if ($toDate !== '') {
            $query->whereDate("{$table}.{$column}", '<=', $toDate);
        }
    }

    private function paginateCollection(Collection $items, int $perPage, int $page, string $pageName, Request $request): LengthAwarePaginator
    {
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'pageName' => $pageName,
                'query' => collect($request->query())->except($pageName)->all(),
            ]
        );
    }
}
