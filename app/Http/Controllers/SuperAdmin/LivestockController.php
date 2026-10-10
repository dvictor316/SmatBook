<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\LivestockAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LivestockController extends Controller
{
    public function index(Request $request)
    {
        $companyIds = LivestockAccess::livestockCompanyIds();
        $companies = empty($companyIds)
            ? collect()
            : Company::withoutGlobalScopes()->whereIn('id', $companyIds)->orderBy('name')->get(['id', 'name']);

        $selectedCompanyId = $request->integer('company_id');
        if ($selectedCompanyId && ! in_array($selectedCompanyId, $companyIds, true)) {
            abort(404);
        }

        $scopeIds = $selectedCompanyId ? [$selectedCompanyId] : $companyIds;
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $metrics = [
            'tenants' => count($companyIds),
            'farms' => $this->count('livestock_farms', $scopeIds, fn ($query) => $query->where('is_active', true)),
            'active_flocks' => $this->count('livestock_flocks', $scopeIds, fn ($query) => $query->where('status', 'active')),
            'birds' => $this->sum('livestock_flocks', 'current_birds', $scopeIds, fn ($query) => $query->where('status', 'active')),
            'eggs' => $this->sum('livestock_daily_productions', 'total_good_eggs', $scopeIds, fn ($query) => $query->whereBetween('production_date', [$from, $to])),
            'feed_kg' => $this->sum('livestock_daily_productions', 'feed_kg', $scopeIds, fn ($query) => $query->whereBetween('production_date', [$from, $to])),
            'mortality' => $this->sum('livestock_daily_productions', 'mortality', $scopeIds, fn ($query) => $query->whereBetween('production_date', [$from, $to])),
            'opex' => $this->sum('livestock_opex_entries', 'amount', $scopeIds, fn ($query) => $query->whereBetween('expense_date', [$from, $to])),
            'revenue' => $this->sum('livestock_revenue_entries', 'amount', $scopeIds, fn ($query) => $query->whereBetween('revenue_date', [$from, $to])),
            'capital' => $this->sum('livestock_investments', 'cost', $scopeIds, fn ($query) => $query->where('cost_class', 'capital')),
            'working_capital' => $this->sum('livestock_investments', 'cost', $scopeIds, fn ($query) => $query->where('cost_class', 'working_capital')),
        ];
        $metrics['operating_return'] = $metrics['revenue'] - $metrics['opex'];

        $farmRows = $this->farmRows($scopeIds);
        $recentProduction = $this->recentProduction($scopeIds);
        $recentTransactions = $this->recentTransactions($scopeIds);

        return view('SuperAdmin.livestock.overview', compact(
            'companies', 'selectedCompanyId', 'metrics', 'farmRows', 'recentProduction', 'recentTransactions', 'from', 'to'
        ));
    }

    private function query(string $table, array $companyIds)
    {
        if (! Schema::hasTable($table) || $companyIds === []) {
            return null;
        }

        return DB::table($table)->whereIn('company_id', $companyIds);
    }

    private function count(string $table, array $companyIds, ?callable $filter = null): int
    {
        $query = $this->query($table, $companyIds);
        if (! $query) {
            return 0;
        }

        if ($filter) {
            $filter($query);
        }

        return $query->count();
    }

    private function sum(string $table, string $column, array $companyIds, ?callable $filter = null): float
    {
        $query = $this->query($table, $companyIds);
        if (! $query || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        if ($filter) {
            $filter($query);
        }

        return (float) $query->sum($column);
    }

    private function farmRows(array $companyIds)
    {
        if (! Schema::hasTable('livestock_farms') || $companyIds === []) {
            return collect();
        }

        return DB::table('livestock_farms as farms')
            ->leftJoin('companies', 'companies.id', '=', 'farms.company_id')
            ->leftJoin('livestock_flocks as flocks', function ($join) {
                $join->on('flocks.farm_id', '=', 'farms.id')->where('flocks.status', '=', 'active');
            })
            ->whereIn('farms.company_id', $companyIds)
            ->groupBy('farms.id', 'farms.company_id', 'farms.name', 'farms.code', 'farms.location', 'farms.bird_capacity', 'farms.is_active', 'companies.name')
            ->orderBy('companies.name')
            ->orderBy('farms.name')
            ->select([
                'farms.id', 'farms.company_id', 'farms.name', 'farms.code', 'farms.location',
                'farms.bird_capacity', 'farms.is_active', 'companies.name as company_name',
            ])
            ->selectRaw('COUNT(flocks.id) as active_flocks, COALESCE(SUM(flocks.current_birds), 0) as current_birds')
            ->get();
    }

    private function recentProduction(array $companyIds)
    {
        if (! Schema::hasTable('livestock_daily_productions') || $companyIds === []) {
            return collect();
        }

        return DB::table('livestock_daily_productions as production')
            ->leftJoin('livestock_farms as farms', 'farms.id', '=', 'production.farm_id')
            ->leftJoin('companies', 'companies.id', '=', 'production.company_id')
            ->whereIn('production.company_id', $companyIds)
            ->latest('production.production_date')
            ->limit(12)
            ->get(['production.*', 'farms.name as farm_name', 'companies.name as company_name']);
    }

    private function recentTransactions(array $companyIds)
    {
        if ($companyIds === []) {
            return collect();
        }

        $rows = collect();
        if (Schema::hasTable('livestock_opex_entries')) {
            $rows = $rows->merge(DB::table('livestock_opex_entries as entries')
                ->leftJoin('livestock_farms as farms', 'farms.id', '=', 'entries.farm_id')
                ->whereIn('entries.company_id', $companyIds)
                ->latest('entries.expense_date')->limit(10)
                ->get(['entries.id', 'entries.expense_date as record_date', 'entries.category as category', 'entries.description', 'entries.amount', 'farms.name as farm_name'])
                ->map(fn ($row) => (object) [...(array) $row, 'kind' => 'Expense']));
        }
        if (Schema::hasTable('livestock_revenue_entries')) {
            $rows = $rows->merge(DB::table('livestock_revenue_entries as entries')
                ->leftJoin('livestock_farms as farms', 'farms.id', '=', 'entries.farm_id')
                ->whereIn('entries.company_id', $companyIds)
                ->latest('entries.revenue_date')->limit(10)
                ->get(['entries.id', 'entries.revenue_date as record_date', 'entries.source as category', 'entries.description', 'entries.amount', 'farms.name as farm_name'])
                ->map(fn ($row) => (object) [...(array) $row, 'kind' => 'Revenue']));
        }

        return $rows->sortByDesc('record_date')->take(12)->values();
    }
}
