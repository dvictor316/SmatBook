<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\CostCenter;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    private function departmentOptionsForCompany(int $companyId, ?int $branchId, ?int $excludeDepartmentId = null)
    {
        $query = Department::forCompany($companyId)
            ->when(
                $branchId !== null && Schema::hasColumn('departments', 'branch_id'),
                fn ($builder) => $builder->where('branch_id', $branchId)
            )
            ->active()
            ->when($excludeDepartmentId !== null, fn ($builder) => $builder->where('id', '!=', $excludeDepartmentId))
            ->orderBy('name');

        $departments = $query->get();

        if ($departments->isEmpty()) {
            $departments = Department::forCompany($companyId)
                ->active()
                ->when($excludeDepartmentId !== null, fn ($builder) => $builder->where('id', '!=', $excludeDepartmentId))
                ->orderBy('name')
                ->get();
        }

        return $departments;
    }

    private function employeeOptionsForCompany(int $companyId, ?int $branchId)
    {
        $query = Employee::withoutGlobalScopes()
            ->forWorkspaceCompany($companyId)
            ->when(
                $branchId !== null && Schema::hasColumn('employees', 'branch_id'),
                fn ($builder) => $builder->where('employees.branch_id', $branchId)
            )
            ->when(
                Schema::hasColumn('employees', 'status'),
                fn ($builder) => $builder->where(function ($sub) {
                    $sub->whereNull('employees.status')
                        ->orWhere('employees.status', 'active');
                })
            )
            ->orderBy('name');

        $employees = $query->get();

        if ($employees->isEmpty()) {
            $employees = Employee::withoutGlobalScopes()
                ->forWorkspaceCompany($companyId)
                ->when(
                    Schema::hasColumn('employees', 'status'),
                    fn ($builder) => $builder->where(function ($sub) {
                        $sub->whereNull('employees.status')
                            ->orWhere('employees.status', 'active');
                    })
                )
                ->orderBy('name')
                ->get();
        }

        return $employees;
    }

    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', Auth::user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }

    private function resolveDepartmentBranchId(): ?int
    {
        $branchId = $this->getActiveBranchContext()['id'];

        if ($branchId === null || $branchId === '') {
            return null;
        }

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function index()
    {
        $companyId = Auth::user()->company_id;
        $branchId = $this->resolveDepartmentBranchId();
        $departments = Department::forCompany($companyId)
            ->when(
                $branchId !== null && Schema::hasColumn('departments', 'branch_id'),
                fn ($query) => $query->where('branch_id', $branchId)
            )
            ->with(['head', 'parent'])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(25);
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = $this->resolveDepartmentBranchId();
        $employees = $this->employeeOptionsForCompany($companyId, $branchId);
        $departments = $this->departmentOptionsForCompany($companyId, $branchId);

        return view('departments.create', compact('employees', 'departments'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $this->resolveDepartmentBranchId();

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'code'              => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'parent_id'         => 'nullable|exists:departments,id',
            'head_employee_id'  => 'nullable|exists:employees,id',
            'is_active'         => 'boolean',
            'description'       => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;
        if ($branchId !== null && Schema::hasColumn('departments', 'branch_id')) {
            $data['branch_id'] = $branchId;
        }
        $data['is_active']  = $request->boolean('is_active', true);

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $this->authorizeDepartmentAccess($department);
        $companyId   = Auth::user()->company_id;
        $branchId = $this->resolveDepartmentBranchId();
        $employees   = $this->employeeOptionsForCompany($companyId, $branchId);
        $departments = $this->departmentOptionsForCompany($companyId, $branchId, $department->id);

        return view('departments.edit', compact('department', 'employees', 'departments'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeDepartmentAccess($department);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where(fn ($query) => $query->where('company_id', $department->company_id))
                    ->ignore($department->id),
            ],
            'parent_id'        => 'nullable|exists:departments,id',
            'head_employee_id' => 'nullable|exists:employees,id',
            'is_active'        => 'boolean',
            'description'      => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $department->update($data);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $this->authorizeDepartmentAccess($department);
        abort_if($department->employees()->count() > 0, 422,
            'Cannot delete a department with assigned employees.');
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }

    private function authorizeDepartmentAccess(Department $dept): void
    {
        abort_unless($dept->company_id === Auth::user()->company_id, 403);
    }
}
