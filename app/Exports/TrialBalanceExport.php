<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $accounts = Account::withSum(['transactions as total_debit' => function ($query) {
            $query->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
        }], 'debit')
        ->withSum(['transactions as total_credit' => function ($query) {
            $query->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
        }], 'credit')
        ->get();

        return $accounts->map(function ($account) {
            $dr = (float) ($account->total_debit ?? 0);
            $cr = (float) ($account->total_credit ?? 0);
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
