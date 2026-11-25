<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ScopesFinancialStatements
{
    private function normalizeAccountType(?string $type): string
    {
        $value = strtolower(trim((string) $type));
        if ($value === '') {
            return 'other';
        }

        $map = [
            'asset' => [
                'asset', 'assets',
                'bank', 'bank account', 'bank accounts', 'cash', 'cash and bank', 'cash and cash equivalent',
                'cash and cash equivalents', 'petty cash', 'wallet',
                'receivable', 'receivables', 'accounts receivable', 'trade receivable', 'trade receivables',
                'debtor', 'debtors', 'trade debtor', 'trade debtors',
                'inventory', 'inventories', 'stock', 'stocks',
                'prepaid', 'prepaid expense', 'prepaid expenses', 'prepayment', 'prepayments',
                'fixed asset', 'fixed assets', 'non-current asset', 'non-current assets',
                'property plant and equipment', 'ppe', 'intangible asset', 'intangible assets',
                'investment', 'investments', 'long-term investment',
                'other asset', 'other assets', 'sundry debtor', 'sundry debtors',
            ],
            'liability' => [
                'liability', 'liabilities',
                'payable', 'payables', 'accounts payable', 'trade payable', 'trade payables',
                'creditor', 'creditors', 'trade creditor', 'trade creditors', 'sundry creditor', 'sundry creditors',
                'current liability', 'current liabilities',
                'long term liability', 'long-term liability', 'long term liabilities', 'long-term liabilities',
                'loan', 'loans', 'loan payable', 'bank loan', 'overdraft', 'bank overdraft',
                'borrowing', 'borrowings', 'term loan', 'mortgage',
                'tax', 'tax payable', 'vat', 'vat payable', 'paye', 'withholding tax',
                'accrual', 'accruals', 'accrued expense', 'accrued expenses', 'accrued liability',
                'deferred revenue', 'deferred income', 'unearned revenue',
                'customer deposit', 'customer deposits', 'advance from customer',
            ],
            'equity' => [
                'equity', 'equities',
                'capital', 'share capital', 'paid-up capital', 'paid up capital',
                'owner equity', 'owners equity', "owner's equity", 'shareholders equity', 'shareholder equity',
                'retained earnings', 'retained profit', 'retained profits', 'accumulated profit',
                'profit', 'net profit', 'profit and loss', 'p&l', 'profit & loss',
                'reserve', 'reserves', 'general reserve', 'statutory reserve', 'revaluation reserve',
                'dividend', 'dividends', 'drawings', 'proprietors fund', "proprietor's fund",
                'fund', 'funds',
            ],
            'revenue' => [
                'revenue', 'revenues', 'income', 'sales', 'turnover',
                'other income', 'interest income', 'fee income', 'service income',
                'rental income', 'commission income', 'grant income',
            ],
            'expense' => [
                'expense', 'expenses', 'cost', 'costs',
                'cogs', 'cost of sales', 'cost of goods sold', 'direct cost', 'direct costs',
                'operating expense', 'operating expenses', 'administration', 'administrative expense',
                'selling expense', 'selling expenses', 'overhead', 'overheads',
            ],
        ];

        foreach ($map as $key => $aliases) {
            if (in_array($value, $aliases, true)) {
                return $key;
            }
        }

        // Return a consistent sentinel for unrecognised types so callers can
        // distinguish "unknown" from any real account-type key.
        return 'other';
    }

    private function applyTransactionScope($query, Request $request)
    {
        $companyId = (int) (session('current_tenant_id') ?? $request->user()?->company_id ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $query->where('transactions.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $query->where('transactions.user_id', $userId);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function resolveActiveBranch(Request $request): array
    {
        $branchScope = (string) $request->get('branch_scope', '');
        $branchId = (string) $request->get('branch_id', '');
        $allBranches = $request->boolean('all_branches')
            || strtolower($branchScope) === 'all'
            || strtolower($branchId) === 'all'
            || ($branchScope === '' && $branchId === '' && session('active_branch_scope') === 'all');

        if ($allBranches) {
            session(['active_branch_scope' => 'all']);
            return ['id' => null, 'name' => null, 'scope' => 'all'];
        }

        $activeBranchId = trim((string) session('active_branch_id', ''));
        $activeBranchName = trim((string) session('active_branch_name', ''));

        if ($branchId !== '') {
            $activeBranchId = trim($branchId);
            $activeBranchName = '';
        }

        $companyId = (int) (session('current_tenant_id') ?? auth()->user()?->company_id ?? 0);
        if (($activeBranchId === '' || $activeBranchName === '') && $companyId > 0 && Schema::hasTable('settings')) {
            $branchKey = 'branches_json_company_' . $companyId;
            $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
            $branches = json_decode($rawBranches, true) ?: [];

            if ($activeBranchId !== '') {
                $match = collect($branches)->firstWhere('id', $activeBranchId);
                $activeBranchName = trim((string) ($match['name'] ?? $activeBranchName));
            } else {
                $first = collect($branches)->first();
                $activeBranchId = trim((string) ($first['id'] ?? $activeBranchId));
                $activeBranchName = trim((string) ($first['name'] ?? $activeBranchName));
            }
        }

        if ($activeBranchId !== '') {
            session(['active_branch_id' => $activeBranchId]);
        }
        if ($activeBranchName !== '') {
            session(['active_branch_name' => $activeBranchName]);
        }
        session(['active_branch_scope' => 'branch']);

        return ['id' => $activeBranchId ?: null, 'name' => $activeBranchName ?: null, 'scope' => 'branch'];
    }

    private function applyExactBranchScope($query, string $branchId, string $branchName, string $branchIdColumn = 'branch_id', string $branchNameColumn = 'branch_name'): void
    {
        $branchId = trim($branchId);
        $branchName = trim($branchName);

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($branchId, $branchName, $branchIdColumn, $branchNameColumn) {
            if ($branchId !== '') {
                $sub->where($branchIdColumn, $branchId);

                if ($branchName !== '') {
                    $sub->orWhere(function ($legacy) use ($branchIdColumn, $branchNameColumn, $branchName) {
                        $legacy->where(function ($emptyBranchId) use ($branchIdColumn) {
                            $emptyBranchId->whereNull($branchIdColumn)->orWhere($branchIdColumn, '');
                        })->where($branchNameColumn, $branchName);
                    });
                }

                return;
            }

            $sub->where($branchNameColumn, $branchName);
        });
    }

    private function applyLegacyOpeningBalanceBranchScope(
        $query,
        array $activeBranch,
        string $table = 'transactions'
    ): void {
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return;
        }

        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $qualifiedBranchId = "{$table}.branch_id";
        $qualifiedBranchName = "{$table}.branch_name";

        $query->where(function ($scoped) use ($activeBranch, $branchId, $branchName, $table, $qualifiedBranchId, $qualifiedBranchName) {
            $this->applyExactBranchScope($scoped, $branchId, $branchName, $qualifiedBranchId, $qualifiedBranchName);

            $scoped->orWhere(function ($legacy) use ($activeBranch, $table, $qualifiedBranchId, $qualifiedBranchName) {
                $legacy->where("{$table}.transaction_type", Transaction::TYPE_OPENING_BALANCE)
                    ->where(function ($missing) use ($qualifiedBranchId, $qualifiedBranchName) {
                        $missing->where(function ($branchIdGap) use ($qualifiedBranchId) {
                            $branchIdGap->whereNull($qualifiedBranchId)
                                ->orWhere($qualifiedBranchId, '');
                        })->where(function ($branchNameGap) use ($qualifiedBranchName) {
                            $branchNameGap->whereNull($qualifiedBranchName)
                                ->orWhere($qualifiedBranchName, '');
                        });
                    })
                    ->whereExists(function ($anchor) use ($activeBranch, $table) {
                        $anchor->select(DB::raw('1'))
                            ->from('transactions as branch_anchor')
                            ->whereNull('branch_anchor.deleted_at')
                            ->where('branch_anchor.transaction_type', Transaction::TYPE_OPENING_BALANCE)
                            ->whereColumn('branch_anchor.transaction_date', "{$table}.transaction_date")
                            ->whereColumn('branch_anchor.reference', "{$table}.reference")
                            ->whereColumn('branch_anchor.related_id', "{$table}.related_id")
                            ->whereColumn('branch_anchor.related_type', "{$table}.related_type");

                        if (Schema::hasColumn('transactions', 'company_id')) {
                            $anchor->where(function ($sameCompany) use ($table) {
                                $sameCompany->whereColumn('branch_anchor.company_id', "{$table}.company_id")
                                    ->orWhere(function ($bothNull) use ($table) {
                                        $bothNull->whereNull('branch_anchor.company_id')
                                            ->whereNull("{$table}.company_id");
                                    });
                            });
                        }

                        if (Schema::hasColumn('transactions', 'user_id')) {
                            $anchor->where(function ($sameUser) use ($table) {
                                $sameUser->whereColumn('branch_anchor.user_id', "{$table}.user_id")
                                    ->orWhere(function ($bothNull) use ($table) {
                                        $bothNull->whereNull('branch_anchor.user_id')
                                            ->whereNull("{$table}.user_id");
                                    });
                            });
                        }

                        $this->applyExactBranchScope(
                            $anchor,
                            trim((string) ($activeBranch['id'] ?? '')),
                            trim((string) ($activeBranch['name'] ?? '')),
                            'branch_anchor.branch_id',
                            'branch_anchor.branch_name'
                        );
                    });
            });
        });
    }

    private function applyAccountScope($query, Request $request)
    {
        $companyId = (int) (session('current_tenant_id') ?? $request->user()?->company_id ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where('accounts.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $query->where('accounts.user_id', $userId);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function unpostedAccountOpenings(Request $request, Carbon $date, array $branch): Collection
    {
        if (!Schema::hasColumn('accounts', 'opening_balance')) {
            return collect();
        }

        // An opening journal dated after the cutoff is handled by the posted date filter.
        $posted = Transaction::withoutGlobalScopes()
            ->whereNull('transactions.deleted_at')
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('related_type', Account::class)
            ->whereColumn('account_id', 'related_id')
            ->select('account_id');
        $this->applyTransactionScope($posted, $request);

        $query = Account::withoutGlobalScopes()->whereNull('accounts.deleted_at')
            ->whereRaw('ABS(COALESCE(opening_balance, 0)) >= 0.005')
            ->whereNotIn('accounts.id', $posted);
        $this->applyAccountScope($query, $request);
        $query->where(fn ($created) => $created->whereNull('created_at')->orWhereDate('created_at', '<=', $date->toDateString()));

        $branchId = (string) ($branch['id'] ?? '');
        $branchName = (string) ($branch['name'] ?? '');
        if ($branch['scope'] !== 'all' && ($branchId !== '' || $branchName !== '')) {
            $query->where(function ($scoped) use ($branchId, $branchName) {
                $this->applyExactBranchScope($scoped, $branchId, $branchName);
                $scoped->orWhere(fn ($unassigned) => $unassigned
                    ->whereRaw("COALESCE(branch_id, '') = ''")
                    ->whereRaw("COALESCE(branch_name, '') = ''"));
            });
        }

        return $query->orderByRaw('ABS(opening_balance) DESC')->orderBy('id')
            ->get(['id', 'name', 'code', 'opening_balance'])
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'amount' => (float) $account->opening_balance,
            ]);
    }
}
