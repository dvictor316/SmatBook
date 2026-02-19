<?php

namespace App\Http\Controllers;

use App\Models\Plan; // Changed from Package to Plan
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index()
    {
        $plans = Plan::all();
        
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
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|unique:plans,name',
            'price'    => 'required|numeric',
            'duration' => 'required|string', // Comes from form as "duration"
            'features' => 'nullable|string',
            'status'   => 'required',
        ]);

        // Clean features: Remove JSON artifacts
        $features = $request->has('features') 
            ? str_replace(['[', ']', '"', '\\'], '', strip_tags($request->features)) 
            : null;

        // Map data to match the "plans" table columns
        Plan::create([
            'name'          => $validated['name'],
            'price'         => $validated['price'],
            'billing_cycle' => strtolower($validated['duration']), // maps "Monthly" to "monthly"
            'features'      => $features,
            'status'        => $validated['status'] == 1 ? 'active' : 'inactive',
            'is_active'     => $validated['status'],
            'recommended'   => 0,
        ]);

        return redirect()->route('super_admin.packages.index')->with('success', 'Plan merged and saved successfully.');
    }

    /**
     * Update the specified plan.
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
        return redirect()->route('super_admin.packages.index')->with('success', 'Plan deleted.');
    }
}