<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Livestock\LivestockDashboardController as TenantLivestockController;
use App\Models\Company;
use App\Support\LivestockAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
        $selectedCompany = $selectedCompanyId ? $companies->firstWhere('id', $selectedCompanyId) : null;
        $management = $this->managementData($selectedCompanyId);

        return view('SuperAdmin.livestock.overview', compact(
            'companies', 'selectedCompany', 'selectedCompanyId', 'metrics', 'farmRows', 'recentProduction',
            'recentTransactions', 'management', 'from', 'to'
        ));
    }

    public function storeFarm(Request $request)
    {
        $companyId = $this->validatedCompanyId($request);
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => ['required', 'string', 'max:40', Rule::unique('livestock_farms', 'code')->where(fn ($query) => $query->where('company_id', $companyId))],
            'bird_capacity' => 'required|integer|min:1',
            'eggs_per_crate' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:255',
        ]);

        DB::table('livestock_farms')->insert([
            ...$data, 'company_id' => $companyId, 'farm_type' => 'layers', 'is_active' => true,
            'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->backToCompany($companyId, 'Layer farm created.');
    }

    public function updateFarm(Request $request, int $farm)
    {
        $row = $this->managedFarm($farm);
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => ['required', 'string', 'max:40', Rule::unique('livestock_farms', 'code')->where(fn ($query) => $query->where('company_id', $row->company_id))->ignore($row->id)],
            'bird_capacity' => 'required|integer|min:1', 'eggs_per_crate' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:255', 'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_at'] = now();
        DB::table('livestock_farms')->where('id', $row->id)->where('company_id', $row->company_id)->update($data);

        return $this->backToCompany((int) $row->company_id, 'Farm details updated.');
    }

    public function storeFlock(Request $request)
    {
        $farm = $this->managedFarm($request->integer('farm_id'));
        $data = $request->validate([
            'batch_code' => ['required', 'string', 'max:60', Rule::unique('livestock_flocks', 'batch_code')->where(fn ($query) => $query->where('company_id', $farm->company_id))],
            'breed' => 'nullable|string|max:100', 'placement_date' => 'required|date',
            'age_at_placement_weeks' => 'required|integer|min:0|max:200', 'opening_birds' => 'required|integer|min:1',
            'cost_per_bird' => 'required|numeric|min:0', 'supplier' => 'nullable|string|max:191', 'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data, $farm) {
            $flockId = DB::table('livestock_flocks')->insertGetId([
                ...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id,
                'current_birds' => $data['opening_birds'], 'status' => 'active', 'created_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('livestock_investments')->insert([
                'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'flock_id' => $flockId,
                'cost_class' => 'working_capital', 'category' => 'point_of_cage_birds',
                'description' => 'Point-of-cage birds - '.$data['batch_code'], 'cost_date' => $data['placement_date'],
                'cost' => round($data['opening_birds'] * $data['cost_per_bird'], 2), 'salvage_value' => 0,
                'useful_life_months' => 18, 'allocation_method' => 'straight_line', 'accumulated_allocation' => 0,
                'status' => 'active', 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return $this->backToCompany((int) $farm->company_id, 'Flock opened and bird acquisition recorded.');
    }

    public function updateFlockStatus(Request $request, int $flock)
    {
        $row = $this->managedRow('livestock_flocks', $flock);
        $data = $request->validate(['status' => 'required|in:active,closed']);
        DB::table('livestock_flocks')->where('id', $row->id)->update([
            'status' => $data['status'], 'closed_on' => $data['status'] === 'closed' ? now()->toDateString() : null, 'updated_at' => now(),
        ]);

        return $this->backToCompany((int) $row->company_id, 'Flock status updated.');
    }

    public function storeInvestment(Request $request)
    {
        $farm = $this->managedFarm($request->integer('farm_id'));
        $class = $request->validate(['cost_class' => 'required|in:capital,working_capital'])['cost_class'];
        $categories = $class === 'capital' ? TenantLivestockController::CAPITAL_CATEGORIES : TenantLivestockController::WORKING_CATEGORIES;
        $data = $request->validate([
            'flock_id' => ['nullable', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($query) => $query->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            'category' => ['required', Rule::in($categories)], 'description' => 'required|string|max:191',
            'cost_date' => 'required|date', 'cost' => 'required|numeric|min:0.01', 'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1|max:1200', 'notes' => 'nullable|string|max:1000',
        ]);
        DB::table('livestock_investments')->insert([
            ...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'cost_class' => $class,
            'salvage_value' => $data['salvage_value'] ?? 0, 'allocation_method' => 'straight_line',
            'accumulated_allocation' => 0, 'status' => 'active', 'created_by' => auth()->id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->backToCompany((int) $farm->company_id, 'Investment record created.');
    }

    public function storeOpex(Request $request)
    {
        return $this->storeTransaction($request, false);
    }

    public function storeRevenue(Request $request)
    {
        return $this->storeTransaction($request, true);
    }

    public function storeProduction(Request $request)
    {
        $farm = $this->managedFarm($request->integer('farm_id'));
        $data = $request->validate([
            'flock_id' => ['required', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($query) => $query->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            'production_date' => ['required', 'date', Rule::unique('livestock_daily_productions')->where(fn ($query) => $query->where('farm_id', $farm->id)->where('flock_id', $request->integer('flock_id')))],
            'opening_birds' => 'required|integer|min:0', 'mortality' => 'nullable|integer|min:0', 'culled' => 'nullable|integer|min:0',
            'egg_crates' => 'nullable|integer|min:0', 'loose_eggs' => 'nullable|integer|min:0', 'damaged_eggs' => 'nullable|integer|min:0',
            'feed_kg' => 'nullable|numeric|min:0', 'water_litres' => 'nullable|numeric|min:0',
            'medication' => 'nullable|string|max:255', 'vaccination' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:1000',
        ]);
        $mortality = (int) ($data['mortality'] ?? 0);
        $culled = (int) ($data['culled'] ?? 0);
        abort_if($mortality + $culled > (int) $data['opening_birds'], 422, 'Mortality and culls cannot exceed opening birds.');
        $closing = (int) $data['opening_birds'] - $mortality - $culled;
        $goodEggs = ((int) ($data['egg_crates'] ?? 0) * (int) $farm->eggs_per_crate) + (int) ($data['loose_eggs'] ?? 0);
        $henDay = $data['opening_birds'] > 0 ? round(($goodEggs / $data['opening_birds']) * 100, 2) : 0;

        DB::transaction(function () use ($data, $farm, $closing, $goodEggs, $henDay) {
            $productionId = DB::table('livestock_daily_productions')->insertGetId([
                ...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'closing_birds' => $closing,
                'total_good_eggs' => $goodEggs, 'hen_day_percent' => $henDay, 'recorded_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ((float) ($data['feed_kg'] ?? 0) > 0) {
                $this->sourceMovement($farm, $data['flock_id'], $data['production_date'], 'feed', 'consumption', -(float) $data['feed_kg'], 'kg', 'production', $productionId);
            }
            if ($goodEggs > 0) {
                $this->sourceMovement($farm, $data['flock_id'], $data['production_date'], 'eggs', 'production', $goodEggs, 'egg', 'production', $productionId);
            }
            DB::table('livestock_flocks')->where('id', $data['flock_id'])->where('company_id', $farm->company_id)->update(['current_birds' => $closing, 'updated_at' => now()]);
        });

        return $this->backToCompany((int) $farm->company_id, 'Daily production recorded and inventory updated.');
    }

    public function storeInventory(Request $request)
    {
        $farm = $this->managedFarm($request->integer('farm_id'));
        $data = $request->validate([
            'flock_id' => ['nullable', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($query) => $query->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            'movement_date' => 'required|date', 'item_type' => 'required|in:feed,eggs',
            'movement_type' => 'required|in:opening,receipt,adjustment_in,adjustment_out,wastage',
            'quantity' => 'required|numeric|min:0.001', 'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000',
        ]);
        $quantity = (float) $data['quantity'] * (in_array($data['movement_type'], ['adjustment_out', 'wastage'], true) ? -1 : 1);
        DB::table('livestock_inventory_movements')->insert([
            ...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'quantity' => $quantity,
            'unit' => $data['item_type'] === 'feed' ? 'kg' : 'egg', 'unit_cost' => $data['unit_cost'] ?? 0,
            'total_value' => round(abs($quantity) * (float) ($data['unit_cost'] ?? 0), 2),
            'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->backToCompany((int) $farm->company_id, 'Inventory movement recorded.');
    }

    public function destroyRecord(string $type, int $id)
    {
        $map = [
            'investment' => 'livestock_investments', 'opex' => 'livestock_opex_entries',
            'revenue' => 'livestock_revenue_entries', 'production' => 'livestock_daily_productions',
            'inventory' => 'livestock_inventory_movements',
        ];
        abort_unless(isset($map[$type]), 404);
        $table = $map[$type];
        $row = $this->managedRow($table, $id);
        if (in_array($type, ['opex', 'revenue'], true) && ! empty($row->posted_at)) {
            abort(422, 'Posted accounting records cannot be deleted.');
        }
        if ($type === 'inventory' && ! empty($row->source_id)) {
            abort(422, 'Automatic inventory movements must be removed with their source record.');
        }

        DB::transaction(function () use ($table, $type, $row) {
            if (in_array($type, ['revenue', 'production'], true)) {
                $sourceClass = $type === 'revenue' ? \App\Models\LivestockRevenueEntry::class : \App\Models\LivestockDailyProduction::class;
                DB::table('livestock_inventory_movements')->where('company_id', $row->company_id)->where('source_type', $sourceClass)->where('source_id', $row->id)->delete();
            }
            DB::table($table)->where('id', $row->id)->where('company_id', $row->company_id)->delete();
            if ($type === 'production') {
                $closing = DB::table('livestock_daily_productions')->where('company_id', $row->company_id)->where('flock_id', $row->flock_id)->latest('production_date')->value('closing_birds');
                $opening = DB::table('livestock_flocks')->where('id', $row->flock_id)->value('opening_birds');
                DB::table('livestock_flocks')->where('id', $row->flock_id)->update(['current_birds' => $closing ?? $opening, 'updated_at' => now()]);
            }
        });

        return $this->backToCompany((int) $row->company_id, ucfirst($type).' record removed.');
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

    private function managementData(?int $companyId): array
    {
        $empty = ['farms' => collect(), 'flocks' => collect(), 'investments' => collect(), 'opex' => collect(), 'revenue' => collect(), 'production' => collect(), 'inventory' => collect()];
        if (! $companyId) {
            return $empty;
        }

        $farms = DB::table('livestock_farms')->where('company_id', $companyId)->orderBy('name')->get();
        $flocks = DB::table('livestock_flocks as flocks')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'flocks.farm_id')
            ->where('flocks.company_id', $companyId)->latest('flocks.id')->get(['flocks.*', 'farms.name as farm_name']);

        return [
            'farms' => $farms,
            'flocks' => $flocks,
            'investments' => DB::table('livestock_investments as records')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'records.farm_id')->where('records.company_id', $companyId)->latest('records.cost_date')->limit(50)->get(['records.*', 'farms.name as farm_name']),
            'opex' => DB::table('livestock_opex_entries as records')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'records.farm_id')->where('records.company_id', $companyId)->latest('records.expense_date')->limit(50)->get(['records.*', 'farms.name as farm_name']),
            'revenue' => DB::table('livestock_revenue_entries as records')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'records.farm_id')->where('records.company_id', $companyId)->latest('records.revenue_date')->limit(50)->get(['records.*', 'farms.name as farm_name']),
            'production' => DB::table('livestock_daily_productions as records')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'records.farm_id')->where('records.company_id', $companyId)->latest('records.production_date')->limit(50)->get(['records.*', 'farms.name as farm_name']),
            'inventory' => DB::table('livestock_inventory_movements as records')->leftJoin('livestock_farms as farms', 'farms.id', '=', 'records.farm_id')->where('records.company_id', $companyId)->latest('records.movement_date')->latest('records.id')->limit(75)->get(['records.*', 'farms.name as farm_name']),
        ];
    }

    private function storeTransaction(Request $request, bool $revenue)
    {
        $farm = $this->managedFarm($request->integer('farm_id'));
        $categoryField = $revenue ? 'source' : 'category';
        $dateField = $revenue ? 'revenue_date' : 'expense_date';
        $priceField = $revenue ? 'unit_price' : 'unit_cost';
        $categories = $revenue ? TenantLivestockController::REVENUE_SOURCES : TenantLivestockController::OPEX_CATEGORIES;
        $data = $request->validate([
            'flock_id' => ['nullable', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($query) => $query->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            $dateField => 'required|date', $categoryField => ['required', Rule::in($categories)],
            'description' => 'nullable|string|max:255', 'quantity' => 'required|numeric|min:0.001',
            'unit' => 'nullable|string|max:30', $priceField => 'required|numeric|min:0', 'amount' => 'nullable|numeric|min:0.01',
            ...($revenue ? ['customer' => 'nullable|string|max:191'] : ['frequency' => 'required|in:daily,weekly,monthly,occasional', 'vendor' => 'nullable|string|max:191']),
            'reference' => 'nullable|string|max:100',
        ]);
        $amount = round((float) ($data['amount'] ?? 0), 2) ?: round((float) $data['quantity'] * (float) $data[$priceField], 2);
        $table = $revenue ? 'livestock_revenue_entries' : 'livestock_opex_entries';

        DB::transaction(function () use ($data, $farm, $revenue, $table, $amount, $dateField) {
            $id = DB::table($table)->insertGetId([
                ...$data, 'amount' => $amount, 'company_id' => $farm->company_id, 'farm_id' => $farm->id,
                'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($revenue && $data['source'] === 'eggs') {
                $multiplier = in_array(strtolower(trim((string) ($data['unit'] ?? ''))), ['crate', 'crates', 'tray', 'trays'], true) ? (int) $farm->eggs_per_crate : 1;
                $this->sourceMovement($farm, $data['flock_id'] ?? null, $data[$dateField], 'eggs', 'sale', -((float) $data['quantity'] * $multiplier), 'egg', 'revenue', $id);
            }
        });

        return $this->backToCompany((int) $farm->company_id, $revenue ? 'Farm revenue recorded.' : 'Farm operating expense recorded.');
    }

    private function sourceMovement(object $farm, ?int $flockId, string $date, string $itemType, string $movementType, float $quantity, string $unit, string $source, int $sourceId): void
    {
        DB::table('livestock_inventory_movements')->insert([
            'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'flock_id' => $flockId,
            'movement_date' => $date, 'item_type' => $itemType, 'movement_type' => $movementType,
            'quantity' => $quantity, 'unit' => $unit, 'unit_cost' => 0, 'total_value' => 0,
            'source_type' => $source === 'revenue' ? \App\Models\LivestockRevenueEntry::class : \App\Models\LivestockDailyProduction::class,
            'source_id' => $sourceId, 'notes' => 'Super admin managed '.$movementType,
            'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function validatedCompanyId(Request $request): int
    {
        $companyId = $request->validate(['company_id' => ['required', 'integer', Rule::in(LivestockAccess::livestockCompanyIds())]])['company_id'];

        return (int) $companyId;
    }

    private function managedFarm(int $id): object
    {
        return $this->managedRow('livestock_farms', $id);
    }

    private function managedRow(string $table, int $id): object
    {
        abort_unless(Schema::hasTable($table), 404);
        $row = DB::table($table)->where('id', $id)->first();
        abort_unless($row && in_array((int) $row->company_id, LivestockAccess::livestockCompanyIds(), true), 404);

        return $row;
    }

    private function backToCompany(int $companyId, string $message)
    {
        return redirect()->route('super_admin.livestock.index', ['company_id' => $companyId])->with('success', $message);
    }
}
