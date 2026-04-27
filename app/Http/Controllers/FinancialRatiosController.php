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
        $period    = $request->input('period', now()->format('Y'));

        // Pull aggregated figures from journal entries / ledger.
        // These queries are intentionally lightweight; a full implementation
        // would hit the accounts/ledger service layer.
        $revenue     = $this->sumLedger($companyId, 'revenue', $period);
        $cogs        = $this->sumLedger($companyId, 'cogs', $period);
        $opex        = $this->sumLedger($companyId, 'operating_expense', $period);
        $netIncome   = $revenue - $cogs - $opex;
        $totalAssets = $this->sumLedger($companyId, 'assets', $period);
        $totalDebt   = $this->sumLedger($companyId, 'liabilities', $period);
        $equity      = $totalAssets - $totalDebt;
        $currentAssets      = $this->sumLedger($companyId, 'current_assets', $period);
        $currentLiabilities = $this->sumLedger($companyId, 'current_liabilities', $period);
        $inventory   = $this->sumLedger($companyId, 'inventory', $period);

        $ratios = [
            'gross_margin'        => $revenue > 0 ? round(($revenue - $cogs) / $revenue * 100, 2) : null,
            'net_profit_margin'   => $revenue > 0 ? round($netIncome / $revenue * 100, 2) : null,
            'current_ratio'       => $currentLiabilities > 0 ? round($currentAssets / $currentLiabilities, 2) : null,
            'quick_ratio'         => $currentLiabilities > 0 ? round(($currentAssets - $inventory) / $currentLiabilities, 2) : null,
            'debt_to_equity'      => $equity > 0 ? round($totalDebt / $equity, 2) : null,
            'return_on_assets'    => $totalAssets > 0 ? round($netIncome / $totalAssets * 100, 2) : null,
            'return_on_equity'    => $equity > 0 ? round($netIncome / $equity * 100, 2) : null,
            'asset_turnover'      => $totalAssets > 0 ? round($revenue / $totalAssets, 2) : null,
        ];

        return view('reports.financial-ratios', compact('ratios', 'period', 'revenue', 'netIncome'));
    }

    private function sumLedger(int $companyId, string $category, string $period): float
    {
        // Placeholder — replace with actual ledger/account-balance query
        // scoped to the period year and company.
        return 0.0;
    }
}
