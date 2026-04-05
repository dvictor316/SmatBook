<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $userId;
    protected $branchId;
    protected $branchName;
    protected $branchScope;

    public function __construct($startDate, $endDate, int $companyId = 0, int $userId = 0, ?string $branchId = null, ?string $branchName = null, string $branchScope = 'branch')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->branchId = $branchId;
        $this->branchName = $branchName;
        $this->branchScope = $branchScope;
    }

    public function collection()
    {
        $txnQuery = \App\Models\Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->groupBy('account_id');

        if ($this->branchScope !== 'all') {
            $branchId = trim((string) ($this->branchId ?? ''));
            $branchName = trim((string) ($this->branchName ?? ''));
            $txnQuery->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '') {
                    $sub->orWhere('branch_name', $branchName);
                }
            });
        }

        $txnTotals = $txnQuery->get()->keyBy('account_id');
        $accountIds = $txnTotals->keys()->all();

        if (empty($accountIds)) {
            return collect();
        }

        $accounts = Account::withoutGlobalScope('tenant')
            ->whereIn('id', $accountIds)
            ->where(function ($q) {
                if ($this->companyId > 0) {
                    $q->where('company_id', $this->companyId)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('company_id')
                              ->where('user_id', $this->userId);
                      });
                } elseif ($this->userId > 0) {
                    $q->where('user_id', $this->userId);
                }
            })
            ->get();

        return $accounts->map(function ($account) {
            $totals = $txnTotals->get($account->id);
            $dr = (float) ($totals->total_debit ?? 0);
            $cr = (float) ($totals->total_credit ?? 0);
            $net = $dr - $cr;

            $debitBalance = 0.0;
            $creditBalance = 0.0;

            if (in_array($account->type, ['Asset', 'Expense'])) {
                if ($net >= 0) {
                    $debitBalance = $net;
                } else {
                    $creditBalance = abs($net);
                }
            } else {
                if ($net <= 0) {
                    $creditBalance = abs($net);
                } else {
                    $debitBalance = $net;
                }
            }

            return [
                $account->code ?? 'N/A',
                $account->name,
                $account->type,
                $debitBalance,
                $creditBalance,
            ];
        })->filter(fn ($row) => ($row[3] > 0 || $row[4] > 0))->values();
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Account Type',
            'Debit Balance',
            'Credit Balance',
        ];
    }
}
