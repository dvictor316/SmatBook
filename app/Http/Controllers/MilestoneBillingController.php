<?php

namespace App\Http\Controllers;

use App\Models\ProjectMilestone;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MilestoneBillingController extends Controller
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
    public function index(Request $request)
    {
        $companyId  = Auth::user()->company_id;
        $milestones = ProjectMilestone::forCompany($companyId)
            ->with(['project', 'customer', 'invoice'])
            ->latest()
            ->paginate(25);

        return view('milestones.index', compact('milestones'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $projects = Project::where('company_id', $companyId)->orderBy('name')->get();
        return view('milestones.create', compact('customers', 'projects'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'customer_id'     => 'nullable|exists:customers,id',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'due_date'        => 'required|date',
            'billing_amount'  => 'required|numeric|min:0',
            'billing_type'    => 'required|in:fixed,percentage_of_contract,on_completion',
            'percentage'      => 'nullable|numeric|min:0|max:100',
        ]);

        ProjectMilestone::create([
            'company_id'      => $companyId,
            'project_id'      => $data['project_id'],
            'customer_id'     => $data['customer_id'] ?? null,
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'due_date'        => $data['due_date'],
            'billing_amount'  => $data['billing_amount'],
            'billing_type'    => $data['billing_type'],
            'percentage'      => $data['percentage'] ?? null,
            'status'          => 'pending',
        ]);

        return redirect()->route('milestones.index')->with('success', 'Milestone created.');
    }

    public function show(ProjectMilestone $milestone)
    {
        $this->authorizeMilestoneAccess($milestone);
        $milestone->load(['project', 'customer', 'invoice']);
        return view('milestones.show', compact('milestone'));
    }

    public function complete(ProjectMilestone $milestone)
    {
        $this->authorizeMilestoneAccess($milestone);
        abort_unless($milestone->status === 'in_progress' || $milestone->status === 'pending', 422,
            'Milestone cannot be completed.');

        $milestone->update([
            'status'       => 'completed',
            'completion_date' => now(),
        ]);

        return back()->with('success', 'Milestone marked as completed. Ready to invoice.');
    }

    public function createInvoice(ProjectMilestone $milestone)
    {
        $this->authorizeMilestoneAccess($milestone);
        abort_unless($milestone->status === 'completed', 422,
            'Only completed milestones can be invoiced.');
        abort_if($milestone->invoice_id, 422, 'Invoice already exists for this milestone.');

        // Redirect to invoice creation pre-filled with milestone data
        return redirect()->route('add-invoice', [
            'customer_id' => $milestone->customer_id,
            'description' => $milestone->name,
            'amount' => $milestone->billing_amount,
            'milestone_id' => $milestone->id,
        ]);
    }

    public function destroy(ProjectMilestone $milestone)
    {
        $this->authorizeMilestoneAccess($milestone);
        abort_if($milestone->invoice_id, 422, 'Cannot delete a milstone that has been invoiced.');
        $milestone->delete();
        return redirect()->route('milestones.index')->with('success', 'Milestone deleted.');
    }

    private function authorizeMilestoneAccess(ProjectMilestone $milestone): void
    {
        abort_unless($milestone->company_id === Auth::user()->company_id, 403);
    }
}
