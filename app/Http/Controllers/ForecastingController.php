<?php

namespace App\Http\Controllers;

use App\Models\Forecast;
use App\Models\ForecastItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForecastingController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', Auth::user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $forecasts = Forecast::forCompany($companyId)
            ->with('items')
            ->latest('period_start')
            ->paginate(20);
        return view('forecasting.index', compact('forecasts'));
    }

    public function create()
    {
        return view('forecasting.create');
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:revenue,expense,cash_flow,sales',
            'scenario'         => 'required|in:base,optimistic,pessimistic,custom',
            'frequency'        => 'required|in:weekly,monthly,quarterly,annually',
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after:period_start',
            'assumptions'      => 'nullable|string',
            'items'            => 'nullable|array',
            'items.*.category' => 'required|string|max:255',
            'items.*.period_date' => 'required|date',
            'items.*.forecast_amount' => 'required|numeric|min:0',
            'items.*.notes'    => 'nullable|string',
        ]);

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $branch) {
            $totalForecastAmount = collect($data['items'] ?? [])->sum('forecast_amount');

            $forecast = Forecast::create([
                'company_id'   => $companyId,
                'branch_id'    => $branch['id'],
                'name'         => $data['name'],
                'type'         => $data['type'],
                'scenario'     => $data['scenario'],
                'frequency'    => $data['frequency'],
                'period_start' => $data['period_start'],
                'period_end'   => $data['period_end'],
                'assumptions'  => $data['assumptions'] ?? null,
                'status'       => 'draft',
                'total_forecast_amount' => $totalForecastAmount,
                'created_by'   => Auth::id(),
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $forecast->items()->create([
                    'category'           => $item['category'],
                    'period_date'        => $item['period_date'],
                    'forecast_amount'    => $item['forecast_amount'],
                    'actual_amount'      => 0,
                    'notes'              => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('forecasting.index')->with('success', 'Forecast created.');
    }

    public function show(Forecast $forecast)
    {
        $this->authorizeForecastAccess($forecast);
        $forecast->load('items');
        return view('forecasting.show', compact('forecast'));
    }

    public function updateActuals(Request $request, Forecast $forecast)
    {
        $this->authorizeForecastAccess($forecast);

        $data = $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:forecast_items,id',
            'items.*.actual_amount' => 'required|numeric|min:0',
        ]);

        foreach ($data['items'] as $item) {
            ForecastItem::where('id', $item['id'])
                ->where('forecast_id', $forecast->id)
                ->update(['actual_amount' => $item['actual_amount']]);
        }

        return back()->with('success', 'Actuals updated.');
    }

    public function destroy(Forecast $forecast)
    {
        $this->authorizeForecastAccess($forecast);
        $forecast->delete();
        return redirect()->route('forecasting.index')->with('success', 'Forecast deleted.');
    }

    private function authorizeForecastAccess(Forecast $forecast): void
    {
        abort_unless($forecast->company_id === Auth::user()->company_id, 403);
    }
}
