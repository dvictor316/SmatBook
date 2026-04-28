<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinancialRatiosController extends Controller
{
    /**
     * Display calculated financial ratios dashboard.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $period    = $request->input('period', 'ytd');

        // Pull aggregated figures from journal entries / ledger.
        // These queries are intentionally lightweight; a full implementation
        // would hit the accounts/ledger service layer.
        $revenue            = $this->sumLedger($companyId, 'revenue', $period);
        $cogs               = $this->sumLedger($companyId, 'cogs', $period);
        $opex               = $this->sumLedger($companyId, 'operating_expense', $period);
        $interestExpense    = $this->sumLedger($companyId, 'interest_expense', $period);
        $netIncome          = $revenue - $cogs - $opex - $interestExpense;
        $ebit               = $revenue - $cogs - $opex;
        $totalAssets        = $this->sumLedger($companyId, 'assets', $period);
        $totalDebt          = $this->sumLedger($companyId, 'liabilities', $period);
        $equity             = max($totalAssets - $totalDebt, 0);
        $currentAssets      = $this->sumLedger($companyId, 'current_assets', $period);
        $currentLiabilities = $this->sumLedger($companyId, 'current_liabilities', $period);
        $cashEquivalents    = $this->sumLedger($companyId, 'cash', $period);
        $inventory          = $this->sumLedger($companyId, 'inventory', $period);
        $receivables        = $this->sumLedger($companyId, 'receivables', $period);
        $payables           = $this->sumLedger($companyId, 'payables', $period);

        $ratios = [
            // Liquidity
            'current_ratio'      => $currentLiabilities > 0 ? round($currentAssets / $currentLiabilities, 2) : 0,
            'quick_ratio'        => $currentLiabilities > 0 ? round(($currentAssets - $inventory) / $currentLiabilities, 2) : 0,
            'cash_ratio'         => $currentLiabilities > 0 ? round($cashEquivalents / $currentLiabilities, 2) : 0,
            'working_capital'    => $currentAssets - $currentLiabilities,
            // Profitability
            'gross_margin'       => $revenue > 0 ? round(($revenue - $cogs) / $revenue * 100, 2) : 0,
            'net_margin'         => $revenue > 0 ? round($netIncome / $revenue * 100, 2) : 0,
            'roa'                => $totalAssets > 0 ? round($netIncome / $totalAssets * 100, 2) : 0,
            'roe'                => $equity > 0 ? round($netIncome / $equity * 100, 2) : 0,
            // Leverage
            'debt_to_equity'     => $equity > 0 ? round($totalDebt / $equity, 2) : 0,
            'debt_ratio'         => $totalAssets > 0 ? round($totalDebt / $totalAssets, 2) : 0,
            'interest_coverage'  => $interestExpense > 0 ? round($ebit / $interestExpense, 2) : 0,
            'equity_multiplier'  => $equity > 0 ? round($totalAssets / $equity, 2) : 0,
            // Efficiency
            'inventory_turnover' => $inventory > 0 ? round($cogs / $inventory, 2) : 0,
            'dso'                => $revenue > 0 ? round($receivables / ($revenue / 365), 1) : 0,
            'ap_days'            => $cogs > 0 ? round($payables / ($cogs / 365), 1) : 0,
            'asset_turnover'     => $totalAssets > 0 ? round($revenue / $totalAssets, 2) : 0,
        ];

        return view('Reports.financial-ratios', compact('ratios', 'period', 'revenue', 'netIncome'));
    }

    private function sumLedger(int $companyId, string $category, string $period): float
    {
        // Placeholder — replace with actual ledger/account-balance query
        // scoped to the period year and company.
        return 0.0;
    }
}
