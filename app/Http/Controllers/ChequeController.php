<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChequeController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $query = Cheque::query()->with(['supplier', 'customer', 'bank']);
        $this->applyTenantBranchScope($query, 'cheques');

        if (in_array($type, ['issue', 'receive'])) {
            $query->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $cheques = $query->latest('cheque_date')->paginate(25);

        return view('cheques.index', compact('cheques', 'type'));
    }

    public function create()
    {
        $suppliers = Supplier::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
            ->orderBy('name')
            ->get();
        $customers = Customer::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'customers'))
            ->orderBy('customer_name')
            ->get();
        $banks     = Bank::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'banks'))
            ->orderBy('name')
            ->get();

        return view('cheques.create', compact('suppliers', 'customers', 'banks'));
    }

    public function store(Request $request)
    {
        $scope = $this->scopeContext();
        $companyId = $scope['company_id'];
        $branchId = $scope['branch_id'] !== '' ? $scope['branch_id'] : (Auth::user()->branch_id ?? null);

        $data = $request->validate([
            'cheque_number' => 'required|string|max:100',
            'type'          => 'required|in:issue,receive',
            'bank_id'       => 'nullable|exists:banks,id',
            'payee_name'    => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01',
            'cheque_date'   => 'required|date',
            'due_date'      => 'nullable|date|after_or_equal:cheque_date',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'customer_id'   => 'nullable|exists:customers,id',
            'notes'         => 'nullable|string|max:500',
        ]);

        if (!empty($data['supplier_id'])) {
            Supplier::query()
                ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
                ->findOrFail($data['supplier_id']);
        }

        if (!empty($data['customer_id'])) {
            Customer::query()
                ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'customers'))
                ->findOrFail($data['customer_id']);
        }

        if (!empty($data['bank_id'])) {
            Bank::query()
                ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'banks'))
                ->findOrFail($data['bank_id']);
        }

        $data['company_id'] = $companyId;
        $data['branch_id']  = $branchId;
        $data['created_by'] = Auth::id();
        $data['status']     = 'pending';

        Cheque::create($data);

        return redirect()->route('cheques.index')
            ->with('success', 'Cheque recorded successfully.');
    }

    public function show(Cheque $cheque)
    {
        $this->authorizeChequeAccess($cheque);
        $cheque->load(['supplier', 'customer', 'bank']);
        return view('cheques.show', compact('cheque'));
    }

    public function edit(Cheque $cheque)
    {
        $this->authorizeChequeAccess($cheque);
        $suppliers = Supplier::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
            ->orderBy('name')
            ->get();
        $customers = Customer::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'customers'))
            ->orderBy('customer_name')
            ->get();
        $banks     = Bank::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'banks'))
            ->orderBy('name')
            ->get();
        return view('cheques.edit', compact('cheque', 'suppliers', 'customers', 'banks'));
    }

    public function update(Request $request, Cheque $cheque)
    {
        $this->authorizeChequeAccess($cheque);

        $data = $request->validate([
            'payee_name'  => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'cheque_date' => 'required|date',
            'due_date'    => 'nullable|date',
            'status'      => 'required|in:pending,cleared,bounced,cancelled,voided,deposited',
            'notes'       => 'nullable|string|max:500',
        ]);

        $cheque->update($data);

        return redirect()->route('cheques.index')
            ->with('success', 'Cheque updated.');
    }

    public function updateStatus(Request $request, Cheque $cheque)
    {
        $this->authorizeChequeAccess($cheque);

        $data = $request->validate([
            'status' => 'required|in:pending,cleared,bounced,cancelled,voided,deposited',
        ]);

        $cheque->update($data);

        return back()->with('success', 'Cheque status updated to ' . $data['status'] . '.');
    }

    public function destroy(Cheque $cheque)
    {
        $this->authorizeChequeAccess($cheque);
        abort_if(in_array($cheque->status, ['cleared', 'deposited']), 422,
            'Cannot delete a cleared or deposited cheque.');
        $cheque->delete();
        return redirect()->route('cheques.index')->with('success', 'Cheque deleted.');
    }

    private function authorizeChequeAccess(Cheque $cheque): void
    {
        $this->authorizeTenantBranchModelAccess($cheque);
    }
}
