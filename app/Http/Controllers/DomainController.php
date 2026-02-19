<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DomainController extends Controller
{
    /**
     * Display the Master List with Search & Filtering
     */
    public function index(Request $request)
    {
        $query = Domain::query();

        // Optimized Search: Search across multiple related fields
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('customer_name', 'like', $searchTerm)
                  ->orWhere('domain_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('package_name', 'like', $searchTerm);
            });
        }

        // Status Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Customer ID Filtering
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $domains = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Fetch users with 'customer' role for filtering dropdowns
        $customers = User::where('role', 'customer')->get();

        return view('SuperAdmin.domain-request', compact('domains', 'customers'));
    }

    /**
     * Show the Workspace Setup Form
     * Fixed unauthorized check logic
     */
    public function setup($domainId)
    {
        $user = Auth::user();
        if ($user->role !== 'super_admin' && $user->role !== 'admin') {
            return redirect()->route('login')->with('error', 'Unauthorized access');
        }

        $domain = Domain::findOrFail($domainId);
        
        // Retrieve the most recent subscription associated with the customer
        $subscription = Subscription::where('user_id', $domain->customer_id ?? $domain->user_id)
            ->latest()
            ->with(['plan', 'user'])
            ->first();

        return view('SuperAdmin.workspace-setup', compact('subscription', 'domain'));
    }

    /**
     * Edit an existing domain record
     * Handles: /superadmin/domains/edit/{id}
     */
    public function edit($id)
    {
        $domain = Domain::findOrFail($id);
        return view('SuperAdmin.domains.edit', compact('domain'));
    }

    /**
     * Update Domain Details & Expiry
     */
    public function update(Request $request, $id)
    {
        $domain = Domain::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email'         => 'required|email',
            'domain_name'   => 'required|unique:domains,domain_name,' . $domain->id,
            'status'        => 'required|in:Active,Pending,Expired,Suspended',
            'expiry_date'   => 'nullable|date',
            'package_name'  => 'required|string',
            'employees'     => 'required|integer',
        ]);

        // Auto-set expiry if admin activates it without picking a specific date
        if ($validated['status'] === 'Active' && empty($validated['expiry_date'])) {
            $validated['expiry_date'] = now()->addYear();
        }

        $domain->update($validated);

        return redirect()->route('super_admin.domains.index')->with('success', 'Domain updated successfully.');
    }

    /**
     * Store the Workspace Setup (Initialize Hub & Launch)
     */
    public function storeSetup(Request $request, $domainId)
    {
        $domain = Domain::findOrFail($domainId);

        $validated = $request->validate([
            'domain_prefix' => [
                'required', 
                'string', 
                'lowercase', 
                'regex:/^[a-z0-9\-]+$/', 
                'min:3', 
                'max:63', 
                'unique:domains,domain_name,' . $domain->id
            ],
            'organization_scale' => 'required|string|in:1-10,11-50,51-200,201-500,500+',
        ]);

        DB::transaction(function () use ($domain, $validated) {
            // 1. Update Domain record
            $domain->update([
                'domain_name'        => $validated['domain_prefix'],
                'organization_scale' => $validated['organization_scale'],
                'status'             => 'Active',
                'setup_completed_at' => now(),
            ]);

            // 2. Locate and activate the Subscription
            $subscription = Subscription::where('user_id', $domain->customer_id ?? $domain->user_id)
                ->latest()
                ->first();

            if ($subscription) {
                $subscription->update([
                    'status'         => 'active',
                    'initialized_at' => now(),
                ]);
            }
        });

        return redirect()->route('super_admin.domains.index')
            ->with('success', 'Workspace successfully initialized for ' . $domain->customer_name);
    }

    /**
     * AJAX: Fetch single domain details for the View Modal
     */
    public function show($id)
    {
        $domain = Domain::findOrFail($id);
        
        return response()->json([
            'domain'       => $domain->domain_name,
            'status'       => $domain->status,
            'customer'     => $domain->customer_name,
            'email'        => $domain->email,
            'package'      => $domain->package_name,
            'employees'    => $domain->employees,
            'package_type' => $domain->package_type,
            'expiry_date'  => $domain->expiry_date ? Carbon::parse($domain->expiry_date)->format('d M Y') : 'Not Set',
            'created_at'   => $domain->created_at->format('d M Y'),
        ]);
    }

    /**
     * AJAX & Quick Action: Update Status Toggle
     */
    public function updateStatus(Request $request, $id)
    {
        $domain = Domain::findOrFail($id);
        $newStatus = $request->status;
        $updateData = ['status' => $newStatus];

        if ($newStatus === 'Active' && is_null($domain->expiry_date)) {
            $updateData['expiry_date'] = now()->addYear();
        }

        $domain->update($updateData);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated.']);
        }

        return back()->with('success', 'Status updated to ' . $newStatus);
    }

    /**
     * Remove Domain Record
     */
    public function destroy($id)
    {
        $domain = Domain::findOrFail($id);
        $domain->delete();

        return redirect()->route('super_admin.domains.index')->with('success', 'Domain record deleted.');
    }
}