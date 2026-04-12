<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Make sure Carbon is available
use App\Support\GeoCurrency;

class IndexSales extends Component
{
    // ... (Your previous methods for total sales etc. are fine) ...

    /**
     * Fetch monthly sales data for the current year.
     *
     * @return array
     */
    private function getMonthlySalesDataForChart()
    {
        $currentYear = Carbon::now()->year;
        
        // This query sums up invoice amounts month by month for the current year
        $salesData = DB::table('invoices')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total_sales')
            )
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Initialize an array for all 12 months with 0 sales
        $monthlySales = array_fill(1, 12, 0);

        // Map the database results into the monthly array
        foreach ($salesData as $sale) {
            $monthlySales[$sale->month] = (float) $sale->total_sales;
        }

        // Return just the values in correct order (January to December)
        return array_values($monthlySales);
    }

    public function render()
    {
        // ... (Your existing sales stats calculations) ...
        $totalSales = (float) DB::table('invoices')->sum('amount');
        $totalReceipts = 0.00; 
        $totalExpenses = 0.00;
        $totalEarnings = $totalReceipts - $totalExpenses;

        $salesStats = [
            'total_sales'    => $totalSales,
            'total_receipts' => $totalReceipts,
            'total_expenses' => $totalExpenses,
            'total_earnings' => $totalEarnings,
        ];
        
        // --- New dynamic data for the chart ---
        $chartData = $this->getMonthlySalesDataForChart();
        // ------------------------------------

        $currencySymbol = GeoCurrency::currentSymbol();

        // Pass all data to the view
        return view('livewire.index-sales', compact('salesStats', 'currencySymbol', 'chartData'));
    }
}
