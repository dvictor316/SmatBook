<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostCenterController extends Controller
{

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
        $branchId = $this->getActiveBranchContext()['id'];
        $costCenters = CostCenter::forCompany($companyId)
            ->where('branch_id', $branchId)
            ->with('department')
            ->orderBy('name')
            ->paginate(25);
        return view('cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = $this->getActiveBranchContext()['id'];
        $departments = Department::forCompany($companyId)
            ->where('branch_id', $branchId)
            ->active()->orderBy('name')->get();
        return view('cost-centers.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $this->getActiveBranchContext()['id'];

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:50|unique:cost_centers,code,NULL,id,company_id,'.$companyId,
            'type'          => 'required|in:operational,project,department,branch,profit_center,investment_center',
            'department_id' => 'nullable|exists:departments,id',
            'is_active'     => 'boolean',
            'description'   => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;
        $data['branch_id']  = $branchId;
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();

        CostCenter::create($data);

        return redirect()->route('cost-centers.index')->with('success', 'Cost center created.');
    }

    public function edit(CostCenter $costCenter)
    {
        $this->authorize($costCenter);
        $departments = Department::forCompany(Auth::user()->company_id)->active()->orderBy('name')->get();
        return view('cost-centers.edit', compact('costCenter', 'departments'));
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        $this->authorize($costCenter);
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:50|unique:cost_centers,code,'.$costCenter->id.',id,company_id,'.$companyId,
            'type'          => 'required|in:operational,project,department,branch,profit_center,investment_center',
            'department_id' => 'nullable|exists:departments,id',
            'is_active'     => 'boolean',
            'description'   => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $costCenter->update($data);

        return redirect()->route('cost-centers.index')->with('success', 'Cost center updated.');
    }

    public function destroy(CostCenter $costCenter)
    {
        $this->authorize($costCenter);
        $costCenter->delete();
        return redirect()->route('cost-centers.index')->with('success', 'Cost center deleted.');
    }

    private function authorize(CostCenter $cc): void
    {
        abort_unless($cc->company_id === Auth::user()->company_id, 403);
    }
}
