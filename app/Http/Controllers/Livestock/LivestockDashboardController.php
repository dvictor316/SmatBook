<?php

namespace App\Http\Controllers\Livestock;

use App\Http\Controllers\Controller;
use App\Models\LivestockDailyProduction;
use App\Models\LivestockFarm;
use App\Models\LivestockFlock;
use App\Models\LivestockInvestment;
use App\Models\LivestockOpexEntry;
use App\Models\LivestockRevenueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LivestockDashboardController extends Controller
{
    public const CAPITAL_CATEGORIES = ['land', 'layer_house', 'battery_cages', 'borehole_tanks', 'generator_solar', 'rubber_hose_bowls', 'office_utilities', 'egg_crates'];

    public const WORKING_CATEGORIES = ['point_of_cage_birds', 'feeding_14_25_weeks', 'medication', 'vaccination', 'personnel'];

    public const OPEX_CATEGORIES = ['feeds', 'medication', 'vaccination', 'personnel', 'maintenance', 'miscellaneous'];

    public const REVENUE_SOURCES = ['eggs', 'culled_layers', 'manure_litter', 'used_feed_bags'];

    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $farms = $this->farmQuery()->orderBy('name')->get();
        $farm = $request->filled('farm_id')
            ? $farms->firstWhere('id', (int) $request->query('farm_id'))
            : $farms->first();

        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()))->toDateString();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->toDateString();
        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $flocks = collect();
        $investments = collect();
        $opex = collect();
        $revenue = collect();
        $productions = collect();
        $summary = $this->emptySummary();

        if ($farm) {
            $flocks = LivestockFlock::query()->where('company_id', $companyId)->where('farm_id', $farm->id)->latest('id')->get();
            $investments = LivestockInvestment::query()->with('flock')->where('company_id', $companyId)->where('farm_id', $farm->id)->orderBy('cost_class')->latest('cost_date')->get();
            $opex = LivestockOpexEntry::query()->with('flock')->where('company_id', $companyId)->where('farm_id', $farm->id)->whereBetween('expense_date', [$from, $to])->latest('expense_date')->limit(100)->get();
            $revenue = LivestockRevenueEntry::query()->with('flock')->where('company_id', $companyId)->where('farm_id', $farm->id)->whereBetween('revenue_date', [$from, $to])->latest('revenue_date')->limit(100)->get();
            $productions = LivestockDailyProduction::query()->with('flock')->where('company_id', $companyId)->where('farm_id', $farm->id)->whereBetween('production_date', [$from, $to])->latest('production_date')->limit(100)->get();

            $capital = $investments->where('cost_class', 'capital');
            $working = $investments->where('cost_class', 'working_capital');
            $periodAllocation = (float) $investments->sum(fn ($item) => $item->periodAllocation($from, $to));
            $opexTotal = (float) $opex->sum('amount');
            $revenueTotal = (float) $revenue->sum('amount');
            $goodEggs = (int) $productions->sum('total_good_eggs');
            $summary = [
                'capital_cost' => (float) $capital->sum('cost'),
                'capital_allocation' => (float) $capital->sum(fn ($item) => $item->allocationAsOf($to)),
                'working_capital' => (float) $working->sum('cost'),
                'working_allocation' => (float) $working->sum(fn ($item) => $item->allocationAsOf($to)),
                'period_allocation' => $periodAllocation,
                'opex' => $opexTotal,
                'revenue' => $revenueTotal,
                'net_return' => $revenueTotal - $opexTotal - $periodAllocation,
                'birds' => (int) $flocks->where('status', 'active')->sum('current_birds'),
                'good_eggs' => $goodEggs,
                'crates_equivalent' => $farm->eggs_per_crate > 0 ? round($goodEggs / $farm->eggs_per_crate, 2) : 0,
                'damaged_eggs' => (int) $productions->sum('damaged_eggs'),
                'mortality' => (int) $productions->sum('mortality'),
                'feed_kg' => (float) $productions->sum('feed_kg'),
                'average_hen_day' => round((float) $productions->avg('hen_day_percent'), 2),
                'cost_per_egg' => $goodEggs > 0 ? round(($opexTotal + $periodAllocation) / $goodEggs, 2) : 0,
                'revenue_per_egg' => $goodEggs > 0 ? round($revenueTotal / $goodEggs, 2) : 0,
            ];
        }

        return view('livestock.dashboard', compact('farms', 'farm', 'flocks', 'investments', 'opex', 'revenue', 'productions', 'summary', 'from', 'to'));
    }

    public function storeFarm(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => ['required', 'string', 'max:40', Rule::unique('livestock_farms', 'code')->where(fn ($q) => $q->where('company_id', $companyId))],
            'bird_capacity' => 'required|integer|min:1',
            'eggs_per_crate' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:255',
        ]);
        LivestockFarm::create([...$data, 'company_id' => $companyId, 'branch_id' => session('active_branch_id'), 'branch_name' => session('active_branch_name'), 'farm_type' => 'layers', 'created_by' => auth()->id()]);

        return back()->with('success', 'Layer farm created.');
    }

    public function storeFlock(Request $request)
    {
        $farm = $this->scopedFarm($request->integer('farm_id'));
        $data = $request->validate([
            'batch_code' => ['required', 'string', 'max:60', Rule::unique('livestock_flocks', 'batch_code')->where(fn ($q) => $q->where('company_id', auth()->user()->company_id))],
            'breed' => 'nullable|string|max:100', 'placement_date' => 'required|date', 'age_at_placement_weeks' => 'required|integer|min:0|max:200',
            'opening_birds' => 'required|integer|min:1', 'cost_per_bird' => 'required|numeric|min:0', 'supplier' => 'nullable|string|max:191', 'notes' => 'nullable|string|max:1000',
        ]);
        $flock = LivestockFlock::create([...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'current_birds' => $data['opening_birds'], 'status' => 'active', 'created_by' => auth()->id()]);
        LivestockInvestment::create(['company_id' => $farm->company_id, 'farm_id' => $farm->id, 'flock_id' => $flock->id, 'cost_class' => 'working_capital', 'category' => 'point_of_cage_birds', 'description' => 'Point-of-cage birds - '.$flock->batch_code, 'cost_date' => $data['placement_date'], 'cost' => round($data['opening_birds'] * $data['cost_per_bird'], 2), 'useful_life_months' => 18, 'allocation_method' => 'straight_line', 'created_by' => auth()->id()]);

        return back()->with('success', 'Flock cycle opened and bird acquisition recorded as working capital.');
    }

    public function storeInvestment(Request $request)
    {
        $farm = $this->scopedFarm($request->integer('farm_id'));
        $class = $request->validate(['cost_class' => 'required|in:capital,working_capital'])['cost_class'];
        $categories = $class === 'capital' ? self::CAPITAL_CATEGORIES : self::WORKING_CATEGORIES;
        $data = $request->validate([
            'flock_id' => ['nullable', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($q) => $q->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            'category' => ['required', Rule::in($categories)], 'description' => 'required|string|max:191', 'cost_date' => 'required|date',
            'cost' => 'required|numeric|min:0.01', 'salvage_value' => 'nullable|numeric|min:0', 'useful_life_months' => 'required|integer|min:1|max:1200', 'notes' => 'nullable|string|max:1000',
        ]);
        LivestockInvestment::create([...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'cost_class' => $class, 'allocation_method' => 'straight_line', 'created_by' => auth()->id()]);

        return back()->with('success', $class === 'capital' ? 'Capital expenditure added to the depreciation schedule.' : 'Working capital added to the amortization schedule.');
    }

    public function storeOpex(Request $request)
    {
        $farm = $this->scopedFarm($request->integer('farm_id'));
        $data = $request->validate($this->transactionRules($farm, 'expense_date', 'category', self::OPEX_CATEGORIES, 'unit_cost'));
        $amount = round((float) ($data['amount'] ?? 0), 2) ?: round((float) $data['quantity'] * (float) $data['unit_cost'], 2);
        LivestockOpexEntry::create([...$data, 'amount' => $amount, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'created_by' => auth()->id()]);

        return back()->with('success', 'Farm operating expense recorded.');
    }

    public function storeRevenue(Request $request)
    {
        $farm = $this->scopedFarm($request->integer('farm_id'));
        $data = $request->validate($this->transactionRules($farm, 'revenue_date', 'source', self::REVENUE_SOURCES, 'unit_price', true));
        $amount = round((float) ($data['amount'] ?? 0), 2) ?: round((float) $data['quantity'] * (float) $data['unit_price'], 2);
        LivestockRevenueEntry::create([...$data, 'amount' => $amount, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'created_by' => auth()->id()]);

        return back()->with('success', 'Farm revenue recorded.');
    }

    public function storeProduction(Request $request)
    {
        $farm = $this->scopedFarm($request->integer('farm_id'));
        $data = $request->validate([
            'flock_id' => ['required', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($q) => $q->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            'production_date' => ['required', 'date', Rule::unique('livestock_daily_productions')->where(fn ($q) => $q->where('farm_id', $farm->id)->where('flock_id', $request->integer('flock_id')))],
            'opening_birds' => 'required|integer|min:0', 'mortality' => 'nullable|integer|min:0', 'culled' => 'nullable|integer|min:0',
            'egg_crates' => 'nullable|integer|min:0', 'loose_eggs' => 'nullable|integer|min:0', 'damaged_eggs' => 'nullable|integer|min:0',
            'feed_kg' => 'nullable|numeric|min:0', 'water_litres' => 'nullable|numeric|min:0', 'medication' => 'nullable|string|max:255', 'vaccination' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:1000',
        ]);
        $mortality = (int) ($data['mortality'] ?? 0);
        $culled = (int) ($data['culled'] ?? 0);
        abort_if($mortality + $culled > (int) $data['opening_birds'], 422, 'Mortality and culls cannot exceed opening birds.');
        $closing = (int) $data['opening_birds'] - $mortality - $culled;
        $goodEggs = ((int) ($data['egg_crates'] ?? 0) * (int) $farm->eggs_per_crate) + (int) ($data['loose_eggs'] ?? 0);
        $henDay = $data['opening_birds'] > 0 ? round(($goodEggs / $data['opening_birds']) * 100, 2) : 0;
        DB::transaction(function () use ($data, $farm, $closing, $goodEggs, $henDay) {
            LivestockDailyProduction::create([...$data, 'company_id' => $farm->company_id, 'farm_id' => $farm->id, 'closing_birds' => $closing, 'total_good_eggs' => $goodEggs, 'hen_day_percent' => $henDay, 'recorded_by' => auth()->id()]);
            LivestockFlock::where('company_id', $farm->company_id)->whereKey($data['flock_id'])->update(['current_birds' => $closing]);
        });

        return back()->with('success', 'Daily production recorded and flock balance updated.');
    }

    public function destroyInvestment(LivestockInvestment $investment)
    {
        $this->assertCompany($investment);
        $investment->delete();

        return back()->with('success', 'Investment record removed.');
    }

    public function destroyOpex(LivestockOpexEntry $entry)
    {
        $this->assertCompany($entry);
        $entry->delete();

        return back()->with('success', 'OPEX record removed.');
    }

    public function destroyRevenue(LivestockRevenueEntry $entry)
    {
        $this->assertCompany($entry);
        $entry->delete();

        return back()->with('success', 'Revenue record removed.');
    }

    public function destroyProduction(LivestockDailyProduction $production)
    {
        $this->assertCompany($production);
        $flockId = (int) $production->flock_id;
        DB::transaction(function () use ($production, $flockId) {
            $production->delete();
            $flock = LivestockFlock::where('company_id', auth()->user()->company_id)->find($flockId);
            if ($flock) {
                $lastClosing = LivestockDailyProduction::where('company_id', $flock->company_id)->where('flock_id', $flockId)->latest('production_date')->value('closing_birds');
                $flock->update(['current_birds' => $lastClosing === null ? $flock->opening_birds : $lastClosing]);
            }
        });

        return back()->with('success', 'Production record removed and flock balance recalculated.');
    }

    private function farmQuery()
    {
        return LivestockFarm::query()
            ->where('company_id', auth()->user()->company_id)
            ->when(session('active_branch_id'), fn ($query) => $query->where(function ($branch) {
                $branch->where('branch_id', session('active_branch_id'))->orWhereNull('branch_id');
            }))
            ->where('is_active', true);
    }

    private function scopedFarm(int $id): LivestockFarm
    {
        return $this->farmQuery()->findOrFail($id);
    }

    private function assertCompany($model): void
    {
        abort_unless((int) $model->company_id === (int) auth()->user()->company_id, 404);
    }

    private function emptySummary(): array
    {
        return array_fill_keys(['capital_cost', 'capital_allocation', 'working_capital', 'working_allocation', 'period_allocation', 'opex', 'revenue', 'net_return', 'birds', 'good_eggs', 'crates_equivalent', 'damaged_eggs', 'mortality', 'feed_kg', 'average_hen_day', 'cost_per_egg', 'revenue_per_egg'], 0);
    }

    private function transactionRules(LivestockFarm $farm, string $dateField, string $categoryField, array $categories, string $priceField, bool $revenue = false): array
    {
        return [
            'flock_id' => ['nullable', 'integer', Rule::exists('livestock_flocks', 'id')->where(fn ($q) => $q->where('company_id', $farm->company_id)->where('farm_id', $farm->id))],
            $dateField => 'required|date', $categoryField => ['required', Rule::in($categories)], 'description' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.001', 'unit' => 'nullable|string|max:30', $priceField => 'required|numeric|min:0', 'amount' => 'nullable|numeric|min:0.01',
            ...($revenue ? ['customer' => 'nullable|string|max:191'] : ['frequency' => 'required|in:daily,weekly,monthly,occasional', 'vendor' => 'nullable|string|max:191']),
            'reference' => 'nullable|string|max:100',
        ];
    }
}
