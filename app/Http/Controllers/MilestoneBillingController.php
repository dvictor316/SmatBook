<?php

namespace App\Http\Controllers;

use App\Models\ProjectMilestone;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ->with(['customer', 'invoice'])
            ->latest()
            ->paginate(25);

        return view('milestones.index', compact('milestones'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        return view('milestones.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'project_name'    => 'required|string|max:255',
            'customer_id'     => 'nullable|exists:customers,id',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'due_date'        => 'required|date',
            'amount'          => 'required|numeric|min:0',
            'currency'        => 'nullable|string|size:3',
            'billable'        => 'boolean',
            'deliverables'    => 'nullable|string',
        ]);

        $branch = $this->getActiveBranchContext();
        $data['company_id'] = $companyId;
        $data['branch_id']  = $branch['id'];
        $data['branch_name'] = $branch['name'];
        $data['status']     = 'pending';
        $data['billable']   = $request->boolean('billable', true);
        $data['created_by'] = Auth::id();

        ProjectMilestone::create($data);

        return redirect()->route('milestones.index')->with('success', 'Milestone created.');
    }

    public function show(ProjectMilestone $milestone)
    {
        $this->authorize($milestone);
        $milestone->load(['customer', 'invoice']);
        return view('milestones.show', compact('milestone'));
    }

    public function complete(ProjectMilestone $milestone)
    {
        $this->authorize($milestone);
        abort_unless($milestone->status === 'in_progress' || $milestone->status === 'pending', 422,
            'Milestone cannot be completed.');

        $milestone->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Milestone marked as completed. Ready to invoice.');
    }

    public function createInvoice(ProjectMilestone $milestone)
    {
        $this->authorize($milestone);
        abort_unless($milestone->status === 'completed', 422,
            'Only completed milestones can be invoiced.');
        abort_unless($milestone->billable, 422, 'Milestone is not billable.');
        abort_if($milestone->invoice_id, 422, 'Invoice already exists for this milestone.');

        // Redirect to invoice creation pre-filled with milestone data
        return redirect()->route('invoices.create', [
            'customer_id'  => $milestone->customer_id,
            'description'  => $milestone->project_name . ' — ' . $milestone->title,
            'amount'       => $milestone->amount,
            'milestone_id' => $milestone->id,
        ]);
    }

    public function destroy(ProjectMilestone $milestone)
    {
        $this->authorize($milestone);
        abort_if($milestone->invoice_id, 422, 'Cannot delete a milstone that has been invoiced.');
        $milestone->delete();
        return redirect()->route('milestones.index')->with('success', 'Milestone deleted.');
    }

    private function authorize(ProjectMilestone $milestone): void
    {
        abort_unless($milestone->company_id === Auth::user()->company_id, 403);
    }
}
