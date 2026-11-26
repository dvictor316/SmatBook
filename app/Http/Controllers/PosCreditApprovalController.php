<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosCreditApprovalController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->authorizedCompany($request);

        $approvals = DB::table('pos_credit_approvals as approval')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'approval.requested_by')
            ->where('approval.company_id', $companyId)
            ->select('approval.*', 'cashier.name as cashier_name')
            ->orderByRaw("CASE WHEN approval.status = 'pending' THEN 0 ELSE 1 END")
            ->latest('approval.id')->paginate(25);

        return view('pos.credit-approvals', compact('approvals'));
    }

    public function review(Request $request, int $id)
    {
        $companyId = $this->authorizedCompany($request);
        $decision = $request->validate(['decision' => 'required|in:approved,rejected'])['decision'];

        $updated = DB::table('pos_credit_approvals')->where('company_id', $companyId)->where('id', $id)
            ->where('status', 'pending')->update([
                'status' => $decision,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with($updated ? 'success' : 'error', $updated
            ? 'Credit sale request '.$decision.'.'
            : 'This request is no longer pending.');
    }

    private function authorizedCompany(Request $request): int
    {
        $user = $request->user();
        $companyId = (int) (session('current_tenant_id') ?? $user?->company_id);
        abort_unless($companyId > 0 && $user?->canManageRoles(), 403);

        return $companyId;
    }
}
