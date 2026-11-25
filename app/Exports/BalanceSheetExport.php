<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BalanceSheetExport implements FromArray, WithHeadings
{
    public function __construct(private readonly array $report) {}

    public function array(): array
    {
        $sections = [
            ['currentAssets', 'Current Asset', 'Total Current Assets', 'totalCurrentAssets'],
            ['fixedAssets', 'Fixed Asset', 'Total Fixed Assets', 'totalFixedAssets'],
            ['currentLiabilities', 'Current Liability', 'Total Current Liabilities', 'totalCurrentLiabilities'],
            ['longTermLiabilities', 'Long-term Liability', 'Total Long-term Liabilities', 'totalLongTermLiabilities'],
            ['equity', 'Equity', 'Total Equity', 'totalEquity'],
        ];
        $rows = [];

        foreach ($sections as [$key, $type, $label, $total]) {
            foreach ($this->report[$key] ?? [] as $account) {
                $rows[] = [$account->_display_name ?? $account->name, $type, (float) $account->balance];
            }
            $rows[] = [$label, '', (float) ($this->report[$total] ?? 0)];
        }

        $assets = (float) ($this->report['totalAssets'] ?? 0);
        $liabilities = (float) ($this->report['totalLiabilities'] ?? 0);
        $equity = (float) ($this->report['totalEquity'] ?? 0);
        $rows[] = ['Total Assets', '', $assets];
        $rows[] = ['Total Liabilities', '', $liabilities];
        $rows[] = ['Total Liabilities & Equity', '', round($liabilities + $equity, 2)];
        $rows[] = ['Statement Difference', '', round($assets - $liabilities - $equity, 2)];

        return $rows;
    }

    public function headings(): array
    {
        return ['Account Name', 'Type', 'Balance'];
    }
}
