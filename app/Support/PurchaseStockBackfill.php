<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Reconcile only demonstrably missing receipt movements; never replay every purchase. */
class PurchaseStockBackfill
{
    public function receipts(int $companyId, string $start, string $end): Collection
    {
        return DB::table('purchase_items as i')->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->where('p.company_id', $companyId)
            ->where('p.created_at', '>=', $start)->where('p.created_at', '<', $end)
            ->whereIn(DB::raw('LOWER(p.status)'), ['received', 'paid', 'partial', 'unpaid', 'completed'])
            ->where(fn ($q) => $q->whereNull('p.purchase_type')->orWhere('p.purchase_type', 'inventory'))
            ->where('p.purchase_no', 'not like', 'AUTO-STK-%')
            ->where('p.purchase_no', 'not like', 'HIST-INV-%')
            ->where('p.purchase_no', 'not like', 'PO-%')
            ->select('i.*', 'p.purchase_no', 'p.user_id as purchase_user_id', 'p.company_id as purchase_company_id',
                'p.branch_id as purchase_branch_id', 'p.branch_name as purchase_branch_name')
            ->orderBy('i.created_at')->orderBy('i.id')->get();
    }

    public function reconcile(int $companyId, Collection $receipts, string $start, string $end, bool $apply, string $dateLabel): array
    {
        return DB::transaction(function () use ($companyId, $receipts, $start, $end, $apply, $dateLabel) {
            $productId = (int) $receipts->first()->product_id;
            DB::table('purchases')->where('company_id', $companyId)->whereIn('id', $receipts->pluck('purchase_id')->unique())
                ->orderBy('id')->lockForUpdate()->get();
            $fresh = $this->receipts($companyId, $start, $end)->whereIn('id', $receipts->pluck('id'))->values();
            if ($fresh->count() !== $receipts->count() || $fresh->contains(fn ($row) => (int) $row->product_id !== $productId)) {
                return [['product' => $productId, 'purchase' => '', 'action' => 'REVIEW', 'units' => 0, 'reason' => 'Receipt lines changed during the run; preview again.']];
            }
            $receipts = $fresh;
            $product = DB::table('products')->where('company_id', $companyId)->where('id', $productId)->lockForUpdate()->first();
            if (!$product) {
                return [['product' => $productId, 'purchase' => '', 'action' => 'REVIEW', 'units' => 0, 'reason' => 'Product ownership is missing or differs from the receipt.']];
            }
            $history = DB::table('inventory_history')->where('product_id', $productId)
                ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
                ->orderBy('created_at')->orderBy('id')->get();
            $current = (float) ($product->stock ?? $product->stock_quantity ?? 0);
            $opening = $history->filter(fn ($row) => strtolower(trim((string) $row->reference)) === 'initial stock');
            $trusted = $opening->count() === 1 && $history->first()?->id === $opening->first()?->id
                && $history->every(fn ($row) => in_array(strtolower(trim($row->type)), ['in', 'out'], true) && (float) $row->quantity >= 0)
                && abs($this->balance($history) - $current) < .000001;
            $pool = $history->filter(fn ($row) => $row->created_at >= $start && $row->created_at < $end
                && strtolower(trim($row->type)) === 'in'
                && in_array(strtolower(trim((string) $row->reference)), ['stock in', 'purchase'], true)
                && (float) $row->quantity > 0);
            $rows = [];
            $missing = [];
            $postedBranches = [];
            $ambiguous = false;
            foreach ($receipts as $receipt) {
                $marker = 'Purchase stock backfill item '.$receipt->id;
                $qty = (float) ($receipt->stock_units ?? 0);
                if ($qty <= 0) {
                    $qty = (float) ($receipt->qty ?? 0) * max(1, (float) ($receipt->conversion_factor ?? 1));
                }
                $qty = round($qty, 6);
                $base = ['product' => $product->name.' (#'.$productId.')', 'purchase' => $receipt->purchase_no, 'units' => $qty];
                if ($history->contains(fn ($row) => $row->reference === $marker)) {
                    $rows[] = $base + ['action' => 'DONE', 'reason' => 'Already backfilled; no additional stock.'];
                    continue;
                }
                if ($receipt->created_at < $start || $receipt->created_at >= $end
                    || DB::table('purchase_returns')->where('purchase_id', $receipt->purchase_id)->exists()
                    || $qty <= 0 || abs($qty - round($qty, 2)) > .000001
                    || ((float) ($receipt->stock_units ?? 0) <= 0 && empty($receipt->unit)
                        && (!empty($product->purchase_unit_id) || (float) ($product->conversion_rate ?? 1) > 1))) {
                    $ambiguous = true;
                    $rows[] = $base + ['action' => 'REVIEW', 'reason' => 'Edited line, return, unsupported precision or unknown historical unit conversion.'];
                    continue;
                }
                $matched = $pool->first(fn ($row) => abs((float) $row->quantity - $qty) < .000001
                    && abs(strtotime($row->created_at) - strtotime($receipt->created_at)) <= 2);
                if ($matched) {
                    $pool = $pool->reject(fn ($row) => $row->id === $matched->id);
                    $rows[] = $base + ['action' => 'POSTED', 'reason' => 'Matching receipt stock movement exists (#'.$matched->id.').'];
                    $branchId = (string) ($receipt->branch_id ?: $receipt->purchase_branch_id);
                    if ($branchId !== '' && (string) $matched->branch_id === $branchId) {
                        $postedBranches[$branchId] ??= ['qty' => 0.0, 'name' => $receipt->branch_name ?: $receipt->purchase_branch_name];
                        $postedBranches[$branchId]['qty'] += $qty;
                    }
                } else {
                    $missing[] = [$receipt, $qty, $marker, $base];
                }
            }
            // Aggregated or manual stock-ins cannot reliably be allocated to unmatched receipts.
            if ($pool->isNotEmpty()) $ambiguous = true;
            $branchBalances = [];
            if ($trusted && !$ambiguous) {
                foreach ($postedBranches as $branchId => $posted) {
                    $saved = DB::table('product_branch_stocks')->where('product_id', $productId)->where('company_id', $companyId)
                        ->where('branch_id', $branchId)->lockForUpdate()->first();
                    $ledger = $this->balance($history->filter(fn ($row) => (string) $row->branch_id === (string) $branchId));
                    $before = (float) ($saved->quantity ?? 0);
                    $gap = round($ledger - $before, 6);
                    $marker = 'Purchase branch backfill '.$dateLabel.' '.$branchId;
                    if ($gap <= 0) continue;
                    if ($gap > $posted['qty'] + .000001 || $history->contains(fn ($row) => $row->reference === $marker)) {
                        $rows[] = ['product' => $product->name.' (#'.$productId.')', 'purchase' => '', 'units' => 0,
                            'action' => 'REVIEW', 'reason' => 'Branch discrepancy exceeds the matched receipts or was previously reconciled.'];
                        continue;
                    }
                    $branchBalances[$branchId] = ['row' => $saved, 'before' => $before, 'after' => $ledger, 'name' => $posted['name'], 'trusted' => true];
                    $rows[] = ['product' => $product->name.' (#'.$productId.')', 'purchase' => '', 'units' => $gap,
                        'action' => $apply ? 'BRANCH SYNCED' : 'WOULD SYNC BRANCH',
                        'reason' => 'Restore missing branch credit within yesterday receipt quantities; company stock unchanged.'];
                    if ($apply) {
                        DB::table('inventory_history')->insert(['product_id' => $productId, 'company_id' => $companyId,
                            'user_id' => $product->user_id, 'branch_id' => $branchId, 'branch_name' => $posted['name'],
                            'type' => 'in', 'quantity' => 0, 'reference' => $marker,
                            'remarks' => json_encode(['branch_before' => $before, 'branch_after' => $ledger,
                                'receipt_day_start' => $start, 'receipt_day_end_exclusive' => $end,
                                'purchase_item_ids' => $receipts->pluck('id')->all()], JSON_THROW_ON_ERROR),
                            'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
            $add = 0.0;
            foreach ($missing as [$receipt, $qty, $marker, $base]) {
                $branchId = (string) ($receipt->branch_id ?: $receipt->purchase_branch_id);
                $branchName = $receipt->branch_name ?: $receipt->purchase_branch_name;
                $reason = !$trusted ? 'Current stock cannot be verified against a complete opening balance and movement history.'
                    : ($ambiguous ? 'Unallocated stock movements or edited receipts require review.' : '');
                if ($branchId === '') $reason = 'Receipt has no receiving branch.';
                if ($reason === '' && !array_key_exists($branchId, $branchBalances)) {
                    $branchRows = DB::table('product_branch_stocks')->where('product_id', $productId)->where('company_id', $companyId);
                    $saved = (clone $branchRows)->where('branch_id', $branchId)->lockForUpdate()->first();
                    $branchHistory = $history->filter(fn ($row) => (string) $row->branch_id === $branchId
                        || (empty($row->branch_id) && $branchName && $row->branch_name === $branchName));
                    $before = $saved ? (float) $saved->quantity : $this->balance($branchHistory);
                    $branchBalances[$branchId] = ['row' => $saved, 'before' => $before, 'after' => $before, 'name' => $branchName,
                        'trusted' => abs($this->balance($branchHistory) - $before) < .000001];
                }
                if ($reason === '' && !$branchBalances[$branchId]['trusted']) $reason = 'Receiving branch balance differs from its recorded movements.';
                if ($reason !== '') {
                    $rows[] = $base + ['action' => 'REVIEW', 'reason' => $reason];
                    continue;
                }
                $branchBalances[$branchId]['after'] += $qty;
                $add += $qty;
                $rows[] = $base + ['action' => $apply ? 'ADDED' : 'WOULD ADD', 'reason' => 'Missing receipt movement; verified product and branch balances.'];
                if ($apply) {
                    $audit = ['purchase_id' => $receipt->purchase_id, 'item_id' => $receipt->id,
                        'receipt_day_start' => $start, 'receipt_day_end_exclusive' => $end,
                        'product_before' => $current + $add - $qty, 'product_after' => $current + $add,
                        'branch_id' => $branchId, 'branch_before' => $branchBalances[$branchId]['after'] - $qty,
                        'branch_after' => $branchBalances[$branchId]['after']];
                    DB::table('inventory_history')->insert(['product_id' => $productId, 'company_id' => $companyId,
                        'user_id' => $receipt->purchase_user_id ?: $product->user_id, 'branch_id' => $branchId,
                        'branch_name' => $branchName, 'type' => 'in', 'quantity' => $qty, 'reference' => $marker,
                        'remarks' => json_encode($audit, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            $next = round($current + $add, 6);
            $syncColumn = abs((float) $product->stock_quantity - $current) > .000001;
            $branchChanged = collect($branchBalances)->contains(fn ($row) => $row['before'] !== $row['after']);
            if ($add > 0 || $syncColumn || $branchChanged) {
                if ($add === 0 && $syncColumn) $rows[] = ['product' => $product->name.' (#'.$productId.')', 'purchase' => '', 'units' => 0,
                    'action' => $apply ? 'SYNCED' : 'WOULD SYNC', 'reason' => 'Align stock_quantity to existing products.stock; no receipt quantity added.'];
                if ($apply) {
                    // Raw updates intentionally avoid the observer generating another synthetic purchase.
                    DB::table('products')->where('id', $productId)->where('company_id', $companyId)
                        ->update(['stock' => $next, 'stock_quantity' => $next, 'updated_at' => now()]);
                    foreach ($branchBalances as $branchId => $balance) {
                        if ($balance['after'] === $balance['before']) continue;
                        DB::table('product_branch_stocks')->updateOrInsert(['product_id' => $productId, 'company_id' => $companyId, 'branch_id' => $branchId],
                            ['quantity' => round($balance['after'], 2), 'branch_name' => $balance['name'],
                                'created_at' => $balance['row']->created_at ?? now(), 'updated_at' => now()]);
                    }
                }
            }
            return $rows;
        });
    }

    private function balance(Collection $rows): float
    {
        return round($rows->sum(fn ($row) => strtolower(trim($row->type)) === 'out' ? -(float) $row->quantity : (float) $row->quantity), 6);
    }
}
