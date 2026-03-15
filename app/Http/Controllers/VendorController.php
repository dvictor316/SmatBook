<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; 
use Illuminate\Routing\Redirector; 
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
     */
    public function index(Request $request): View
    {
        $query = Vendor::query()->latest();

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $vendors = $query->paginate(20)->withQueryString();

        // Loop through vendors and calculate their actual current balance dynamically
        foreach ($vendors as $vendor) {
            $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');
        }

        return view('Customers.vendors', compact('vendors'));
    }

    /**
     * Show the form for creating a new vendor.
     */
    public function create(): View
    {
        return view('Customers.create');
    }
    
    /**
     * Store a newly created vendor in storage and record initial balance transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:vendors,email',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'balance' => 'nullable|numeric|min:0',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        // 1. Create the Vendor account
        $vendor = Vendor::create($validated);

        // 2. Create the initial ledger transaction regardless of the balance amount
        $initialAmount = (float) $request->input('balance', 0.00);

        VendorLedgerTransaction::create([
            'vendor_id' => $vendor->id,
            'name' => 'Initial Balance on Creation',
            'reference' => 'SYS-INIT',
            'mode' => 'System',
            'amount' => $initialAmount,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor added successfully!');
    }

    /**
     * Handle the general ledger route (no ID provided).
     */
    public function ledger_general(): View
    {
         return view('Customers.ledger');
    }

    /**
     * Handle the specific vendor ledger route (ID provided).
     */
    public function ledger($id): View
    {
        $vendor = Vendor::findOrFail($id);
        
        $transactions = VendorLedgerTransaction::where('vendor_id', $vendor->id)
                                ->orderBy('created_at', 'asc')
                                ->get();

        $closingBalance = $transactions->sum('amount');

        return view('Customers.ledger', compact('vendor', 'transactions', 'closingBalance')); 
    }

    /**
     * Route alias handler used by vendors/{id}/ledger.
     */
    public function vendorLedger($id): View
    {
        return $this->ledger($id);
    }
    
    /**
     * Show the form for adding a new transaction to a vendor's ledger.
     */
    public function createTransaction($id): View
    {
        $vendor = Vendor::findOrFail($id);
        return view('Customers.create_transaction', compact('vendor'));
    }

    /**
     * Store a new transaction for a specific vendor.
     */
    public function storeTransaction(Request $request, $id): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'reference' => 'required|string|max:191',
            'mode' => 'required|string|max:191',
            'amount' => 'required|numeric',
        ]);
        
        $validated['vendor_id'] = $vendor->id;

        VendorLedgerTransaction::create($validated);

        return redirect()->route('vendors.ledger', ['id' => $vendor->id])->with('success', 'Transaction added.');
    }

    public function updateLedgerProfile(Request $request, $id): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:vendors,email,' . $vendor->id . '|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
                Storage::disk('public')->delete($vendor->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Vendor profile updated successfully.');
    }

    public function updateTransaction(Request $request, $id, $transactionId): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);
        $transaction = VendorLedgerTransaction::where('vendor_id', $vendor->id)->findOrFail($transactionId);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'reference' => 'required|string|max:191',
            'mode' => 'required|string|max:191',
            'amount' => 'required|numeric',
        ]);

        $transaction->update($validated);

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroyTransaction($id, $transactionId): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);
        $transaction = VendorLedgerTransaction::where('vendor_id', $vendor->id)->findOrFail($transactionId);
        $transaction->delete();

        return redirect()
            ->route('vendors.ledger', ['id' => $vendor->id])
            ->with('success', 'Transaction deleted successfully.');
    }
    
    /**
     * Show the form for editing the specified vendor.
     */
    public function edit($id): View
    {
        $vendor = Vendor::findOrFail($id);
        // Calculate current balance here to display it in the edit view
        $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');

        return view('Customers.edit', compact('vendor'));
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:vendors,email,'.$vendor->id.'|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'logo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
                Storage::disk('public')->delete($vendor->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully!');
    }

    /**
     * Remove the specified vendor from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);
        if ($vendor->logo && Storage::disk('public')->exists($vendor->logo)) {
            Storage::disk('public')->delete($vendor->logo);
        }
        $vendor->delete(); 

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully!');
    }

    /**
     * Show a single vendor record page.
     */
    public function show($id): View
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->current_balance = VendorLedgerTransaction::where('vendor_id', $vendor->id)->sum('amount');
        $transactions = VendorLedgerTransaction::where('vendor_id', $vendor->id)
            ->latest()
            ->limit(15)
            ->get();

        return view('Customers.ledger', [
            'vendor' => $vendor,
            'transactions' => $transactions,
            'closingBalance' => $vendor->current_balance,
        ]);
    }
}
