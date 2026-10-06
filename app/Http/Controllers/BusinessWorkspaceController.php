<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\ActiveBranchResolver;
use App\Support\BusinessWorkspaceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessWorkspaceController extends Controller
{
    public function activate(
        Request $request,
        int $companyId,
        BusinessWorkspaceResolver $workspaceResolver,
        ActiveBranchResolver $branchResolver
    ): RedirectResponse {
        $company = Company::withoutGlobalScope('tenant')->findOrFail($companyId);
        $workspaceResolver->activate($request->user(), $company);
        $branchResolver->ensureSession($request->user());

        return redirect()->route('user.dashboard')
            ->with('success', 'Switched to '.($company->name ?? $company->company_name ?? 'business workspace').'.');
    }

    public function create(Request $request, BusinessWorkspaceResolver $workspaceResolver): RedirectResponse
    {
        $user = $request->user();
        $primaryCompanyId = (int) ($user->getRawOriginal('company_id') ?? 0);
        $isOwner = $workspaceResolver->accessibleCompanies($user)->contains(
            fn (Company $company) => (int) $company->user_id === (int) $user->id
                || (int) $company->owner_id === (int) $user->id
        );

        abort_unless($isOwner || $primaryCompanyId === 0, 403, 'Only a business owner can add another business.');

        session(['creating_additional_business' => true]);

        return redirect()->route('membership-plans', ['stay' => 1, 'new_business' => 1])
            ->with('info', 'Choose a separate subscription for the new business.');
    }
}
