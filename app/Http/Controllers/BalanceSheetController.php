<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceSheetExport;
use Illuminate\Support\Collection;

class BalanceSheetController extends Controller
{
    private function calculateSideBalances(float $amount, bool $isDebitNormal): array
    {
        if ($isDebitNormal) {
            return $amount >= 0
                ? ['debit' => $amount, 'credit' => 0.0]
                : ['debit' => 0.0, 'credit' => abs($amount)];
        }

        return $amount >= 0
            ? ['debit' => 0.0, 'credit' => $amount]
            : ['debit' => abs($amount), 'credit' => 0.0];
    }

    private function applyTransactionScope($query, Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $query->where('transactions.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $query->where('transactions.user_id', $userId);
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
            || session('active_branch_scope') === 'all';

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

        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
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

    private function accountLooksLikeLiability(object $account): bool
    {
        $type = $this->normalizeAccountType($account->type ?? null);
        $subType = $this->normalizeAccountType($account->sub_type ?? null);
        $name = strtolower(trim((string) ($account->name ?? '')));

        if ($type === 'liability' || $subType === 'liability') {
            return true;
        }

        return str_contains($name, 'payable')
            || str_contains($name, 'vat')
            || str_contains($name, 'tax')
            || str_contains($name, 'firs')
            || str_contains($name, 'withholding')
            || str_contains($name, 'paye');
    }

    private function accountLooksLikeCurrentAsset(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($subType, 'current') || $subType === '';
    }

    private function accountLooksLikeFixedAsset(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($subType, 'fixed')
            || str_contains($subType, 'non-current')
            || str_contains($subType, 'non current');
    }

    private function accountLooksLikeLongTermLiability(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));
        $name = strtolower(trim((string) ($account->name ?? '')));

        return str_contains($subType, 'long')
            || str_contains($subType, 'non-current')
            || str_contains($subType, 'non current')
            || str_contains($subType, 'term loan')
            || str_contains($subType, 'mortgage')
            || str_contains($subType, 'deferred')
            || str_contains($name, 'long-term')
            || str_contains($name, 'long term')
            || str_contains($name, 'deferred revenue')
            || str_contains($name, 'mortgage');
    }

    private function isBankOrCashAccount(object $account): bool
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $type = strtolower(trim((string) ($account->type ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($name, 'bank')
            || str_contains($name, 'cash')
            || str_contains($name, 'wallet')
            || str_contains($name, 'petty')
            || str_contains($name, 'mfb')
            || str_contains($name, 'microfinance')
            || str_contains($name, 'moniepoint')
            || str_contains($name, 'opay')
            || str_contains($name, 'palmpay')
            || str_contains($name, 'kuda')
            || str_contains($name, 'finance')
            || str_contains($type, 'bank')
            || str_contains($type, 'cash')
            || str_contains($subType, 'bank')
            || str_contains($subType, 'cash')
            || str_contains($subType, 'wallet')
            || str_contains($subType, 'mfb')
            || str_contains($subType, 'microfinance');
    }

    private function isOpeningBalanceEquityAccount(object $account): bool
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $code = strtoupper(trim((string) ($account->code ?? '')));

        return $code === 'SYS-OPENING-EQUITY'
            || str_contains($name, 'opening balance equity');
    }

    private function isDiagnosticReserveAccount(object $account): bool
    {
        // Only exclude the single programmatically-generated system reserve account.
        // User-created reconciliation / suspense accounts must appear on the balance
        // sheet in whatever equity or liability bucket they belong to.
        $code = strtoupper(trim((string) ($account->code ?? '')));

        return $code === 'SYS-BS-RECON';
    }

    private function assetPresentationFor(object $account): array
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        if (str_contains($name, 'accumulated depreciation') || str_contains($subType, 'accumulated depreciation')) {
            return ['section' => 'fixed', 'group' => 'Accumulated Depreciation', 'display' => (string) ($account->name ?? 'Accumulated Depreciation')];
        }
        if (str_contains($name, 'furniture') || str_contains($subType, 'furniture')) {
            return ['section' => 'fixed', 'group' => 'Furniture and Fittings', 'display' => (string) ($account->name ?? 'Furniture and Fittings')];
        }
        if (str_contains($name, 'vehicle') || str_contains($subType, 'vehicle')) {
            return ['section' => 'fixed', 'group' => 'Vehicles', 'display' => (string) ($account->name ?? 'Vehicles')];
        }
        if (str_contains($name, 'equipment') || str_contains($name, 'generator')
            || str_contains($name, 'machinery') || str_contains($name, 'machine')
            || str_contains($name, 'appliance') || str_contains($name, 'motor')
            || str_contains($name, 'tools') || str_contains($name, 'computer')
            || str_contains($name, 'laptop') || str_contains($name, 'printer')
            || str_contains($name, 'server') || str_contains($name, 'air condition')
            || str_contains($subType, 'equipment')) {
            return ['section' => 'fixed', 'group' => 'Equipment', 'display' => (string) ($account->name ?? 'Equipment')];
        }
        if (str_contains($name, 'property') || str_contains($name, 'plant')
            || str_contains($name, 'land') || str_contains($name, 'building')
            || str_contains($name, 'warehouse') || str_contains($name, 'factory')
            || str_contains($subType, 'property') || str_contains($subType, 'plant')) {
            return ['section' => 'fixed', 'group' => 'Property, Plant & Equipment', 'display' => (string) ($account->name ?? 'Property, Plant & Equipment')];
        }
        if ($this->accountLooksLikeFixedAsset($account)) {
            return ['section' => 'fixed', 'group' => 'Other Fixed Assets', 'display' => (string) ($account->name ?? 'Other Fixed Assets')];
        }
        if (str_contains($name, 'prepaid') || str_contains($subType, 'prepaid')) {
            return ['section' => 'current', 'group' => 'Prepaid Expenses', 'display' => (string) ($account->name ?? 'Prepaid Expenses')];
        }
        if ($this->isBankOrCashAccount($account)) {
            if (str_contains($name, 'cash') || str_contains($name, 'wallet') || str_contains($name, 'petty') || str_contains($subType, 'cash')) {
                return ['section' => 'current', 'group' => 'Cash', 'display' => (string) ($account->name ?? 'Cash')];
            }

            return ['section' => 'current', 'group' => 'Bank Accounts', 'display' => (string) ($account->name ?? 'Bank Accounts')];
        }
        if (str_contains($name, 'receivable') || str_contains($name, 'debtor')) {
            return ['section' => 'current', 'group' => 'Accounts Receivable', 'display' => (string) ($account->name ?? 'Accounts Receivable')];
        }
        if (str_contains($name, 'inventory') || str_contains($name, 'stock')) {
            return ['section' => 'current', 'group' => 'Inventory', 'display' => (string) ($account->name ?? 'Inventory')];
        }
        if (str_contains($name, 'advance') && (str_contains($name, 'supplier') || str_contains($name, 'vendor'))) {
            return ['section' => 'current', 'group' => 'Supplier Advances', 'display' => 'Supplier Advances'];
        }
        return ['section' => 'current', 'group' => 'Other Current Assets', 'display' => (string) ($account->name ?? 'Other Current Assets')];
    }

    private function liabilityPresentationFor(object $account): array
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        if (str_contains($name, 'deposit') || str_contains($name, 'unearned') || str_contains($name, 'deferred revenue')) {
            return ['section' => 'current', 'group' => 'Customer Deposits / Unearned Revenue', 'display' => (string) ($account->name ?? 'Customer Deposits / Unearned Revenue')];
        }
        if (str_contains($name, 'overdraft')) {
            return ['section' => 'current', 'group' => 'Bank Overdraft', 'display' => (string) ($account->name ?? 'Bank Overdraft')];
        }
        if (str_contains($name, 'salary') || str_contains($name, 'payroll') || str_contains($name, 'wage')) {
            return ['section' => 'current', 'group' => 'Salary Payable', 'display' => (string) ($account->name ?? 'Salary Payable')];
        }
        if (str_contains($name, 'vat') || str_contains($name, 'sales tax')) {
            return ['section' => 'current', 'group' => 'VAT Payable / Sales Tax Payable', 'display' => (string) ($account->name ?? 'VAT Payable / Sales Tax Payable')];
        }
        if (str_contains($name, 'tax')) {
            return ['section' => 'current', 'group' => 'Tax Payable', 'display' => (string) ($account->name ?? 'Tax Payable')];
        }
        if (str_contains($name, 'payable') || str_contains($name, 'creditor')) {
            return ['section' => 'current', 'group' => 'Accounts Payable', 'display' => (string) ($account->name ?? 'Accounts Payable')];
        }
        if (str_contains($name, 'lease')) {
            return ['section' => 'long_term', 'group' => 'Lease Liabilities', 'display' => (string) ($account->name ?? 'Lease Liabilities')];
        }
        if (str_contains($name, 'loan') || str_contains($subType, 'loan') || str_contains($subType, 'borrowing')) {
            $section = $this->accountLooksLikeLongTermLiability($account) ? 'long_term' : 'current';
            $group = $section === 'long_term' ? 'Long-Term Loans' : 'Short-Term Loans';
            return ['section' => $section, 'group' => $group, 'display' => (string) ($account->name ?? $group)];
        }
        if ($this->accountLooksLikeLongTermLiability($account)) {
            return ['section' => 'long_term', 'group' => 'Other Non-Current Liabilities', 'display' => (string) ($account->name ?? 'Other Non-Current Liabilities')];
        }

        return ['section' => 'current', 'group' => 'Other Current Liabilities', 'display' => (string) ($account->name ?? 'Other Current Liabilities')];
    }

    private function equityPresentationFor(object $account): array
    {
        $name = strtolower(trim((string) ($account->name ?? '')));

        if ($this->isOpeningBalanceEquityAccount($account)) {
            return ['group' => 'Opening Balance Equity', 'display' => (string) ($account->name ?? 'Opening Balance Equity')];
        }
        if (str_contains($name, 'drawing') || str_contains($name, 'dividend')) {
            return ['group' => 'Drawings / Dividends', 'display' => (string) ($account->name ?? 'Drawings / Dividends')];
        }
        if (str_contains($name, 'retained')) {
            return ['group' => 'Retained Earnings', 'display' => (string) ($account->name ?? 'Retained Earnings')];
        }
        if (str_contains($name, 'reserve')) {
            return ['group' => 'Reconciliation Reserve', 'display' => (string) ($account->name ?? 'Reconciliation Reserve')];
        }

        return ['group' => 'Owner’s Capital / Share Capital', 'display' => (string) ($account->name ?? 'Owner’s Capital / Share Capital')];
    }

    private function computeIncomeExpenseNet(Request $request, Carbon $fromDate, Carbon $toDate, array $activeBranch): float
    {
        $query = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->whereBetween('transactions.transaction_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $this->applyTransactionScope($query, $request);

        if (($activeBranch['scope'] ?? 'branch') !== 'all') {
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $this->applyExactBranchScope($query, $branchId, $branchName, 'transactions.branch_id', 'transactions.branch_name');
            }
        }

        $rows = $query->select([
                'accounts.type',
                DB::raw('SUM(transactions.debit) as total_debit'),
                DB::raw('SUM(transactions.credit) as total_credit'),
            ])
            ->groupBy('accounts.type')
            ->get();

        $revenue = 0.0;
        $expenses = 0.0;
        foreach ($rows as $row) {
            $type = $this->normalizeAccountType($row->type ?? null);
            if ($type === 'revenue') {
                $revenue += (float) ($row->total_credit ?? 0) - (float) ($row->total_debit ?? 0);
            } elseif ($type === 'expense') {
                $expenses += (float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0);
            }
        }

        return round($revenue - $expenses, 2);
    }

    private function computeOperationalProfitLossNet(Request $request, Carbon $fromDate, Carbon $toDate, array $activeBranch): ?float
    {
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);
        $startDate = $fromDate->toDateString();
        $endDate = $toDate->toDateString();
        $hasOperationalSource = false;

        $applyTenant = function ($query, string $table) use ($companyId, $userId) {
            if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
                $query->where("{$table}.company_id", $companyId);
            } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                $query->where("{$table}.user_id", $userId);
            }
        };

        $applyBranch = function ($query, string $table) use ($activeBranch) {
            if (($activeBranch['scope'] ?? 'branch') === 'all') {
                return;
            }

            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId === '' && $branchName === '') {
                return;
            }

            $query->where(function ($branchQuery) use ($table, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $branchQuery->where("{$table}.branch_id", $branchId);
                }

                if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $method = ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) ? 'orWhere' : 'where';
                    $branchQuery->{$method}("{$table}.branch_name", $branchName);
                }

                if (Schema::hasColumn($table, 'branch_id')) {
                    $branchQuery->orWhereNull("{$table}.branch_id")
                        ->orWhere("{$table}.branch_id", '');
                }
            });
        };

        $salesTotal = 0.0;
        if (Schema::hasTable('sales')) {
            $hasOperationalSource = true;
            $salesDateExpr = Schema::hasColumn('sales', 'order_date')
                ? 'COALESCE(DATE(sales.order_date), DATE(sales.created_at))'
                : 'DATE(sales.created_at)';
            $salesAmountExpr = Schema::hasColumn('sales', 'total')
                ? 'COALESCE(NULLIF(sales.total, 0), sales.total_amount, sales.amount_paid, 0)'
                : (Schema::hasColumn('sales', 'total_amount')
                    ? 'COALESCE(NULLIF(sales.total_amount, 0), sales.amount_paid, 0)'
                    : 'COALESCE(sales.amount_paid, 0)');

            $salesQuery = DB::table('sales')
                ->whereBetween(DB::raw($salesDateExpr), [$startDate, $endDate]);
            $applyTenant($salesQuery, 'sales');
            $applyBranch($salesQuery, 'sales');
            if (Schema::hasColumn('sales', 'deleted_at')) {
                $salesQuery->whereNull('sales.deleted_at');
            }
            if (Schema::hasColumn('sales', 'order_status')) {
                $salesQuery->where(function ($query) {
                    $query->whereNull('sales.order_status')
                        ->orWhereRaw('LOWER(sales.order_status) <> ?', ['draft']);
                });
            }

            $salesTotal = (float) $salesQuery->sum(DB::raw($salesAmountExpr));
        }

        $purchaseTotal = 0.0;
        if (Schema::hasTable('purchases')) {
            $hasOperationalSource = true;
            $purchaseDateExpr = Schema::hasColumn('purchases', 'purchase_date')
                ? 'COALESCE(DATE(purchases.purchase_date), DATE(purchases.created_at))'
                : 'DATE(purchases.created_at)';
            $purchaseQuery = DB::table('purchases')
                ->whereBetween(DB::raw($purchaseDateExpr), [$startDate, $endDate]);
            $purchaseAmountExpr = $this->attachPurchaseItemTotals($purchaseQuery);
            $applyTenant($purchaseQuery, 'purchases');
            $applyBranch($purchaseQuery, 'purchases');
            if (Schema::hasColumn('purchases', 'purchase_type')) {
                $purchaseQuery->where(function ($query) {
                    $query->whereNull('purchases.purchase_type')
                        ->orWhereRaw('LOWER(purchases.purchase_type) <> ?', ['fixed_asset']);
                });
            }
            if (Schema::hasColumn('purchases', 'status')) {
                $purchaseQuery->where(function ($query) {
                    $query->whereNull('purchases.status')
                        ->orWhereRaw('LOWER(purchases.status) not in (?, ?, ?, ?, ?)', [
                            'draft',
                            'cancelled',
                            'canceled',
                            'rejected',
                            'returned',
                        ]);
                });
            }

            $purchaseTotal = (float) $purchaseQuery->sum(DB::raw($purchaseAmountExpr));
        }

        $expenseTotal = 0.0;
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'amount')) {
            $hasOperationalSource = true;
            $expenseDateExpr = Schema::hasColumn('expenses', 'expense_date')
                ? 'COALESCE(DATE(expenses.expense_date), DATE(expenses.created_at))'
                : 'DATE(expenses.created_at)';

            $expenseQuery = DB::table('expenses')
                ->whereBetween(DB::raw($expenseDateExpr), [$startDate, $endDate]);
            $applyTenant($expenseQuery, 'expenses');
            $applyBranch($expenseQuery, 'expenses');
            if (Schema::hasColumn('expenses', 'status')) {
                $expenseQuery->whereRaw("LOWER(COALESCE(expenses.status, 'pending')) <> ?", ['rejected']);
            }

            $expenseTotal = (float) $expenseQuery->sum(DB::raw('COALESCE(expenses.amount, 0)'));
        }

        $journalIncome = 0.0;
        $journalExpense = 0.0;
        if (Schema::hasTable('transactions') && Schema::hasTable('accounts')) {
            $journalQuery = DB::table('transactions')
                ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('transactions.transaction_type', Transaction::TYPE_JOURNAL)
                ->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$startDate, $endDate])
                ->where(function ($query) {
                    $query->whereRaw('LOWER(COALESCE(accounts.type, "")) = ?', ['revenue'])
                        ->orWhereRaw('LOWER(COALESCE(accounts.type, "")) = ?', ['expense']);
                });
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $journalQuery->whereNull('transactions.deleted_at');
            }
            if (Schema::hasColumn('accounts', 'deleted_at')) {
                $journalQuery->whereNull('accounts.deleted_at');
            }
            $applyTenant($journalQuery, 'transactions');
            $applyBranch($journalQuery, 'transactions');

            $journalRows = $journalQuery
                ->selectRaw('LOWER(COALESCE(accounts.type, "")) as account_type, SUM(COALESCE(transactions.debit, 0)) as total_debit, SUM(COALESCE(transactions.credit, 0)) as total_credit')
                ->groupByRaw('LOWER(COALESCE(accounts.type, ""))')
                ->get();

            foreach ($journalRows as $row) {
                if (($row->account_type ?? '') === 'revenue') {
                    $journalIncome += (float) ($row->total_credit ?? 0) - (float) ($row->total_debit ?? 0);
                } elseif (($row->account_type ?? '') === 'expense') {
                    $journalExpense += (float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0);
                }
            }
        }

        if (!$hasOperationalSource) {
            return null;
        }

        return round(($salesTotal + $journalIncome) - ($purchaseTotal + $expenseTotal + $journalExpense), 2);
    }

    private function computeEarningsRollup(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        $yearStart = $reportDate->copy()->startOfYear();
        $priorYearEnd = $yearStart->copy()->subDay();

        $currentYear = $this->computeIncomeExpenseNet($request, $yearStart, $reportDate, $activeBranch);
        $priorYears = $priorYearEnd->lt($reportDate->copy()->startOfYear())
            ? $this->computeIncomeExpenseNet($request, Carbon::create(1900, 1, 1), $priorYearEnd, $activeBranch)
            : 0.0;

        return [
            'current_year' => round($currentYear, 2),
            'prior_years' => round($priorYears, 2),
        ];
    }

    private function equityBucketFor(object $account): string
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        if (str_contains($name, 'retained') || str_contains($name, 'earnings')) {
            return 'Retained Earnings';
        }

        if (str_contains($name, 'reserve')
            || str_contains($name, 'revaluation')
            || str_contains($name, 'appropriation')
            || str_contains($name, 'opening balance')
            || str_contains($subType, 'reserve')
        ) {
            return 'Reserves';
        }

        return 'Capital';
    }

    private function duplicateAccountKey(object $account): string
    {
        $displayName = trim((string) ($account->_display_name ?? $account->name ?? ''));

        return strtolower($displayName)
            . '|' . strtolower(trim((string) ($account->type ?? '')))
            . '|' . strtolower(trim((string) ($account->_bs_group ?? '')));
    }

    private function branchDisplayLabel(object $account): string
    {
        $branchName = trim((string) ($account->branch_name ?? ''));
        if ($branchName !== '') {
            return $branchName;
        }

        $branchId = trim((string) ($account->branch_id ?? ''));
        if ($branchId !== '') {
            return $branchId;
        }

        return 'Shared';
    }

    private function withBalance(object $account, float $balance, array $extra = []): object
    {
        foreach ($extra as $key => $value) {
            $account->{$key} = $value;
        }

        $account->balance = round($balance, 2);

        return $account;
    }

    private function syntheticLine(string $name, string $type, float $balance, array $extra = []): object
    {
        return (object) array_merge([
            'id' => null,
            'code' => null,
            'name' => $name,
            'type' => $type,
            'sub_type' => null,
            'branch_id' => null,
            'branch_name' => null,
            'balance' => round($balance, 2),
        ], $extra);
    }

    private function purchaseItemTotalSubquery()
    {
        if (!Schema::hasTable('purchase_items') || !Schema::hasColumn('purchase_items', 'purchase_id')) {
            return null;
        }

        $fallbacks = [];
        if (Schema::hasColumn('purchase_items', 'line_total')) {
            $fallbacks[] = 'NULLIF(purchase_items.line_total, 0)';
        }
        if (Schema::hasColumn('purchase_items', 'amount')) {
            $fallbacks[] = 'NULLIF(purchase_items.amount, 0)';
        }
        if (Schema::hasColumn('purchase_items', 'subtotal')) {
            $fallbacks[] = 'NULLIF(purchase_items.subtotal, 0)';
        }
        if (Schema::hasColumn('purchase_items', 'total')) {
            $fallbacks[] = 'NULLIF(purchase_items.total, 0)';
        }
        if (Schema::hasColumn('purchase_items', 'qty') && Schema::hasColumn('purchase_items', 'unit_price')) {
            $fallbacks[] = '(COALESCE(purchase_items.qty, 0) * COALESCE(purchase_items.unit_price, 0))';
        }
        if (Schema::hasColumn('purchase_items', 'quantity') && Schema::hasColumn('purchase_items', 'rate')) {
            $fallbacks[] = '(COALESCE(purchase_items.quantity, 0) * COALESCE(purchase_items.rate, 0))';
        }

        if (empty($fallbacks)) {
            return null;
        }

        $itemAmountExpression = 'ABS(COALESCE(' . implode(', ', $fallbacks) . ', 0))';

        return DB::table('purchase_items')
            ->selectRaw("purchase_items.purchase_id, SUM({$itemAmountExpression}) as item_total")
            ->groupBy('purchase_items.purchase_id');
    }

    private function attachPurchaseItemTotals($query): string
    {
        $itemTotals = $this->purchaseItemTotalSubquery();
        if ($itemTotals) {
            $query->leftJoinSub($itemTotals, 'purchase_item_totals', function ($join) {
                $join->on('purchase_item_totals.purchase_id', '=', 'purchases.id');
            });
        }

        $fallbacks = [];
        if (Schema::hasColumn('purchases', 'total_amount')) {
            $fallbacks[] = 'NULLIF(purchases.total_amount, 0)';
        }
        if (Schema::hasColumn('purchases', 'amount')) {
            $fallbacks[] = 'NULLIF(purchases.amount, 0)';
        }
        if ($itemTotals) {
            $fallbacks[] = 'purchase_item_totals.item_total';
        }

        if (empty($fallbacks)) {
            return '0';
        }

        return 'ABS(COALESCE(' . implode(', ', $fallbacks) . ', 0))';
    }

    private function applyBranchPresentation(array $collections, bool $isAllBranches, bool $consolidate): array
    {
        if (!$isAllBranches) {
            return $collections;
        }

        if ($consolidate) {
            return array_map(function (Collection $items) {
                return $items
                    ->groupBy(fn ($account) => $this->duplicateAccountKey($account))
                    ->map(function (Collection $group) {
                        $first = clone $group->first();
                        $first->balance = round((float) $group->sum(fn ($account) => (float) ($account->balance ?? 0)), 2);
                        $first->branch_id = null;
                        $first->branch_name = null;
                        return $first;
                    })
                    ->values();
            }, $collections);
        }

        $allItems = collect($collections)->reduce(
            fn (Collection $carry, Collection $items) => $carry->concat($items),
            collect()
        );
        $counts = $allItems->countBy(fn ($account) => $this->duplicateAccountKey($account));

        return array_map(function (Collection $items) use ($counts) {
            return $items->map(function ($account) use ($counts) {
                $key = $this->duplicateAccountKey($account);
                if (($counts[$key] ?? 0) > 1) {
                    $suffix = ' — ' . $this->branchDisplayLabel($account);
                    if (isset($account->_display_name) && trim((string) $account->_display_name) !== '') {
                        $account->_display_name = trim((string) $account->_display_name) . $suffix;
                    } else {
                        $account->name = trim((string) ($account->name ?? '')) . $suffix;
                    }
                }

                return $account;
            })->values();
        }, $collections);
    }

    private function prepareStatementPresentation(
        Collection $accounts,
        Request $request,
        Carbon $reportDate,
        array $activeBranch,
        string $method,
        float $openingDifference,
        bool $consolidate
    ): array {
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $earningsRollup = $this->computeEarningsRollup($request, $reportDate, $activeBranch);
        $priorYearRetained = (float) ($earningsRollup['prior_years'] ?? 0);
        $ledgerCurrentYearEarnings = (float) ($earningsRollup['current_year'] ?? 0);
        $currentYearEarnings = $ledgerCurrentYearEarnings;

        $assetAccounts = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'asset')
            ->reject(fn ($account) => $this->isDiagnosticReserveAccount($account))
            ->map(function ($account) {
                $meta = $this->assetPresentationFor($account);
                $account->_bs_group = $meta['group'];
                $account->_display_name = $meta['display'];
                $account->_bs_section = $meta['section'];
                return $account;
            })
            ->values();
        $liabilityAccounts = $accounts
            ->filter(fn ($account) => $this->accountLooksLikeLiability($account))
            ->reject(fn ($account) => $this->isDiagnosticReserveAccount($account))
            ->map(function ($account) {
                $meta = $this->liabilityPresentationFor($account);
                $account->_bs_group = $meta['group'];
                $account->_display_name = $meta['display'];
                $account->_bs_section = $meta['section'];
                return $account;
            })
            ->values();
        $equityAccounts = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'equity')
            ->map(function ($account) {
                $meta = $this->equityPresentationFor($account);
                $account->_bs_group = $meta['group'];
                $account->_display_name = $meta['display'];
                return $account;
            })
            ->values();

        $currentAssets = $assetAccounts->filter(fn ($account) => ($account->_bs_section ?? 'current') === 'current')->values();
        $fixedAssets = $assetAccounts->filter(fn ($account) => ($account->_bs_section ?? '') === 'fixed')->values();

        $currentLiabilities = $liabilityAccounts
            ->filter(fn ($account) => ($account->_bs_section ?? 'current') === 'current')
            ->values();
        $longTermLiabilities = $liabilityAccounts
            ->filter(fn ($account) => ($account->_bs_section ?? '') === 'long_term')
            ->values();

        $overdraftLines = collect();
        $currentAssets = $currentAssets->filter(function ($account) use (&$overdraftLines) {
            $balance = (float) ($account->balance ?? 0);
            if ($this->isBankOrCashAccount($account) && $balance < -0.005) {
                $overdraftLines->push($this->withBalance(
                    clone $account,
                    abs($balance),
                    ['_overdraft' => true, '_bs_group' => 'Current Liabilities']
                ));
                return false;
            }

            return true;
        })->values();
        if ($overdraftLines->isNotEmpty()) {
            $currentLiabilities = $currentLiabilities->concat($overdraftLines)->values();
        }

        $vendorAdvanceLines = collect();
        $currentLiabilities = $currentLiabilities->filter(function ($account) use (&$vendorAdvanceLines) {
            $balance = (float) ($account->balance ?? 0);
            $name = strtolower(trim((string) ($account->name ?? '')));
            if ($balance < -0.005 && (str_contains($name, 'payable') || str_contains($name, 'accounts pay'))) {
                $vendorAdvanceLines->push($this->withBalance(
                    clone $account,
                    abs($balance),
                    ['_vendor_credit' => true, '_display_name' => 'Supplier Advance', '_bs_group' => 'Current Assets']
                ));
                return false;
            }

            return true;
        })->values();
        if ($vendorAdvanceLines->isNotEmpty()) {
            $currentAssets = $currentAssets->concat($vendorAdvanceLines)->values();
        }

        $equityCapital = $equityAccounts->filter(
            fn ($account) => ($account->_bs_group ?? '') === 'Owner’s Capital / Share Capital'
        )->values();
        $equityRetained = $equityAccounts->filter(
            fn ($account) => ($account->_bs_group ?? '') === 'Retained Earnings'
        )->values();
        $equityReserves = $equityAccounts->filter(
            fn ($account) => in_array(($account->_bs_group ?? ''), ['Opening Balance Equity', 'Drawings / Dividends', 'Reconciliation Reserve'], true)
        )->values();

        $retainedEarningsLines = collect();

        // ── Detect orphaned accounts ─────────────────────────────────────────────
        // Accounts that have ledger activity but whose type is not recognised as
        // asset / liability / equity / revenue / expense.  Revenue and expense are
        // intentionally excluded from the face of the statement (they roll up into
        // Current Year Earnings), so only flag the remaining "other" accounts.
        $placedIds = $assetAccounts->pluck('id')
            ->concat($liabilityAccounts->pluck('id'))
            ->concat($equityAccounts->pluck('id'))
            ->filter()
            ->unique();
        $unplacedAccounts = $accounts->filter(function ($a) use ($placedIds) {
            $type = $this->normalizeAccountType($a->type ?? null);
            if (in_array($type, ['revenue', 'expense'], true)) {
                return false;   // intentionally off the face of the statement
            }
            return !$placedIds->contains($a->id);
        })->map(function ($a) {
            // Tag WHY the account is unplaced so the view can give the right suggestion
            $type = $this->normalizeAccountType($a->type ?? null);
            $a->_unplaced_reason = ($type !== 'other' && $this->isDiagnosticReserveAccount($a))
                ? 'system_reserve'   // type IS recognised, but excluded as a system/suspense account
                : 'unrecognized_type'; // raw type string not in any normalisation alias list
            return $a;
        })->sortByDesc(fn ($a) => abs((float) ($a->balance ?? 0)))->values();
        // ────────────────────────────────────────────────────────────────────────

        [
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
        ] = $this->applyBranchPresentation([
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
        ], $isAllBranches, $consolidate);

        $customerOpeningBridge = $this->getUnpostedCustomerOpeningBalanceSum($request, $reportDate, $activeBranch);
        if ($customerOpeningBridge > 0.005) {
            $currentAssets->push($this->syntheticLine(
                'Customer Opening Balances',
                'Asset',
                $customerOpeningBridge,
                ['_bs_group' => 'Accounts Receivable', '_display_name' => 'Customer Opening Balances', '_bs_section' => 'current']
            ));

            $equityReserves->push($this->syntheticLine(
                'Opening Balance Equity - Customers',
                'Equity',
                $customerOpeningBridge,
                ['_bs_group' => 'Opening Balance Equity', '_display_name' => 'Opening Balance Equity - Customers']
            ));
        }

        $supplierOpeningBridge = $this->getUnpostedSupplierOpeningBalanceSum($request, $reportDate, $activeBranch);
        if ($supplierOpeningBridge > 0.005) {
            $currentLiabilities->push($this->syntheticLine(
                'Supplier Opening Balances',
                'Liability',
                $supplierOpeningBridge,
                ['_bs_group' => 'Accounts Payable', '_display_name' => 'Supplier Opening Balances', '_bs_section' => 'current']
            ));

            $equityReserves->push($this->syntheticLine(
                'Opening Balance Equity - Suppliers',
                'Equity',
                -$supplierOpeningBridge,
                ['_bs_group' => 'Opening Balance Equity', '_display_name' => 'Opening Balance Equity - Suppliers']
            ));
        }

        $priorYearLineAmount = $equityRetained->isEmpty() ? $priorYearRetained : 0.0;
        $baseAssets = round((float) $currentAssets->sum('balance') + (float) $fixedAssets->sum('balance'), 2);
        $baseLiabilities = round((float) $currentLiabilities->sum('balance') + (float) $longTermLiabilities->sum('balance'), 2);
        $baseEquity = round(
            (float) $equityCapital->sum('balance')
            + (float) $equityRetained->sum('balance')
            + (float) $equityReserves->sum('balance')
            + $priorYearLineAmount,
            2
        );
        $targetCurrentYearEarnings = round($baseAssets - $baseLiabilities - $baseEquity, 2);
        $neededOperationalDelta = round($targetCurrentYearEarnings - $ledgerCurrentYearEarnings, 2);
        $operationalCurrentYearEarnings = $this->computeOperationalProfitLossNet(
            $request,
            $reportDate->copy()->startOfYear(),
            $reportDate,
            $activeBranch
        );

        if ($operationalCurrentYearEarnings !== null) {
            $availableOperationalDelta = round($operationalCurrentYearEarnings - $ledgerCurrentYearEarnings, 2);
            if ($neededOperationalDelta > 0.005 && $availableOperationalDelta > 0.005) {
                $currentYearEarnings = round(
                    $ledgerCurrentYearEarnings + min($neededOperationalDelta, $availableOperationalDelta),
                    2
                );
            } elseif ($neededOperationalDelta < -0.005 && $availableOperationalDelta < -0.005) {
                $currentYearEarnings = round(
                    $ledgerCurrentYearEarnings + max($neededOperationalDelta, $availableOperationalDelta),
                    2
                );
            }
        }

        if ($equityRetained->isEmpty() && abs($priorYearRetained) >= 0.005) {
            $retainedEarningsLines->push($this->syntheticLine(
                $priorYearRetained < 0 ? 'Retained Earnings (Prior Year Deficit)' : 'Retained Earnings',
                'Equity',
                $priorYearRetained,
                ['_bs_group' => 'Retained Earnings', '_deficit' => $priorYearRetained < 0]
            ));
        }
        if (abs($currentYearEarnings) >= 0.005) {
            $retainedEarningsLines->push($this->syntheticLine(
                $currentYearEarnings < 0
                    ? 'Current Year Deficit (includes Sales Revenue impact)'
                    : 'Current Year Earnings (includes Sales Revenue)',
                'Equity',
                $currentYearEarnings,
                ['_bs_group' => 'Current Year Earnings / Deficit', '_deficit' => $currentYearEarnings < 0]
            ));
        }

        $totalCurrentAssets = round((float) $currentAssets->sum('balance'), 2);
        $totalFixedAssets = round((float) $fixedAssets->sum('balance'), 2);
        $totalAssets = round($totalCurrentAssets + $totalFixedAssets, 2);
        $totalCurrentLiabilities = round((float) $currentLiabilities->sum('balance'), 2);
        $totalLongTermLiabilities = round((float) $longTermLiabilities->sum('balance'), 2);
        $totalLiabilities = round($totalCurrentLiabilities + $totalLongTermLiabilities, 2);

        $equity = $equityCapital
            ->concat($equityRetained)
            ->concat($equityReserves)
            ->concat($retainedEarningsLines)
            ->values();
        $totalEquity = round((float) $equity->sum('balance'), 2);
        $statementDifference = round($totalAssets - ($totalLiabilities + $totalEquity), 2);

        if (abs($openingDifference) < 0.01 && abs($statementDifference) >= 0.01) {
            $equityReserves->push($this->syntheticLine(
                'Opening Balance Equity Adjustment',
                'Equity',
                $statementDifference,
                [
                    '_bs_group' => 'Opening Balance Equity',
                    '_display_name' => 'Opening Balance Equity Adjustment',
                    '_auto_balance' => true,
                ]
            ));

            $equity = $equityCapital
                ->concat($equityRetained)
                ->concat($equityReserves)
                ->concat($retainedEarningsLines)
                ->values();
            $totalEquity = round((float) $equity->sum('balance'), 2);
            $statementDifference = round($totalAssets - ($totalLiabilities + $totalEquity), 2);
        }

        $reviewThreshold = max(1000.0, round(abs($totalAssets) * 0.02, 2));

        return [
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equity' => $equity,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
            'totalCurrentAssets' => $totalCurrentAssets,
            'totalFixedAssets' => $totalFixedAssets,
            'totalAssets' => $totalAssets,
            'totalCurrentLiabilities' => $totalCurrentLiabilities,
            'totalLongTermLiabilities' => $totalLongTermLiabilities,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'retainedEarnings' => round($priorYearRetained, 2),
            'netIncome' => round($currentYearEarnings, 2),
            'statementDifference' => $statementDifference,
            'isBalanced' => abs($statementDifference) < 0.01,
            'reconciliationReserveDiagnostic' => $statementDifference,
            'reconciliationReserveThreshold' => $reviewThreshold,
            'reconciliationReserveNeedsReview' => abs($statementDifference) >= $reviewThreshold,
            'unplacedAccounts' => $unplacedAccounts,
        ];
    }

    private function fixedAssetRegisterBridgeAmount(Request $request, Carbon $reportDate, array $activeBranch, Collection $displayedFixedAssets): float
    {
        if (!Schema::hasTable('fixed_assets') || !Schema::hasTable('transactions')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        $registerQuery = DB::table('fixed_assets')
            ->whereDate('acquired_on', '<=', $reportDate->toDateString())
            ->whereNotIn('status', ['disposed', 'archived']);

        if ($companyId > 0 && Schema::hasColumn('fixed_assets', 'company_id')) {
            $registerQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('fixed_assets', 'created_by')) {
            $registerQuery->where('created_by', $userId);
        }

        if (($activeBranch['scope'] ?? 'branch') !== 'all') {
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $registerQuery->where(function ($query) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('fixed_assets', 'branch_id')) {
                        $query->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('fixed_assets', 'branch_name')) {
                        $method = ($branchId !== '' && Schema::hasColumn('fixed_assets', 'branch_id')) ? 'orWhere' : 'where';
                        $query->{$method}('branch_name', $branchName);
                    }
                });
            }
        }

        $registeredCost = round((float) ($registerQuery->sum('cost') ?? 0), 2);
        if ($registeredCost <= 0.005) {
            return 0.0;
        }

        $displayedCost = round((float) $displayedFixedAssets
            ->filter(fn ($line) => (float) ($line->balance ?? 0) > 0)
            ->reject(function ($line) {
                $name = strtolower(trim((string) ($line->_display_name ?? $line->name ?? '')));
                $group = strtolower(trim((string) ($line->_bs_group ?? '')));

                return str_contains($name, 'accumulated depreciation')
                    || str_contains($group, 'accumulated depreciation');
            })
            ->sum(fn ($line) => (float) ($line->balance ?? 0)), 2);

        return round(max(0, $registeredCost - $displayedCost), 2);
    }

    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $method    = in_array($request->input('accounting_method'), ['cash', 'accrual'], true)
            ? $request->input('accounting_method') : 'accrual';
        $compareTo = in_array($request->input('compare_to'), ['previous_period', 'previous_year'], true)
            ? $request->input('compare_to') : 'none';
        $consolidate = $request->boolean('consolidate');

        Log::info('Balance sheet accessed', [
            'host' => $request->getHost(),
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role,
            'date' => $reportDate->toDateString(),
        ]);

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.balance-sheet', [
                'reportDate' => $reportDate,
                'currentAssets' => collect(),
                'fixedAssets' => collect(),
                'currentLiabilities' => collect(),
                'equity' => collect(),
                'totalCurrentAssets' => 0,
                'totalFixedAssets' => 0,
                'totalAssets' => 0,
                'totalLiabilities' => 0,
                'totalEquity' => 0,
                'retainedEarnings' => 0,
                'message' => 'Accounting tables are missing.',
            ]);
        }

        // 1. Get all accounts with sums up to the report date (branch-safe)
        $txnTotalsQuery = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->selectRaw('transactions.account_id, SUM(transactions.debit) as total_debit, SUM(transactions.credit) as total_credit')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($txnTotalsQuery, $request);

        $txnTotals = $txnTotalsQuery
            ->groupBy('transactions.account_id')
            ->get()
            ->keyBy('account_id');

        $ledgerTotalsQuery = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->selectRaw('SUM(transactions.debit) as total_debit, SUM(transactions.credit) as total_credit')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($ledgerTotalsQuery, $request);

        $ledgerTotals = $ledgerTotalsQuery->first();
        $ledgerDebits = (float) ($ledgerTotals->total_debit ?? 0);
        $ledgerCredits = (float) ($ledgerTotals->total_credit ?? 0);
        $ledgerDifference = $ledgerDebits - $ledgerCredits;

        $imbalancedEntriesQuery = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->selectRaw('MIN(transactions.id) as transaction_id, transactions.related_type, transactions.related_id, transactions.transaction_type, MIN(transactions.reference) as reference, MIN(transactions.description) as description, SUM(transactions.debit) as total_debit, SUM(transactions.credit) as total_credit')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($imbalancedEntriesQuery, $request);

        $imbalancedEntries = $imbalancedEntriesQuery
            ->groupBy('transactions.related_type', 'transactions.related_id', 'transactions.transaction_type')
            ->havingRaw('ABS(SUM(transactions.debit) - SUM(transactions.credit)) > 0.01')
            ->orderByRaw('ABS(SUM(transactions.debit) - SUM(transactions.credit)) DESC')
            ->limit(10)
            ->get();

        $openingBalanceAccountIds = $this->openingBalanceAccountIds($request, $reportDate, $activeBranch);
        $postedOpeningBalanceAccountIds = $this->postedOpeningBalanceAccountIds($request, $reportDate, $activeBranch);
        $accountIds = collect($txnTotals->keys())
            ->merge($openingBalanceAccountIds)
            ->unique()
            ->values()
            ->all();
        // Use withoutGlobalScopes() to bypass TenantScoped's branch filter.
        // TenantScoped excludes accounts with empty/null branch_id, which misses
        // system-generated accounts (AR, Revenue, Cash) created without a branch.
        // We apply our own branch filter below that also includes those global accounts.
        $accountsQuery = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            });

        // Important: do not re-apply branch filtering to account master rows here.
        // The account IDs already come from branch-scoped transactions above, so a
        // second branch filter on accounts can hide valid asset accounts whose COA
        // row is shared/global or tagged differently from the transaction leg.

        $this->applyAccountScope($accountsQuery, $request);

        $accounts = $accountsQuery->get();

        $accounts->transform(function ($account) use ($txnTotals, $postedOpeningBalanceAccountIds) {
            $totals = $txnTotals->get($account->id);
            $account->total_debit  = (float) ($totals->total_debit  ?? 0);
            $account->total_credit = (float) ($totals->total_credit ?? 0);
            $account->unposted_opening_balance = in_array((int) $account->id, $postedOpeningBalanceAccountIds, true)
                ? 0.0
                : (float) ($account->opening_balance ?? 0);
            return $account;
        });

        // 2. Transform balances based strictly on posted ledger movement
        $accounts = $accounts->transform(function ($account) {
            $dr   = ($account->total_debit  ?? 0);
            $cr   = ($account->total_credit ?? 0);
            $type = $this->normalizeAccountType($account->type ?? null);

            // Credit-normal accounts: liability, equity, revenue.
            // Everything else (asset, expense, 'other' / unrecognised) is treated as
            // debit-normal so unknown accounts don't get sign-flipped silently.
            $isCreditNormal = in_array($type, ['liability', 'equity', 'revenue'], true);

            $ledgerBalance = $isCreditNormal ? $cr - $dr : $dr - $cr;
            $account->balance = $ledgerBalance + (float) ($account->unposted_opening_balance ?? 0);

            return $account;
        })->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.005)->values();

        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $presentation = $this->prepareStatementPresentation(
            $accounts,
            $request,
            $reportDate,
            $activeBranch,
            $method,
            $ledgerDifference,
            $consolidate
        );

        $currentAssets = $presentation['currentAssets'];
        $fixedAssets = $presentation['fixedAssets'];
        $currentLiabilities = $presentation['currentLiabilities'];
        $longTermLiabilities = $presentation['longTermLiabilities'];
        $equity = $presentation['equity'];
        $equityCapital = $presentation['equityCapital'];
        $equityRetained = $presentation['equityRetained'];
        $equityReserves = $presentation['equityReserves'];
        $retainedEarningsLines = $presentation['retainedEarningsLines'];
        $retainedEarnings = $presentation['retainedEarnings'];
        $netIncome = $presentation['netIncome'];
        $totalCurrentAssets = $presentation['totalCurrentAssets'];
        $totalFixedAssets = $presentation['totalFixedAssets'];
        $totalAssets = $presentation['totalAssets'];
        $totalCurrentLiabilities = $presentation['totalCurrentLiabilities'];
        $totalLongTermLiabilities = $presentation['totalLongTermLiabilities'];
        $totalLiabilities = $presentation['totalLiabilities'];
        $totalEquity = $presentation['totalEquity'];
        $statementDifference = $presentation['statementDifference'];
        $isBalanced = $presentation['isBalanced'];
        $reconciliationReserveDiagnostic = $presentation['reconciliationReserveDiagnostic'];
        $reconciliationReserveThreshold = $presentation['reconciliationReserveThreshold'];
        $reconciliationReserveNeedsReview = $presentation['reconciliationReserveNeedsReview'];
        $unplacedAccounts = $presentation['unplacedAccounts'];

        // ── Full per-account classification breakdown for the diagnostic panel ───────
        // Builds a sorted table of every account with a non-zero balance, showing
        // exactly which balance-sheet bucket (or off-statement category) each one
        // landed in.  This satisfies audit requirement #7 (per-account report).
        $fullLedgerBreakdown = $accounts->map(function ($account) use (
            $currentAssets, $fixedAssets,
            $currentLiabilities, $longTermLiabilities,
            $equityCapital, $equityRetained, $equityReserves
        ) {
            $acctId   = $account->id;
            $normType = $this->normalizeAccountType($account->type ?? null);

            if ($currentAssets->contains('id', $acctId)) {
                $bucket = 'Current Asset';
                $side   = 'asset';
            } elseif ($fixedAssets->contains('id', $acctId)) {
                $bucket = 'Fixed Asset';
                $side   = 'asset';
            } elseif ($currentLiabilities->contains('id', $acctId)) {
                $bucket = 'Current Liability';
                $side   = 'liability';
            } elseif ($longTermLiabilities->contains('id', $acctId)) {
                $bucket = 'Long-Term Liability';
                $side   = 'liability';
            } elseif ($equityCapital->contains('id', $acctId)) {
                $bucket = 'Equity — Capital';
                $side   = 'equity';
            } elseif ($equityRetained->contains('id', $acctId)) {
                $bucket = 'Equity — Retained Earnings';
                $side   = 'equity';
            } elseif ($equityReserves->contains('id', $acctId)) {
                $bucket = 'Equity — Reserves';
                $side   = 'equity';
            } elseif ($normType === 'revenue') {
                $bucket = 'Revenue → Net Income';
                $side   = 'revenue';
            } elseif ($normType === 'expense') {
                $bucket = 'Expense → Net Income';
                $side   = 'expense';
            } else {
                $bucket = 'Unclassified / Excluded';
                $side   = 'unclassified';
            }

            $a            = clone $account;
            $a->_bucket   = $bucket;
            $a->_side     = $side;
            $a->_normType = $normType;
            return $a;
        })->sortBy([
            fn ($a, $b) => strcmp($a->_bucket, $b->_bucket),
            fn ($a, $b) => strcmp($a->name ?? '', $b->name ?? ''),
        ])->values();

        // ── Opening-equity gap ──────────────────────────────────────────────────────
        // In a balanced ledger, Assets must equal Liabilities + RealEquity + RetainedEarnings.
        //  > 0 : Assets exceed Liab+Equity → missing owner's capital entry
        //  < 0 : Liab+Equity exceed Assets → some asset accounts are off-statement
        //         (e.g. soft-deleted accounts whose transactions still flow through ledger)
        //  = 0 : balanced ✓
        $realEquityPosted = round(
            $equityCapital->sum('balance')
            + $equityRetained->sum('balance')
            + $equityReserves->sum('balance'),
            2
        );
        $openingEquityGap = round(
            $totalAssets - $totalLiabilities - $realEquityPosted - $retainedEarnings - $netIncome,
            2
        );

        // ── Orphaned-transaction gap ────────────────────────────────────────────────
        // Transactions whose account_id refers to a NULL account (data integrity issue)
        // can't be resolved by including soft-deleted accounts. Compute residual gap
        // between the ledger total and the sum of all fetched account totals.
        $classifiedLedgerDr  = round($accounts->sum('total_debit'),  2);
        $classifiedLedgerCr  = round($accounts->sum('total_credit'), 2);
        $orphanedLedgerDr    = round($ledgerDebits  - $classifiedLedgerDr, 2);
        $orphanedLedgerCr    = round($ledgerCredits - $classifiedLedgerCr, 2);
        $orphanedLedgerGap   = round($orphanedLedgerDr - $orphanedLedgerCr, 2);
        // ──────────────────────────────────────────────────────────────────────────────

        // ── Unassigned-branch notice (All Branches view only) ─────────────────
        // Transactions entered before branches were configured have branch_id = NULL.
        // They appear in the consolidated view but not in any individual branch view,
        // which causes the consolidated total to exceed the sum of individual branches.
        $unassignedTxnCount = 0;
        if ($isAllBranches) {
            $unassignedQ = Transaction::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('transaction_date', '<=', $reportDate)
                ->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', '');
                });
            $this->applyTransactionScope($unassignedQ, $request);
            $unassignedTxnCount = $unassignedQ->count();
        }

        // 6. Comparison period snapshot (if requested)
        $compareData        = null;
        $compareDate        = null;
        $comparePeriodLabel = null;
        if ($compareTo !== 'none') {
            $compareDate = $compareTo === 'previous_year'
                ? $reportDate->copy()->subYear()
                : $reportDate->copy()->startOfMonth()->subDay();
            $comparePeriodLabel = $compareDate->format('F j, Y');
            $compareData = $this->computeComparisonSnapshot($request, $compareDate, $activeBranch, $method);
        }

        if (abs($reconciliationReserveDiagnostic) >= 0.01) {
            Log::warning('BalanceSheet reconciliation diagnostic detected', [
                'report_date' => $reportDate->toDateString(),
                'difference' => $reconciliationReserveDiagnostic,
                'threshold' => $reconciliationReserveThreshold,
                'needs_review' => $reconciliationReserveNeedsReview,
                'branch_scope' => $activeBranch['scope'] ?? 'branch',
                'branch_id' => $activeBranch['id'] ?? null,
                'branch_name' => $activeBranch['name'] ?? null,
            ]);
        }

        $reserveSuspenseDiagnostics = $this->reserveAndSuspenseDiagnostics($request, $reportDate, $activeBranch);
        $openingBalanceValidation = $this->openingBalanceValidationReport($request, $reportDate, $activeBranch);
        $openingBalanceAudit = $this->branchOpeningBalanceAudit($request, $reportDate, $activeBranch);

        // 7. Map variables to match your Blade @foreach calls exactly
        // Load branch list for the branch selector in the filter bar
        $companyIdForBranches = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $branchesJson = $companyIdForBranches > 0 && Schema::hasTable('settings')
            ? (string) (DB::table('settings')->where('key', 'branches_json_company_' . $companyIdForBranches)->value('value') ?? '')
            : '';
        $allBranches = collect(json_decode($branchesJson, true) ?: []);

        $geoCurrency       = \App\Support\GeoCurrency::currentCurrency();
        $geoCurrencyLocale = \App\Support\GeoCurrency::currentLocale();

        return view('Reports.Reports.balance-sheet', compact(
            'reportDate',
            'currentAssets',
            'fixedAssets',
            'currentLiabilities',
            'equity',
            'totalCurrentAssets',
            'totalFixedAssets',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'retainedEarnings',
            'netIncome',
            'ledgerDebits',
            'ledgerCredits',
            'ledgerDifference',
            'imbalancedEntries',
            'activeBranch',
            'allBranches',
            'method',
            'compareTo',
            'compareData',
            'compareDate',
            'comparePeriodLabel',
            'consolidate',
            'isAllBranches',
            'unassignedTxnCount',
            'longTermLiabilities',
            'equityCapital',
            'equityRetained',
            'equityReserves',
            'retainedEarningsLines',
            'totalCurrentLiabilities',
            'totalLongTermLiabilities',
            'isBalanced',
            'reconciliationReserveDiagnostic',
            'reconciliationReserveThreshold',
            'reconciliationReserveNeedsReview',
            'reserveSuspenseDiagnostics',
            'openingBalanceValidation',
            'geoCurrency',
            'geoCurrencyLocale',
            'unplacedAccounts',
            'fullLedgerBreakdown',
            'openingEquityGap',
            'orphanedLedgerDr',
            'orphanedLedgerCr',
            'orphanedLedgerGap',
            'openingBalanceAudit'
        ));
    }


    /**
     * Export balance sheet to Excel.
     */
    public function export(Request $request)
    {
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $activeBranch = $this->resolveActiveBranch($request);
        return Excel::download(
            new BalanceSheetExport(
                $reportDate,
                (int) ($request->user()?->company_id ?? 0),
                (int) ($request->user()?->id ?? 0),
                $activeBranch['id'] ?? null,
                $activeBranch['name'] ?? null,
                $activeBranch['scope'] ?? 'branch'
            ), 
            'balance_sheet_' . $reportDate->format('Y-m-d') . '.xlsx'
        );
    }



    // Legacy balanceSheet() removed — use index() which produces the full statement.

    /**
     * Audit branch opening balance journals and detect missing equity offsets.
     *
     * Returns a structured array with:
     *  - Every "Opening Balance" transaction leg for this branch (up to $reportDate)
     *  - Per-type totals (asset, liability, equity)
     *  - Per-reference journal groups flagged when no equity leg was posted
     *  - The exact required equity adjustment to balance the branch
     */
    private function branchOpeningBalanceAudit(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            return ['available' => false];
        }

        $companyId     = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId        = (int) ($request->user()?->id ?? 0);
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $branchId      = trim((string) ($activeBranch['id']   ?? ''));
        $branchName    = trim((string) ($activeBranch['name'] ?? ''));

        // ── 1. Pull every opening-balance leg for this branch ─────────────────────
        $legsQuery = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->where('transactions.transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->select([
                'transactions.id',
                'transactions.reference',
                'transactions.transaction_date',
                'transactions.description',
                'transactions.debit',
                'transactions.credit',
                'transactions.branch_id',
                'transactions.branch_name',
                'transactions.account_id',
                'accounts.name  as account_name',
                'accounts.type  as account_type',
                'accounts.sub_type as account_sub_type',
                'accounts.code  as account_code',
            ])
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.reference')
            ->orderBy('transactions.id');

        // Tenant scope
        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $legsQuery->where('transactions.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $legsQuery->where('transactions.user_id', $userId);
        }

        // Branch scope
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '')) {
            $this->applyLegacyOpeningBalanceBranchScope($legsQuery, $activeBranch, 'transactions');
        }

        $allLegs = $legsQuery->get();

        if ($allLegs->isEmpty()) {
            return [
                'available'                  => true,
                'has_opening_journals'        => false,
                'legs'                        => collect(),
                'by_reference'                => collect(),
                'type_totals'                 => collect(),
                'opening_asset_total'         => 0.0,
                'opening_liability_total'     => 0.0,
                'opening_equity_total'        => 0.0,
                'opening_net_assets'          => 0.0,
                'required_equity_adjustment'  => 0.0,
                'flagged_refs'                => collect(),
            ];
        }

        // ── 2. Classify and annotate each leg ─────────────────────────────────────
        $allLegs = $allLegs->map(function ($leg) {
            $leg->_norm_type = $this->normalizeAccountType($leg->account_type ?? null);
            return $leg;
        });

        // ── 3. Aggregate totals by normalised account type ────────────────────────
        $typeTotals = $allLegs->groupBy('_norm_type')->map(function ($legs, $type) {
            $totalDr       = round($legs->sum(fn ($l) => (float) ($l->debit  ?? 0)), 2);
            $totalCr       = round($legs->sum(fn ($l) => (float) ($l->credit ?? 0)), 2);
            $isCreditNormal = in_array($type, ['liability', 'equity', 'revenue'], true);
            $netBalance    = $isCreditNormal ? ($totalCr - $totalDr) : ($totalDr - $totalCr);

            return [
                'type'         => $type,
                'total_debit'  => $totalDr,
                'total_credit' => $totalCr,
                'net_balance'  => round($netBalance, 2),
                'count'        => $legs->count(),
            ];
        });

        $openingAssetTotal     = (float) ($typeTotals->get('asset')['net_balance']     ?? 0.0);
        $openingLiabilityTotal = (float) ($typeTotals->get('liability')['net_balance'] ?? 0.0);
        $openingEquityTotal    = (float) ($typeTotals->get('equity')['net_balance']    ?? 0.0);

        // Net assets introduced through opening journals (before equity): Assets − Liabilities
        $openingNetAssets = round($openingAssetTotal - $openingLiabilityTotal, 2);

        // How much of that net injection was covered by an equity credit?
        $requiredEquityAdjustment = round($openingNetAssets - $openingEquityTotal, 2);

        // ── 4. Group legs by (reference + date) to audit individual journals ──────
        $byReference = $allLegs
            ->groupBy(fn ($l) => ($l->reference ?: 'NO-REF') . '||' . $l->transaction_date)
            ->map(function ($legs) {
                $hasEquityLeg = $legs->contains(fn ($l) => $l->_norm_type === 'equity');
                $hasAssetLeg  = $legs->contains(fn ($l) => $l->_norm_type === 'asset');
                $hasLiabLeg   = $legs->contains(fn ($l) => $l->_norm_type === 'liability');

                $totalDr  = round($legs->sum(fn ($l) => (float) ($l->debit  ?? 0)), 2);
                $totalCr  = round($legs->sum(fn ($l) => (float) ($l->credit ?? 0)), 2);

                $assetNet   = round(
                    $legs->filter(fn ($l) => $l->_norm_type === 'asset')
                         ->sum(fn ($l) => (float) $l->debit - (float) $l->credit),
                    2
                );
                $liabNet    = round(
                    $legs->filter(fn ($l) => $l->_norm_type === 'liability')
                         ->sum(fn ($l) => (float) $l->credit - (float) $l->debit),
                    2
                );
                $equityNet  = round(
                    $legs->filter(fn ($l) => $l->_norm_type === 'equity')
                         ->sum(fn ($l) => (float) $l->credit - (float) $l->debit),
                    2
                );
                // Net assets introduced by this journal; equity must cover this amount
                $netAssetIntro  = round($assetNet - $liabNet, 2);
                $missingEquity  = round($netAssetIntro - $equityNet, 2);

                return (object) [
                    'reference'      => $legs->first()->reference ?? '—',
                    'date'           => $legs->first()->transaction_date,
                    'description'    => $legs->first()->description ?? '',
                    'legs'           => $legs->values(),
                    'leg_count'      => $legs->count(),
                    'has_equity_leg' => $hasEquityLeg,
                    'has_asset_leg'  => $hasAssetLeg,
                    'has_liab_leg'   => $hasLiabLeg,
                    'total_debit'    => $totalDr,
                    'total_credit'   => $totalCr,
                    'is_imbalanced'  => abs($totalDr - $totalCr) >= 0.01,
                    'asset_net'      => $assetNet,
                    'liab_net'       => $liabNet,
                    'equity_net'     => $equityNet,
                    'net_asset_intro'=> $netAssetIntro,
                    'missing_equity' => $missingEquity,
                    // Flag journals that introduced net assets but posted no equity credit
                    'flag'           => abs($missingEquity) >= 0.01,
                ];
            })
            ->sortByDesc(fn ($g) => abs($g->missing_equity))
            ->values();

        return [
            'available'                  => true,
            'has_opening_journals'        => true,
            'legs'                        => $allLegs,
            'by_reference'                => $byReference,
            'type_totals'                 => $typeTotals,
            'opening_asset_total'         => round($openingAssetTotal, 2),
            'opening_liability_total'     => round($openingLiabilityTotal, 2),
            'opening_equity_total'        => round($openingEquityTotal, 2),
            'opening_net_assets'          => $openingNetAssets,
            'required_equity_adjustment'  => $requiredEquityAdjustment,
            'flagged_refs'                => $byReference->filter(fn ($g) => $g->flag)->values(),
        ];
    }

    private function computeComparisonSnapshot(Request $request, Carbon $date, array $activeBranch, string $method): array
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return $this->emptySnapshot();
        }

        $consolidate = $request->boolean('consolidate');

        $txnQuery = Transaction::withoutGlobalScopes()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereNull('deleted_at')
            ->where('transaction_date', '<=', $date)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($q) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($q, $activeBranch);
            });
        $this->applyTransactionScope($txnQuery, $request);
        $txnTotals = $txnQuery->groupBy('account_id')->get()->keyBy('account_id');
        $snapshotLedgerDifference = round(
            (float) $txnTotals->sum(fn ($row) => (float) ($row->total_debit ?? 0))
            - (float) $txnTotals->sum(fn ($row) => (float) ($row->total_credit ?? 0)),
            2
        );

        $openingBalanceAccountIds = $this->openingBalanceAccountIds($request, $date, $activeBranch);
        $postedOpeningBalanceAccountIds = $this->postedOpeningBalanceAccountIds($request, $date, $activeBranch);
        $accountIds = collect($txnTotals->keys())
            ->merge($openingBalanceAccountIds)
            ->unique()
            ->values()
            ->all();
        $accountsQuery = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($accountIds) {
                if (!empty($accountIds)) {
                    $q->whereIn('id', $accountIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        // Account IDs already come from branch-scoped transactions/opening balances.
        // A second branch filter on account master rows can hide shared/global asset
        // accounts and create a false balance-sheet gap.
        $this->applyAccountScope($accountsQuery, $request);
        $accounts = $accountsQuery->get();

        $accounts->transform(function ($account) use ($txnTotals, $postedOpeningBalanceAccountIds) {
            $totals  = $txnTotals->get($account->id);
            $dr      = (float) ($totals->total_debit  ?? 0);
            $cr      = (float) ($totals->total_credit ?? 0);
            $type    = $this->normalizeAccountType($account->type ?? null);
            $isCreditNormal = in_array($type, ['liability', 'equity', 'revenue'], true);
            $ledgerBalance = $isCreditNormal ? ($cr - $dr) : ($dr - $cr);
            $openingBalance = in_array((int) $account->id, $postedOpeningBalanceAccountIds, true)
                ? 0.0
                : (float) ($account->opening_balance ?? 0);
            $account->balance = $ledgerBalance + $openingBalance;
            return $account;
        })->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.005)->values();

        $snapshot = $this->prepareStatementPresentation(
            $accounts,
            $request,
            $date,
            $activeBranch,
            $method,
            $snapshotLedgerDifference,
            $consolidate
        );

        return [
            'currentAssets' => $snapshot['currentAssets'],
            'fixedAssets' => $snapshot['fixedAssets'],
            'currentLiabilities' => $snapshot['currentLiabilities'],
            'longTermLiabilities' => $snapshot['longTermLiabilities'],
            'liabilities' => $snapshot['currentLiabilities']->concat($snapshot['longTermLiabilities'])->values(),
            'equity' => $snapshot['equity'],
            'equityCapital' => $snapshot['equityCapital'],
            'equityRetained' => $snapshot['equityRetained'],
            'equityReserves' => $snapshot['equityReserves'],
            'retainedEarningsLines' => $snapshot['retainedEarningsLines'],
            'netIncome' => $snapshot['netIncome'],
            'totalCurrentAssets' => $snapshot['totalCurrentAssets'],
            'totalFixedAssets' => $snapshot['totalFixedAssets'],
            'totalAssets' => $snapshot['totalAssets'],
            'totalCurrentLiabilities' => $snapshot['totalCurrentLiabilities'],
            'totalLongTermLiabilities' => $snapshot['totalLongTermLiabilities'],
            'totalLiabilities' => $snapshot['totalLiabilities'],
            'totalEquity' => $snapshot['totalEquity'],
            'retainedEarnings' => $snapshot['retainedEarnings'],
            'reconciliationReserveDiagnostic' => $snapshot['reconciliationReserveDiagnostic'],
            'reconciliationReserveNeedsReview' => $snapshot['reconciliationReserveNeedsReview'],
        ];
    }

    private function emptySnapshot(): array
    {
        $empty = collect();
        return [
            'currentAssets'      => $empty,
            'fixedAssets'        => $empty,
            'liabilities'        => $empty,
            'currentLiabilities' => $empty,
            'longTermLiabilities' => $empty,
            'equity'             => $empty,
            'equityCapital'      => $empty,
            'equityRetained'     => $empty,
            'equityReserves'     => $empty,
            'retainedEarningsLines' => $empty,
            'netIncome'          => 0.0,
            'totalCurrentAssets' => 0.0,
            'totalFixedAssets'   => 0.0,
            'totalAssets'        => 0.0,
            'totalCurrentLiabilities' => 0.0,
            'totalLongTermLiabilities' => 0.0,
            'totalLiabilities'   => 0.0,
            'totalEquity'        => 0.0,
            'retainedEarnings'   => 0.0,
            'reconciliationReserveDiagnostic' => 0.0,
            'reconciliationReserveNeedsReview' => false,
        ];
    }

    private function applyBranchScopeToTransactionsQuery($query, array $activeBranch): void
    {
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return;
        }

        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch);
    }

    private function reserveAndSuspenseDiagnostics(Request $request, Carbon $reportDate, array $activeBranch): Collection
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            return collect();
        }

        $query = Transaction::withoutGlobalScopes()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->where(function ($sub) {
                $sub->whereRaw('LOWER(accounts.name) like ?', ['%reconciliation%'])
                    ->orWhereRaw('LOWER(accounts.name) like ?', ['%suspense%'])
                    ->orWhereRaw('LOWER(accounts.sub_type) like ?', ['%reconciliation%'])
                    ->orWhereRaw('LOWER(accounts.sub_type) like ?', ['%suspense%'])
                    ->orWhereRaw('LOWER(accounts.code) like ?', ['%recon%'])
                    ->orWhereRaw('LOWER(accounts.code) like ?', ['%susp%']);
            })
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.reference',
                'transactions.transaction_type',
                'transactions.related_id',
                'transactions.related_type',
                'transactions.description',
                'transactions.debit',
                'transactions.credit',
                'transactions.branch_id',
                'transactions.branch_name',
                'accounts.name as account_name',
                'accounts.code as account_code',
            ])
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.id');

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);
        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $query->where('transactions.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $query->where('transactions.user_id', $userId);
        }

        if (($activeBranch['scope'] ?? 'branch') !== 'all') {
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            }
        }

        return $query->get();
    }

    private function openingBalanceValidationReport(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        $customerPostedRows = collect();
        $supplierPostedRows = collect();

        if (Schema::hasTable('transactions')) {
            $customerPostedQuery = Transaction::withoutGlobalScopes()
                ->selectRaw('reference, related_id, COUNT(*) as entry_count, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'CUST-OB-%')
                ->where('transaction_date', '<=', $reportDate)
                ->groupBy('reference', 'related_id');
            $this->applyTransactionScope($customerPostedQuery, $request);
            $this->applyBranchScopeToTransactionsQuery($customerPostedQuery, $activeBranch);
            $customerPostedRows = $customerPostedQuery->get();

            $supplierPostedQuery = Transaction::withoutGlobalScopes()
                ->selectRaw('reference, related_id, COUNT(*) as entry_count, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'SUPP-OB-%')
                ->where('transaction_date', '<=', $reportDate)
                ->groupBy('reference', 'related_id');
            $this->applyTransactionScope($supplierPostedQuery, $request);
            $this->applyBranchScopeToTransactionsQuery($supplierPostedQuery, $activeBranch);
            $supplierPostedRows = $supplierPostedQuery->get();
        }

        return [
            'unposted_customer_opening_balance' => round($this->getUnpostedCustomerOpeningBalanceSum($request, $reportDate, $activeBranch), 2),
            'unposted_supplier_opening_balance' => round($this->getUnpostedSupplierOpeningBalanceSum($request, $reportDate, $activeBranch), 2),
            'legacy_inventory_bridge' => round($this->getLegacyInventoryBridgeAmount($request, $reportDate, collect(), $activeBranch), 2),
            'duplicate_customer_refs' => $customerPostedRows->filter(fn ($row) => (int) ($row->entry_count ?? 0) > 2)->values(),
            'duplicate_supplier_refs' => $supplierPostedRows->filter(fn ($row) => (int) ($row->entry_count ?? 0) > 2)->values(),
            'imbalanced_customer_refs' => $customerPostedRows->filter(fn ($row) => abs((float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0)) >= 0.01)->values(),
            'imbalanced_supplier_refs' => $supplierPostedRows->filter(fn ($row) => abs((float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0)) >= 0.01)->values(),
        ];
    }

    private function applyAccountScope($query, Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) ($request->user()?->id ?? 0);

        // Chart-of-accounts rows created before the tenant-columns migration have
        // company_id = NULL.  We treat them as "global" accounts belonging to every
        // company so they are never silently excluded.  Without this, asset accounts
        // disappear from the balance sheet while Current Year Earnings (computed via
        // a transaction-side JOIN that never touches accounts.company_id) still shows
        // the full figure — producing a phantom ₦X gap.
        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhereNull('company_id');
            });
        } elseif ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            });
        }

        return $query;
    }

    private function openingBalanceAccountIds(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        if (!Schema::hasTable('accounts') || !Schema::hasColumn('accounts', 'opening_balance')) {
            return [];
        }

        $query = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereRaw('ABS(COALESCE(opening_balance, 0)) > 0.005');

        if (Schema::hasColumn('accounts', 'created_at')) {
            $query->where(function ($created) use ($reportDate) {
                $created->whereNull('created_at')
                    ->orWhereDate('created_at', '<=', $reportDate->toDateString());
            });
        }

        if (($activeBranch['scope'] ?? 'branch') !== 'all') {
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if (($branchId !== '' || $branchName !== '') && (Schema::hasColumn('accounts', 'branch_id') || Schema::hasColumn('accounts', 'branch_name'))) {
                $query->where(function ($branchQuery) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('accounts', 'branch_id')) {
                        $branchQuery->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('accounts', 'branch_name')) {
                        $method = ($branchId !== '' && Schema::hasColumn('accounts', 'branch_id')) ? 'orWhere' : 'where';
                        $branchQuery->{$method}('branch_name', $branchName);
                    }
                    if (Schema::hasColumn('accounts', 'branch_id')) {
                        $branchQuery->orWhereNull('branch_id')->orWhere('branch_id', '');
                    }
                });
            }
        }

        $this->applyAccountScope($query, $request);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function postedOpeningBalanceAccountIds(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        if (!Schema::hasTable('transactions')) {
            return [];
        }

        $query = Transaction::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('transaction_date', '<=', $reportDate->toDateString());

        $this->applyTransactionScope($query, $request);
        $this->applyBranchScopeToTransactionsQuery($query, $activeBranch);

        return $query->distinct()->pluck('account_id')->filter()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Sum customer opening balances (customers.balance > 0) that do NOT yet
     * have a journal entry in the transactions table (reference CUST-OB-*).
     * This bridges existing customers who pre-date the journal-entry workflow.
     */
    private function getUnpostedCustomerOpeningBalanceSum(Request $request, $reportDate, array $activeBranch = []): float
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'balance')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) ($request->user()?->id ?? 0);
        $branchId  = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';

        // Find customer IDs that already have journal entries posted (DR leg only).
        // Only exclude IDs that still exist as active customers — orphaned CUST-OB-*
        // transactions from deleted customers must not block real customers from showing.
        $postedCustomerIds = [];
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference')) {
            $postedQuery = Transaction::withoutGlobalScopes()
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'CUST-OB-%')
                ->where('debit', '>', 0);
            if ($companyId > 0) {
                $postedQuery->where('company_id', $companyId);
            } elseif ($userId > 0) {
                $postedQuery->where('user_id', $userId);
            }
            $rawPostedIds = $postedQuery->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->all();
            // Cross-check: keep only IDs that still exist in customers table
            if (!empty($rawPostedIds)) {
                $postedCustomerIds = DB::table('customers')
                    ->whereIn('id', $rawPostedIds)
                    ->pluck('id')
                    ->all();
            }
        }

        $customerQuery = DB::table('customers')
            ->where('balance', '>', 0)
            ->where(function ($q) use ($reportDate) {
                $q->whereNull('opening_balance_date')
                  ->orWhere('opening_balance_date', '<=', $reportDate->toDateString());
            });

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $customerQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
            $customerQuery->where('user_id', $userId);
        }

        // Branch scope: filter by branch_id/branch_name if customers table supports it.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('customers', 'branch_id')) {
            $this->applyExactBranchScope($customerQuery, $branchId, Schema::hasColumn('customers', 'branch_name') ? $branchName : '', 'branch_id', 'branch_name');
        }

        if (!empty($postedCustomerIds)) {
            $customerQuery->whereNotIn('id', $postedCustomerIds);
        }

        return (float) $customerQuery->sum('balance');
    }

    private function getUnpostedSupplierOpeningBalanceSum(Request $request, $reportDate, array $activeBranch = []): float
    {
        if (!Schema::hasTable('suppliers') || !Schema::hasColumn('suppliers', 'opening_balance')) {
            return 0.0;
        }

        $companyId  = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId     = (int) ($request->user()?->id ?? 0);
        $branchId   = trim((string) ($activeBranch['id']   ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';

        $postedSupplierIds = [];
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference')) {
            $postedQuery = Transaction::withoutGlobalScopes()
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'SUPP-OB-%')
                ->where('credit', '>', 0);
            if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
                $postedQuery->where('company_id', $companyId);
            } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
                $postedQuery->where('user_id', $userId);
            }
            $postedSupplierIds = $postedQuery->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->all();
        }

        $supplierQuery = DB::table('suppliers')->where('opening_balance', '>', 0);
        if (Schema::hasColumn('suppliers', 'opening_balance_date')) {
            $supplierQuery->where(function ($q) use ($reportDate) {
                $q->whereNull('opening_balance_date')
                    ->orWhere('opening_balance_date', '<=', $reportDate->toDateString());
            });
        }
        if ($companyId > 0 && Schema::hasColumn('suppliers', 'company_id')) {
            $supplierQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('suppliers', 'user_id')) {
            $supplierQuery->where('user_id', $userId);
        }

        // Branch scope: filter by branch_id/branch_name if suppliers table supports it.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('suppliers', 'branch_id')) {
            $this->applyExactBranchScope($supplierQuery, $branchId, Schema::hasColumn('suppliers', 'branch_name') ? $branchName : '', 'branch_id', 'branch_name');
        }

        if (!empty($postedSupplierIds)) {
            $supplierQuery->whereNotIn('id', $postedSupplierIds);
        }

        return (float) $supplierQuery->sum('opening_balance');
    }

    private function getLegacyInventoryBridgeAmount(Request $request, $reportDate, $accounts, array $activeBranch = []): float
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'stock')) {
            return 0.0;
        }

        $priceColumn = Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (Schema::hasColumn('products', 'price') ? 'price' : null);
        if ($priceColumn === null) {
            return 0.0;
        }

        $companyId  = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId     = (int) ($request->user()?->id ?? 0);
        $branchId   = trim((string) ($activeBranch['id']   ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $productQuery = DB::table('products')
            ->where('stock', '>', 0)
            ->selectRaw("SUM(COALESCE(stock, 0) * COALESCE({$priceColumn}, 0)) as inventory_value");

        if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
            $productQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('products', 'user_id')) {
            $productQuery->where('user_id', $userId);
        }

        // Branch scope: filter products by branch_id if the column exists.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('products', 'branch_id')) {
            $productQuery->where(function ($q) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $q->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                    $q->orWhere('branch_name', $branchName);
                }
                // Include products with no branch assignment
                $q->orWhereNull('branch_id')->orWhere('branch_id', '');
            });
        }

        $inventoryValue = (float) ($productQuery->value('inventory_value') ?? 0);
        if ($inventoryValue <= 0.01) {
            return 0.0;
        }

        $ledgerInventory = (float) $accounts
            ->filter(fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'inventory')
                || str_contains(strtolower((string) ($a->name ?? '')), 'stock'))
            ->sum('balance');

        return max(0.0, round($inventoryValue - max(0.0, $ledgerInventory), 2));
    }

    private function getAccountBalances($date)
    {
        // Check if accounts and transactions tables exist
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return collect([]);
        }
        
        $accounts = Account::with(['transactions' => function($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }])->get();

        return $accounts->map(function($account) {
            // Calculate balance based on account type
            $debits = $account->transactions->sum('debit');
            $credits = $account->transactions->sum('credit');
            
            // Assets & Expenses: Debit increases, Credit decreases
            // Liabilities, Equity & Revenue: Credit increases, Debit decreases
            if (in_array($account->type, ['Asset', 'Expense'])) {
                $balance = $debits - $credits;
            } else {
                $balance = $credits - $debits;
            }

            $account->balance = abs($balance);
            return $account;
        })->where('balance', '>', 0);
    }

    /** Balance Sheet Summary — same data, summary-only view */
    public function summary(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $reportDate   = $request->date ? Carbon::parse($request->date) : Carbon::now();

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.balance-sheet-summary', [
                'reportDate' => $reportDate, 'totalAssets' => 0, 'totalLiabilities' => 0,
                'totalEquity' => 0, 'retainedEarnings' => 0, 'activeBranch' => $activeBranch,
            ]);
        }

        $method = in_array($request->input('accounting_method'), ['cash', 'accrual'], true)
            ? $request->input('accounting_method')
            : 'accrual';
        $snapshot = $this->computeComparisonSnapshot($request, $reportDate, $activeBranch, $method);
        $totalAssets = $snapshot['totalAssets'];
        $totalLiabilities = $snapshot['totalLiabilities'];
        $totalEquity = $snapshot['totalEquity'];
        $retainedEarnings = (float) ($snapshot['retainedEarnings'] ?? 0) + (float) ($snapshot['netIncome'] ?? 0);

        return view('Reports.Reports.balance-sheet-summary', compact('reportDate', 'totalAssets', 'totalLiabilities', 'totalEquity', 'retainedEarnings', 'activeBranch'));
    }

    /** Balance Sheet Comparison — two dates side by side */
    public function comparison(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $dateA = $request->input('date_a') ? Carbon::parse($request->input('date_a')) : Carbon::now();
        $dateB = $request->input('date_b') ? Carbon::parse($request->input('date_b')) : Carbon::now()->subYear();

        $build = function (Carbon $reportDate) use ($request, $activeBranch) {
            if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
                return ['assets' => 0, 'liabilities' => 0, 'equity' => 0, 'retained' => 0];
            }
            $method = in_array($request->input('accounting_method'), ['cash', 'accrual'], true)
                ? $request->input('accounting_method')
                : 'accrual';
            $snapshot = $this->computeComparisonSnapshot($request, $reportDate, $activeBranch, $method);

            return [
                'assets'      => $snapshot['totalAssets'],
                'liabilities' => $snapshot['totalLiabilities'],
                'equity'      => $snapshot['totalEquity'],
                'retained'    => (float) ($snapshot['retainedEarnings'] ?? 0) + (float) ($snapshot['netIncome'] ?? 0),
            ];
        };

        $periodA = $build($dateA);
        $periodB = $build($dateB);
        return view('Reports.Reports.balance-sheet-comparison', compact('dateA', 'dateB', 'periodA', 'periodB', 'activeBranch'));
    }

    /**
     * Calculate retained earnings
     */
    private function calculateRetainedEarnings($date)
    {
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return 0;
        }

        // Revenue
        $revenue = Account::where('type', 'Revenue')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('credit') - $account->transactions->sum('debit');
            });

        // Expenses
        $expenses = Account::where('type', 'Expense')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        // Dividends
        $dividends = Account::where('name', 'like', '%dividend%')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        return $revenue - $expenses - $dividends;
    }

}
