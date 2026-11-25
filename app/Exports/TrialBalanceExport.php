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
    ) {}

    public function collection(): Collection
    {
        return $this->accounts->map(fn ($account) => [
            $account->code,
            $account->name,
            $account->type,
            $account->debit_balance,
            $account->credit_balance,
        ])->push(['', 'Totals', '', $this->totalDebits, $this->totalCredits]);
    }

    public function headings(): array
    {
        return ['Account Code', 'Account Name', 'Account Type', 'Debit Balance', 'Credit Balance'];
    }
}
