<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Domain;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the subscription packages.
     * Table: plans
     */
    public function index()
    {
        $plans = Plan::all();
        
        // Calculations for the dashboard statistics cards
        $totalPlans = $plans->count();
        $activePlans = $plans->where('is_active', 1)->count();
        $pendingPlans = $plans->where('is_active', 0)->count();
        $planTypesCount = $plans->pluck('billing_cycle')->unique()->count();

        return view('SuperAdmin.packages', compact(
            'plans', 
            'totalPlans', 
            'activePlans', 
            'pendingPlans', 
            'planTypesCount'
        ));
    }

    /**
     * Show the edit form for a specific plan.
     */
    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        return view('SuperAdmin.packages_edit', compact('plan'));
    }

    /**
     * List of all subscribers/domains.
     * Corrected to sum the 'price' column directly from the domains table.
     */
    public function subscribers(Request $request)
    {
        $selectedPlan = $request->query('plan');
        $query = Domain::query();

        if ($selectedPlan) {
            $query->where('package_name', $selectedPlan);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->get();

        // Financial Calculation: Sum the price column from domains
        $totalTransaction = $subscriptions->sum('price'); 

        return view('SuperAdmin.subscription', [
            'subscriptions'    => $subscriptions,
            'totalTransaction' => $totalTransaction,
            'selectedPlan'     => $selectedPlan
        ]);
    }

    /**
     * Store a new plan.
     * Features are handled as plain strings to avoid JSON errors.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|unique:plans,name',
            'price'    => 'required|numeric',
            'duration' => 'required|string', 
            'features' => 'nullable|string',
            'status'   => 'required',
        ]);

        // Clean features string for TEXT column storage
        $features = $request->has('features') 
            ? str_replace(['[', ']', '"', '\\'], '', strip_tags($request->features)) 
            : null;

        Plan::create([
            'name'          => $validated['name'],
            'price'         => $validated['price'],
            'billing_cycle' => strtolower($validated['duration']),
            'features'      => $features,
            'status'        => $validated['status'] == 1 ? 'active' : 'inactive',
            'is_active'     => $validated['status'],
        ]);

        return redirect()->route('super_admin.packages.index')->with('success', 'Plan added to the database successfully.');
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|unique:plans,name,' . $id,
            'price'    => 'required|numeric',
            'duration' => 'required|string',
            'status'   => 'required',
        ]);

        // Clean features string for TEXT column storage
        $features = $request->has('features') 
            ? str_replace(['[', ']', '"', '\\'], '', strip_tags($request->features)) 
            : $plan->features;

        $plan->update([
            'name'          => $request->name,
            'price'         => $request->price,
            'billing_cycle' => strtolower($request->duration),
            'features'      => $features,
            'status'        => $request->status == 1 ? 'active' : 'inactive',
            'is_active'     => $request->status,
        ]);

        return redirect()->route('super_admin.packages.index')->with('success', 'Plan updated successfully.');
    }

    /**
     * Remove the plan.
     */
    public function destroy($id)
    {
        Plan::findOrFail($id)->delete();
        return redirect()->route('super_admin.packages.index')->with('success', 'Plan deleted successfully.');
    }
}