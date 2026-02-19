<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ProfitLossList extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;

    protected $listeners = ['filterData' => 'applyFilter'];

    /**
     * Apply date range filter
     */
    public function applyFilter($start, $end)
    {
        // Accept either array with 'start'/'end' keys or plain string
        $this->startDate = is_array($start) ? $start['start'] : $start;
        $this->endDate = is_array($end) ? $end['end'] : $end;

        // Reset to first page when filter changes
        $this->resetPage();
    }

    /**
     * Render the profit & loss data
     */
    public function render()
    {
        // Define the date expression for grouping
        $dateExpr = DB::raw('DATE(created_at)');

        // Build the main query
        $query = DB::table('sales')
            ->select([
                // Select date as 'report_date'
                DB::raw('DATE(created_at) as report_date'),
                // Sum total as 'daily_income'
                DB::raw('SUM(total) as daily_income'),
                // Subquery for expenses on the same date
                DB::raw('(SELECT SUM(amount) FROM expenses WHERE DATE(created_at) = DATE(sales.created_at)) as daily_expense')
            ]);

        // Apply date range filter if set
        if ($this->startDate && $this->endDate) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$this->startDate, $this->endDate]);
        }

        // Group by date and order descending
        $profitLossData = $query->groupBy('report_date')
                                ->orderBy('report_date', 'desc')
                                ->paginate(10);

        return view('livewire.profit-loss-list', [
            'profitLossData' => $profitLossData
        ]);
    }
}