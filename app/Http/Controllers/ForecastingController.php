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
        $forecasts = Forecast::forCompany($companyId)->latest('period_start')->paginate(20);
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
            'period_type'      => 'required|in:monthly,quarterly,annual',
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after:period_start',
            'currency'         => 'required|string|size:3',
            'assumptions'      => 'nullable|string',
            'items'            => 'nullable|array',
            'items.*.category' => 'required|string|max:255',
            'items.*.period_label' => 'required|string|max:100',
            'items.*.forecasted_amount' => 'required|numeric|min:0',
            'items.*.notes'    => 'nullable|string',
        ]);

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $branch) {
            $forecast = Forecast::create([
                'company_id'   => $companyId,
                'branch_id'    => $branch['id'],
                'branch_name'  => $branch['name'],
                'name'         => $data['name'],
                'type'         => $data['type'],
                'period_type'  => $data['period_type'],
                'period_start' => $data['period_start'],
                'period_end'   => $data['period_end'],
                'currency'     => $data['currency'],
                'assumptions'  => $data['assumptions'] ?? null,
                'status'       => 'draft',
                'created_by'   => Auth::id(),
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $forecast->items()->create([
                    'category'           => $item['category'],
                    'period_label'       => $item['period_label'],
                    'forecasted_amount'  => $item['forecasted_amount'],
                    'actual_amount'      => 0,
                    'notes'              => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('forecasting.index')->with('success', 'Forecast created.');
    }

    public function show(Forecast $forecast)
    {
        $this->authorize($forecast);
        $forecast->load('items');
        return view('forecasting.show', compact('forecast'));
    }

    public function updateActuals(Request $request, Forecast $forecast)
    {
        $this->authorize($forecast);

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
        $this->authorize($forecast);
        $forecast->delete();
        return redirect()->route('forecasting.index')->with('success', 'Forecast deleted.');
    }

    private function authorize(Forecast $forecast): void
    {
        abort_unless($forecast->company_id === Auth::user()->company_id, 403);
    }
}
