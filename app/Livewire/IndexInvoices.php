<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Support\GeoCurrency;

class IndexInvoices extends Component
{
    // Cache key constant for invoice analytics
    protected const INVOICE_STATS_CACHE_KEY = 'dashboard_invoice_stats';

    /**
     * Fetch and cache invoice analytics data.
     *
     * @return array
     */
    protected function getInvoiceStats()
    {
        return Cache::remember(self::INVOICE_STATS_CACHE_KEY, 60 * 30, function () {
            // Fetch total amounts by status
            $statusTotals = DB::table('invoices')
                ->select('status', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('status')
                ->get()
                ->keyBy('status'); // Key the collection by status name

            $invoiced = (float) $statusTotals->sum('total_amount');
            $paid = (float) ($statusTotals->get('Paid')->total_amount ?? 0);
            $pending = (float) $invoiced - $paid; // Calculate pending based on total minus paid

            return [
                'invoiced' => $invoiced,
                'received' => $paid,
                'pending'  => $pending,
                'statusTotals' => $statusTotals // This key is correctly set here
            ];
        }) 
        // --- FIX IS HERE: Add 'statusTotals' to the fallback array ---
        ?? ['invoiced' => 0, 'received' => 0, 'pending' => 0, 'statusTotals' => collect()]; 
        // -----------------------------------------------------------
    }

    /**
     * Render the component with recent invoices and analytics.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Fetch recent invoices with customer data
        $invoices = Invoice::with('customer')
                           ->latest()
                           ->take(5)
                           ->get();

        // Fetch analytics data, with caching
        $invoiceStats = $this->getInvoiceStats();
        
        // Calculate percentages for progress bars
        $percentages = [];
        // The check below ensures we don't divide by zero and statusTotals exists
        if ($invoiceStats['invoiced'] > 0 && !empty($invoiceStats['statusTotals'])) {
            foreach ($invoiceStats['statusTotals'] as $statusData) {
                $percent = ($statusData->total_amount / $invoiceStats['invoiced']) * 100;
                $percentages[Str::slug($statusData->status)] = round($percent, 1);
            }
        }

        // Fetch currency symbol from config, fallback to default
        $currencySymbol = GeoCurrency::currentSymbol();

        // Pass data to the view, including the new percentages
        return view('livewire.index-invoices', compact('invoices', 'invoiceStats', 'currencySymbol', 'percentages'));
    }
}
