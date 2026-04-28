<?php

namespace App\Http\Controllers;

use App\Models\IntercompanyTransaction;
use App\Models\Company;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntercompanyController extends Controller
{
    public function index(Request $request)
    {
        $companyId      = Auth::user()->company_id;
        $transactions   = IntercompanyTransaction::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('counterparty_company_id', $companyId);
            })
            ->with(['company', 'counterpartyCompany', 'sourceAccount', 'targetAccount'])
            ->latest('transaction_date')
            ->paginate(25);

        return view('intercompany.index', compact('transactions'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $companies = Company::where('id', '!=', $companyId)->where('status', 'active')->get();
        $accounts  = Account::where('company_id', $companyId)->orderBy('name')->get();
        return view('intercompany.create', compact('companies', 'accounts'));
    }

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
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'counterparty_company_id'  => 'required|exists:companies,id',
            'transaction_type'         => 'required|in:loan,purchase,sale,allocation,management_fee,dividend,transfer',
            'transaction_date'         => 'required|date',
            'amount'                   => 'required|numeric|min:0.01',
            'currency'                 => 'required|string|size:3',
            'description'              => 'required|string|max:500',
            'source_account_id'        => 'nullable|exists:accounts,id',
            'target_account_id'        => 'nullable|exists:accounts,id',
            'reference_number'         => 'nullable|string|max:100',
        ]);

        abort_if($data['counterparty_company_id'] == $companyId, 422,
            'Cannot create intercompany transaction with the same company.');

            $branch = $this->getActiveBranchContext();
            IntercompanyTransaction::create([
                'company_id'               => $companyId,
                'branch_id'                => $branch['id'],
                'branch_name'              => $branch['name'],
                'counterparty_company_id'  => $data['counterparty_company_id'],
                'transaction_type'         => $data['transaction_type'],
                'transaction_date'         => $data['transaction_date'],
                'amount'                   => $data['amount'],
                'currency'                 => $data['currency'],
                'description'              => $data['description'],
                'source_account_id'        => $data['source_account_id'] ?? null,
                'target_account_id'        => $data['target_account_id'] ?? null,
                'reference_number'         => $data['reference_number'] ?? null,
                'status'                   => 'pending',
                'created_by'               => Auth::id(),
            ]);

        return redirect()->route('intercompany.index')
            ->with('success', 'Intercompany transaction created.');
    }

    public function approve(IntercompanyTransaction $intercompanyTransaction)
    {
        $this->authorizeIntercompanyAccess($intercompanyTransaction);
        abort_unless($intercompanyTransaction->status === 'pending', 422, 'Not pending.');

        $intercompanyTransaction->update([
            'status'      => 'posted',
        ]);

        return back()->with('success', 'Transaction approved.');
    }

    public function destroy(IntercompanyTransaction $intercompanyTransaction)
    {
        $this->authorizeIntercompanyAccess($intercompanyTransaction);
        abort_if($intercompanyTransaction->status === 'approved', 422,
            'Cannot delete an approved transaction.');
        $intercompanyTransaction->delete();
        return redirect()->route('intercompany.index')->with('success', 'Transaction deleted.');
    }

    private function authorizeIntercompanyAccess(IntercompanyTransaction $txn): void
    {
        abort_unless(
            $txn->company_id === Auth::user()->company_id ||
            $txn->counterparty_company_id === Auth::user()->company_id,
            403
        );
    }
}
