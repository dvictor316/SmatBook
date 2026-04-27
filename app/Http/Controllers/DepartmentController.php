<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\CostCenter;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index()
    {
        $companyId   = Auth::user()->company_id;
        $departments = Department::forCompany($companyId)
            ->with(['head', 'parent'])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(25);
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $companyId   = Auth::user()->company_id;
        $employees   = Employee::where('company_id', $companyId)->orderBy('name')->get();
        $departments = Department::forCompany($companyId)->active()->orderBy('name')->get();
        return view('departments.create', compact('employees', 'departments'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'code'              => 'nullable|string|max:50',
            'parent_id'         => 'nullable|exists:departments,id',
            'head_employee_id'  => 'nullable|exists:employees,id',
            'is_active'         => 'boolean',
            'description'       => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;
        $data['branch_id']  = Auth::user()->branch_id;
        $data['is_active']  = $request->boolean('is_active', true);

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $this->authorize($department);
        $companyId   = Auth::user()->company_id;
        $employees   = Employee::where('company_id', $companyId)->orderBy('name')->get();
        $departments = Department::forCompany($companyId)->active()->where('id', '!=', $department->id)->orderBy('name')->get();
        return view('departments.edit', compact('department', 'employees', 'departments'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorize($department);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:50',
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
        $this->authorize($department);
        abort_if($department->employees()->count() > 0, 422,
            'Cannot delete a department with assigned employees.');
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }

    private function authorize(Department $dept): void
    {
        abort_unless($dept->company_id === Auth::user()->company_id, 403);
    }
}
