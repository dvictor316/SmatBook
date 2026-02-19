<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    public function cashFlow(Request $request)
    {
        // 1. Setup Dates (Matches your $start->format calls in Blade)
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        // 2. Identify Cash/Bank Accounts
        $cashAccountIds = Account::whereIn('sub_type', ['Bank', 'Cash', 'Cash and Bank'])->pluck('id');

        // 3. Calculate Opening Balance (Net sum of transactions before the period)
        $openingBalance = Transaction::whereIn('account_id', $cashAccountIds)
            ->where('transaction_date', '<', $start->toDateString())
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->first()->balance ?? 0;

        // 4. Period Transactions
        $transactions = Transaction::whereIn('account_id', $cashAccountIds)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->with('account')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Separate Inflows and Outflows for the loops in your Blade
        $inflows = $transactions->where('debit', '>', 0);
        $outflows = $transactions->where('credit', '>', 0);

        // 5. Calculate Summary Totals
        $totalInflow = $inflows->sum('debit');
        $totalOutflow = $outflows->sum('credit');
        $netCashFlow = $totalInflow - $totalOutflow;
        $closingBalance = $openingBalance + $netCashFlow;

        // 6. Chart Logic (For the last 6 months)
        $chartData = $this->getMonthlyTrend($cashAccountIds);

        // Returning variables that match your Blade exactly
        return view('Reports.Reports.cash-flow', compact(
            'start',
            'end',
            'openingBalance',
            'inflows',
            'outflows',
            'totalInflow',
            'totalOutflow',
            'netCashFlow',
            'closingBalance',
            'chartData'
        ));
    }

    private function getMonthlyTrend($cashAccountIds)
    {
        $labels = [];
        $inflows = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            
            $inflows[] = Transaction::whereIn('account_id', $cashAccountIds)
                ->whereMonth('transaction_date', $month->month)
                ->whereYear('transaction_date', $month->year)
                ->sum('debit');

            $outflows[] = Transaction::whereIn('account_id', $cashAccountIds)
                ->whereMonth('transaction_date', $month->month)
                ->whereYear('transaction_date', $month->year)
                ->sum('credit');
        }

        return ['labels' => $labels, 'inflows' => $inflows, 'outflows' => $outflows];
    }

    public function exportCashFlow(Request $request): StreamedResponse
    {
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        $cashAccountIds = Account::whereIn('sub_type', ['Bank', 'Cash', 'Cash and Bank'])->pluck('id');

        $openingBalance = Transaction::whereIn('account_id', $cashAccountIds)
            ->where('transaction_date', '<', $start->toDateString())
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->first()->balance ?? 0;

        $transactions = Transaction::whereIn('account_id', $cashAccountIds)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->with('account')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $inflows = $transactions->where('debit', '>', 0);
        $outflows = $transactions->where('credit', '>', 0);
        $totalInflow = $inflows->sum('debit');
        $totalOutflow = $outflows->sum('credit');
        $netCashFlow = $totalInflow - $totalOutflow;
        $closingBalance = $openingBalance + $netCashFlow;

        $filename = 'cash_flow_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';

        return response()->streamDownload(function () use (
            $start,
            $end,
            $openingBalance,
            $inflows,
            $outflows,
            $totalInflow,
            $totalOutflow,
            $netCashFlow,
            $closingBalance
        ) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Cash Flow Report']);
            fputcsv($out, ['Period', $start->toDateString() . ' to ' . $end->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['Summary']);
            fputcsv($out, ['Opening Balance', $openingBalance]);
            fputcsv($out, ['Total Inflows', $totalInflow]);
            fputcsv($out, ['Total Outflows', $totalOutflow]);
            fputcsv($out, ['Net Cash Flow', $netCashFlow]);
            fputcsv($out, ['Closing Balance', $closingBalance]);
            fputcsv($out, []);

            fputcsv($out, ['Inflows']);
            fputcsv($out, ['Date', 'Description', 'Amount']);
            foreach ($inflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? optional($item->account)->name ?? '',
                    $item->debit,
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Outflows']);
            fputcsv($out, ['Date', 'Description', 'Amount']);
            foreach ($outflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? optional($item->account)->name ?? '',
                    $item->credit,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
