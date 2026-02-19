<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Estimate;
use Illuminate\Support\Facades\Cache;

class IndexEstimates extends Component
{
    // Cache key constant
    protected const CACHE_KEY = 'dashboard_estimates_stats';

    /**
     * Retrieve estimate data with caching.
     *
     * @return array
     */
    protected function getEstimateData()
    {
        return Cache::remember(self::CACHE_KEY, 60 * 60, function () {
            // Fetch counts by status
            $sent = Estimate::where('status', 'sent')->count();
            $draft = Estimate::where('status', 'draft')->count();
            $expired = Estimate::where('status', 'expired')->count();

            // Fetch latest 5 estimates with customer data to prevent N+1 issues
            $estimates = Estimate::with('customer:id,name')->latest()->take(5)->get();

            return compact('sent', 'draft', 'expired', 'estimates');
        }) ?? ['sent' => 0, 'draft' => 0, 'expired' => 0, 'estimates' => []];
    }

    /**
     * Render the Livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $data = $this->getEstimateData();

        return view('livewire.index-estimates', $data);
    }
}
