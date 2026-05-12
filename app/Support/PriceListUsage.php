<?php

namespace App\Support;

use App\Models\PriceList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PriceListUsage
{
    public function activeForCurrentContext(?int $companyId = null): Collection
    {
        if (!Schema::hasTable('price_lists')) {
            return collect();
        }

        $companyId = $companyId ?: (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        if ($companyId <= 0) {
            return collect();
        }

        $query = PriceList::query()
            ->with(['items' => fn ($items) => $items->orderBy('min_quantity')])
            ->where('company_id', $companyId);

        if (Schema::hasColumn('price_lists', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('price_lists', 'valid_from')) {
            $query->where(fn ($q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', now()->toDateString()));
        }
        if (Schema::hasColumn('price_lists', 'valid_to')) {
            $query->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', now()->toDateString()));
        }

        $allBranches = session('active_branch_scope') === 'all'
            || strtolower((string) session('active_branch_id')) === 'all';

        if (!$allBranches && (Schema::hasColumn('price_lists', 'branch_id') || Schema::hasColumn('price_lists', 'branch_name'))) {
            $branchId = trim((string) (session('active_branch_id') ?? auth()->user()?->branch_id ?? ''));
            $branchName = trim((string) (session('active_branch_name') ?? ''));

            $query->where(function ($q) use ($branchId, $branchName) {
                $hasBranchId = Schema::hasColumn('price_lists', 'branch_id');
                $hasBranchName = Schema::hasColumn('price_lists', 'branch_name');

                if ($hasBranchId) {
                    $q->whereNull('branch_id');
                    if ($branchId !== '') {
                        $q->orWhere('branch_id', $branchId);
                    }
                }

                if ($hasBranchName) {
                    $method = $hasBranchId ? 'orWhereNull' : 'whereNull';
                    $q->{$method}('branch_name');
                    if ($branchName !== '') {
                        $q->orWhere('branch_name', $branchName);
                    }
                }
            });
        }

        return $query
            ->orderByDesc(Schema::hasColumn('price_lists', 'is_default') ? 'is_default' : 'id')
            ->orderBy('name')
            ->get();
    }

    public function toFrontend(Collection $priceLists): array
    {
        return $priceLists->map(function (PriceList $priceList) {
            $items = $priceList->items
                ->groupBy('product_id')
                ->map(function ($rows) {
                    return $rows->sortBy('min_quantity')->map(function ($item) {
                        return [
                            'min_quantity' => (float) ($item->min_quantity ?? 1),
                            'price' => (float) ($item->price ?? $item->unit_price ?? 0),
                        ];
                    })->values()->all();
                });

            return [
                'id' => (int) $priceList->id,
                'name' => (string) $priceList->name,
                'currency' => (string) ($priceList->currency ?? ''),
                'discount_type' => $priceList->discount_type
                    ?? (($priceList->type ?? null) === 'fixed' ? 'fixed' : (($priceList->type ?? null) === 'discount' ? 'percentage' : null)),
                'discount_value' => (float) ($priceList->discount_value ?? $priceList->adjustment_value ?? 0),
                'is_default' => (bool) ($priceList->is_default ?? false),
                'items' => $items,
            ];
        })->values()->all();
    }
}
