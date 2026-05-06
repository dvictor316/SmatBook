<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\LandedCost;

class LandedCostController extends Controller
{
    private function branchColumnUsesNumericIds(string $table, string $column = 'branch_id'): bool
    {
        if (!Schema::hasColumn($table, $column)) {
            return false;
        }

        $databaseName = DB::getDatabaseName();
        if (!$databaseName) {
            return false;
        }

        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('data_type');

        if ($columnType === null) {
            return false;
        }

        $columnType = strtolower((string) $columnType);

        return in_array($columnType, ['integer', 'bigint', 'smallint', 'mediumint', 'tinyint'], true);
    }

    private function persistableLandedCostBranchId($branchId)
    {
        $branchId = trim((string) $branchId);
        if ($branchId === '') {
            return null;
        }

        if ($this->branchColumnUsesNumericIds('landed_costs')) {
            return is_numeric($branchId) ? (int) $branchId : null;
        }

        return $branchId;
    }

    private function persistableGoodsReceivedBranchId($branchId)
    {
        $branchId = trim((string) $branchId);
        if ($branchId === '') {
            return null;
        }

        if ($this->branchColumnUsesNumericIds('goods_received_notes')) {
            return is_numeric($branchId) ? (int) $branchId : null;
        }

        return $branchId;
    }

    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', auth()->user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $landedCosts = LandedCost::with('grn')
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        return view('landed-costs.index', compact('landedCosts'));
    }

    public function create()
    {
        $grns = collect();
        $user = auth()->user();
        $branch = $this->getActiveBranchContext();

        if (Schema::hasTable('goods_received_notes')) {
            $grnsQuery = DB::table('goods_received_notes')
                ->where('company_id', $user->company_id)
                ->whereNull('deleted_at')
                ->orderByDesc('received_date')
                ->orderByDesc('id');

            $grnBranchId = $this->persistableGoodsReceivedBranchId($branch['id'] ?? null);
            if (Schema::hasColumn('goods_received_notes', 'branch_id') && $grnBranchId !== null) {
                $grnsQuery->where('branch_id', $grnBranchId);
            }

            $grns = $grnsQuery
                ->limit(100)
                ->get(['id', 'grn_number', 'received_date']);
        }

        return view('landed-costs.create', compact('grns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grn_id'          => 'nullable|exists:goods_received_notes,id',
            'cost_type'       => 'required|string|max:100',
            'description'     => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0.01',
            'currency'        => 'nullable|string|max:10',
            'allocation_method' => 'required|in:by_value,by_weight,by_quantity,equal',
            'notes'           => 'nullable|string',
        ]);

        $user = auth()->user();
        $branch = $this->getActiveBranchContext();
        $payload = $validated + [
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'status'     => 'pending',
        ];

        $landedCostBranchId = $this->persistableLandedCostBranchId($branch['id'] ?? null);
        if (Schema::hasColumn('landed_costs', 'branch_id') && $landedCostBranchId !== null) {
            $payload['branch_id'] = $landedCostBranchId;
        }

        if (Schema::hasColumn('landed_costs', 'branch_name') && !empty($branch['name'])) {
            $payload['branch_name'] = $branch['name'];
        }

        LandedCost::create($payload);

        return redirect()->route('landed-costs.index')
            ->with('success', 'Landed cost recorded.');
    }

    public function show(LandedCost $landedCost)
    {
        $this->authorizeCompany($landedCost);
        return view('landed-costs.show', compact('landedCost'));
    }

    public function edit(LandedCost $landedCost)
    {
        $this->authorizeCompany($landedCost);
        abort_if($landedCost->status === 'allocated', 403, 'Cannot edit an already-allocated landed cost.');
        return view('landed-costs.edit', compact('landedCost'));
    }

    public function update(Request $request, LandedCost $landedCost)
    {
        $this->authorizeCompany($landedCost);
        abort_if($landedCost->status === 'allocated', 403, 'Cannot edit an already-allocated landed cost.');

        $validated = $request->validate([
            'cost_type'         => 'required|string|max:100',
            'description'       => 'nullable|string|max:255',
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'nullable|string|max:10',
            'allocation_method' => 'required|in:by_value,by_weight,by_quantity,equal',
            'notes'             => 'nullable|string',
        ]);

        $landedCost->update($validated);

        return redirect()->route('landed-costs.show', $landedCost)
            ->with('success', 'Landed cost updated.');
    }

    public function allocate(LandedCost $landedCost)
    {
        $this->authorizeCompany($landedCost);
        abort_if($landedCost->status === 'allocated', 422, 'Already allocated.');
        $landedCost->update(['status' => 'allocated', 'allocated_at' => now()]);

        return back()->with('success', 'Landed cost allocated.');
    }

    public function destroy(LandedCost $landedCost)
    {
        $this->authorizeCompany($landedCost);
        abort_if($landedCost->status === 'allocated', 403, 'Cannot delete an allocated landed cost.');
        $landedCost->delete();

        return redirect()->route('landed-costs.index')
            ->with('success', 'Landed cost deleted.');
    }

    private function authorizeCompany(LandedCost $landedCost): void
    {
        abort_if($landedCost->company_id !== auth()->user()->company_id, 403);
    }
}
