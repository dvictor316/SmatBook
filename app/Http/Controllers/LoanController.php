<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $loans = Loan::forCompany($companyId)
            ->with('bank')
            ->latest()
            ->paginate(25);

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $banks     = Bank::where('company_id', $companyId)->orderBy('name')->get();
        return view('loans.create', compact('banks'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'loan_number'          => 'nullable|string|max:100',
            'type'                 => 'required|in:loan,overdraft,credit_line',
            'lender_name'          => 'required|string|max:255',
            'bank_id'              => 'nullable|exists:banks,id',
            'principal_amount'     => 'required|numeric|min:0.01',
            'interest_rate'        => 'required|numeric|min:0',
            'interest_type'        => 'required|in:fixed,floating',
            'disbursement_date'    => 'required|date',
            'maturity_date'        => 'nullable|date|after_or_equal:disbursement_date',
            'tenure_months'        => 'nullable|integer|min:1',
            'repayment_frequency'  => 'required|in:monthly,quarterly,bi_annually,annually,bullet',
            'notes'                => 'nullable|string',
        ]);

        $data['company_id']         = $companyId;
        $data['branch_id']          = Auth::user()->branch_id;
        $data['outstanding_balance'] = $data['principal_amount'];
        $data['status']             = 'active';
        $data['created_by']         = Auth::id();

        Loan::create($data);

        return redirect()->route('loans.index')
            ->with('success', 'Loan recorded.');
    }

    public function show(Loan $loan)
    {
        $this->authorizeLoanAccess($loan);
        $loan->load(['repayments', 'bank']);
        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $this->authorizeLoanAccess($loan);
        $banks = Bank::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        return view('loans.edit', compact('loan', 'banks'));
    }

    public function update(Request $request, Loan $loan)
    {
        $this->authorizeLoanAccess($loan);

        $data = $request->validate([
            'lender_name'   => 'required|string|max:255',
            'interest_rate' => 'required|numeric|min:0',
            'maturity_date' => 'nullable|date',
            'status'        => 'required|in:active,closed,defaulted',
            'notes'         => 'nullable|string',
        ]);

        $loan->update($data);

        return redirect()->route('loans.index')->with('success', 'Loan updated.');
    }

    public function addRepayment(Request $request, Loan $loan)
    {
        $this->authorizeLoanAccess($loan);

        $data = $request->validate([
            'payment_date'   => 'required|date',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid'  => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,cheque',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        $total = (float) $data['principal_paid'] + (float) $data['interest_paid'];
        abort_if($total > (float) $loan->outstanding_balance + (float) $data['interest_paid'],
            422, 'Repayment exceeds outstanding balance.');

        $data['total_paid']  = $total;
        $data['company_id']  = $loan->company_id;
        $data['loan_id']     = $loan->id;
        $data['created_by']  = Auth::id();

        LoanRepayment::create($data);

        $loan->decrement('outstanding_balance', $data['principal_paid']);

        if ((float) $loan->fresh()->outstanding_balance <= 0) {
            $loan->update(['status' => 'closed']);
        }

        return back()->with('success', 'Repayment recorded.');
    }

    public function destroy(Loan $loan)
    {
        $this->authorizeLoanAccess($loan);
        abort_if($loan->repayments()->count() > 0, 422,
            'Cannot delete a loan with existing repayments.');
        $loan->delete();
        return redirect()->route('loans.index')->with('success', 'Loan deleted.');
    }

    private function authorizeLoanAccess(Loan $loan): void
    {
        abort_unless($loan->company_id === Auth::user()->company_id, 403);
    }
}
