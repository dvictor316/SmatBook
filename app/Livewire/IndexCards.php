<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class IndexCards extends Component
{
    // Cache key constant for easy maintenance
    protected const CACHE_KEY = 'company_counts';

    public function getCounts()
    {
        // Retrieve data from cache or execute queries if cache misses
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            return [
                'totalCompanies' => DB::table('companies')->count(),
                'activeCompanies' => DB::table('companies')->where('status', 'active')->count(),
                'inactiveCompanies' => DB::table('companies')->where('status', '!=', 'active')->count(),
                'newCompaniesToday' => DB::table('companies')->whereDate('created_at', now()->toDateString())->count(),
            ];
        });
    }

    public function render()
    {
        $counts = $this->getCounts();

        $currencySymbol = '₦';

        // Pass data to the Blade view
        return view('livewire.index-cards', array_merge($counts, ['currencySymbol' => $currencySymbol]));
    }
}