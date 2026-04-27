<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LandedCost;

class LandedCostController extends Controller
{
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
        $items = LandedCost::where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        return view('landed-costs.index', compact('items'));
    }

    public function create()
    {
        return view('landed-costs.create');
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
        LandedCost::create($validated + [
            'company_id' => $user->company_id,
            'branch_id'  => $branch['id'],
            'branch_name'=> $branch['name'],
            'created_by' => $user->id,
            'status'     => 'pending',
        ]);

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
