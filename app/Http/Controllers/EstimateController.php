<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Customer;
use Illuminate\Support\Facades\Schema;

class EstimateController extends Controller
{
    private function normalizeEstimatePayload(array $validated): array
    {
        $subtotal = Estimate::normalizeMoney($validated['subtotal'] ?? 0);
        $tax = Estimate::normalizeMoney($validated['tax'] ?? 0);
        $discount = Estimate::normalizeMoney($validated['discount'] ?? 0);

        $validated['subtotal'] = $subtotal;
        $validated['tax'] = $tax;
        $validated['discount'] = $discount;
        $validated['total_amount'] = Estimate::calculateTotal($subtotal, $tax, $discount);

        return $validated;
    }

    private function applyTenantScope($query)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('estimates', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('estimates', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function index()
    {
        $estimates = $this->applyTenantScope(Estimate::with('customer'))->get();

        $sent = $this->applyTenantScope(Estimate::query())->where('status', 'Sent')->count();
        $draft = $this->applyTenantScope(Estimate::query())->where('status', 'Draft')->count();
        $expired = $this->applyTenantScope(Estimate::query())->where('status', 'Expired')->count();

        // Updated view path to match your Blade file location
        return view('livewire.index-estimates', compact('estimates', 'sent', 'draft', 'expired'));
    }

    public function create()
    {
        $customersQuery = Customer::query();
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $customersQuery->where('company_id', $companyId);
        }

        $customers = $customersQuery
            ->orderBy(Schema::hasColumn('customers', 'name') ? 'name' : 'id')
            ->get();

        return view('estimates.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estimate_number' => 'required|string|unique:estimates',
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:issue_date',
            'subtotal' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'status' => 'required|in:Draft,Sent,Accepted,Declined,Expired',
            'notes' => 'nullable|string',
        ]);

        $payload = $this->normalizeEstimatePayload($validated);
        if (Schema::hasColumn('estimates', 'company_id')) {
            $payload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('estimates', 'user_id')) {
            $payload['user_id'] = auth()->id();
        }

        Estimate::create($payload);

        return redirect()->route('estimates.index')->with('success', 'Estimate created successfully.');
    }

    public function show($id)
    {
        $estimate = $this->applyTenantScope(Estimate::with('customer'))->findOrFail($id);
        return view('estimates.show', compact('estimate'));
    }

    public function edit($id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);

        $customersQuery = Customer::query();
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $customersQuery->where('company_id', $companyId);
        }

        $customers = $customersQuery
            ->orderBy(Schema::hasColumn('customers', 'name') ? 'name' : 'id')
            ->get();

        return view('estimates.edit', compact('estimate', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);

        $validated = $request->validate([
            'estimate_number' => 'required|string|unique:estimates,estimate_number,' . $estimate->id,
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:issue_date',
            'subtotal' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'status' => 'required|in:Draft,Sent,Accepted,Declined,Expired',
            'notes' => 'nullable|string',
        ]);

        $estimate->update($this->normalizeEstimatePayload($validated));

        return redirect()->route('estimates.index')->with('success', 'Estimate updated successfully.');
    }

    public function destroy($id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);
        $estimate->delete();

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }
}
