<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Collection $accounts,
        private readonly float $totalDebits,
        private readonly float $totalCredits,
        private readonly Collection $unpostedOpenings,
    ) {}

    public function collection(): Collection
    {
        $rows = $this->accounts->map(fn ($account) => [
            $account->code,
            $account->name,
            $account->type,
            $account->debit_balance,
            $account->credit_balance,
        ])->push(['', 'Totals', '', $this->totalDebits, $this->totalCredits]);

        foreach ($this->unpostedOpenings as $opening) {
            $rows->push([$opening['code'], $opening['name'].' (account #'.$opening['id'].') · Saved amount '.$opening['amount'],
                'Unposted opening for review', null, null]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Account Code', 'Account Name', 'Account Type', 'Debit Balance', 'Credit Balance'];
    }
}
