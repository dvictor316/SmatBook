<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index()
    {
        $query = Customer::query()->latest();

        if ($search = trim((string) request('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $customers = $query->paginate(20)->withQueryString();
        return view('Customers.customers', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('Customers.add-customer'); 
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email',
            'phone'         => 'nullable|string|max:191',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Capture all possible form fields, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'currency', 'website', 'notes',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        $data['status'] = 'active'; 
        $data['balance'] = 0.00;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $data = $this->sanitizeForCustomerColumns($data);

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::with(['sales', 'invoices'])->findOrFail($id);
        $invoices = $customer->invoices;

        $invoicescards = [
            [
                'title'  => 'Total Invoices',
                'amount' => $invoices->count(),
                'icon'   => 'clipboard-text',
                'class'  => 'bg-blue-light',
            ],
            [
                'title'  => 'Total Sales',
                'amount' => '₦' . number_format($customer->sales->sum('total'), 2),
                'icon'   => 'archive',
                'class'  => 'bg-green-light',
            ],
            [
                'title'  => 'Pending Balance',
                'amount' => '₦' . number_format($customer->balance, 2),
                'icon'   => 'clock',
                'class'  => 'bg-orange-light',
            ],
            [
                'title'  => 'Total Paid',
                'amount' => '₦' . number_format($customer->sales->sum('amount_paid'), 2),
                'icon'   => 'check-circle',
                'class'  => 'bg-emerald-light',
            ]
        ];

        return view('Customers.customer-details', compact('customer', 'invoices', 'invoicescards'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('Customers.edit-customer', compact('customer'));
    }

   /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email,' . $id,
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status'        => 'required|in:active,deactive',
        ]);

        // Capture all possible form fields for update, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'status', 'currency', 'website', 'notes',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        if ($request->hasFile('image')) {
            if ($customer->image && Storage::disk('public')->exists($customer->image)) {
                Storage::disk('public')->delete($customer->image);
            }
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $data = $this->sanitizeForCustomerColumns($data);

        $customer->update($data);

        // CHANGE THIS LINE: 
        // From: return redirect()->route('customers.show', $customer->id)
        // To:   return redirect()->route('customers.index')
        
        return redirect()->route('customers.index')
                         ->with('success', 'Customer record updated successfully.');
    }
    /**
     * Toggle Status and Filters.
     */
    public function activeView()
    {
        $customers = Customer::where('status', 'active')->latest()->paginate(20);
        return view('Customers.customers', compact('customers'));
    }

    public function deactiveView()
    {
        $customers = Customer::where('status', 'deactive')->latest()->paginate(20);
        return view('Customers.customers', compact('customers'));
    }

    public function activate($id)
    {
        Customer::findOrFail($id)->update(['status' => 'active']);
        return back()->with('success', 'Customer activated.');
    }

    public function deactivate($id)
    {
        Customer::findOrFail($id)->update(['status' => 'deactive']);
        return back()->with('success', 'Customer deactivated.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        
        if ($customer->image) {
            Storage::disk('public')->delete($customer->image);
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function export($id)
    {
        $customer = Customer::findOrFail($id);

        $filename = 'customer_' . $customer->id . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($customer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Customer Name', 'Email', 'Phone', 'Status', 'Balance', 'Created At']);
            fputcsv($out, [
                $customer->id,
                $customer->customer_name,
                $customer->email,
                $customer->phone,
                $customer->status,
                $customer->balance,
                optional($customer->created_at)->toDateTimeString(),
            ]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function apiIndex(Request $request)
    {
        $query = Customer::query()->select(['id', 'customer_name', 'email', 'phone', 'status', 'balance']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->limit(100)->get());
    }

    private function sanitizeForCustomerColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('customers'));
        return array_intersect_key($data, $allowed);
    }
}
