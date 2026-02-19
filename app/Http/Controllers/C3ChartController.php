<?php
// Example Controller to fetch data needed for C3 charts

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
// ... other imports

class C3ChartController extends Controller
{
    public function showC3Charts()
    {
        // Sample Dynamic Data: Company Status Counts (for Pie/Donut Chart)
        $activeCompanies = Company::where('status', 'active')->count();
        $inactiveCompanies = Company::where('status', 'inactive')->count();
        $companiesWithAddress = Company::whereNotNull('address')->count();
        $totalCompanies = Company::count();

        // Sample Dynamic Data: Monthly Company Creation (for Bar Chart)
        $companyCreationData = Company::select(
                DB::raw('MONTHNAME(created_at) as month_name'),
                DB::raw('count(id) as count')
            )
            ->groupBy('month_name')
            ->orderByRaw('MIN(created_at)') // Order by creation time to get correct month order
            ->get();
            
        $months = $companyCreationData->pluck('month_name');
        $counts = $companyCreationData->pluck('count');
        // C3 prefers data formatted slightly differently for columns:
        // ['data1', 30, 200, 100, 400, 150, 250] where 'data1' is the label
        $barChartData = array_merge(['Companies Created'], $counts->toArray());


        return view('chart-c3', compact(
            'activeCompanies',
            'inactiveCompanies',
            'companiesWithAddress',
            'totalCompanies',
            'months',
            'barChartData'
        ));
    }
}
