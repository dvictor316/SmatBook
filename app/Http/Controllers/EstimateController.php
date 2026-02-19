<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;

class EstimateController extends Controller
{
    public function index()
    {
        $estimates = Estimate::with('customer')->get();

        $sent = Estimate::where('status', 'Sent')->count();
        $draft = Estimate::where('status', 'Draft')->count();
        $expired = Estimate::where('status', 'Expired')->count();

        // Updated view path to match your Blade file location
        return view('livewire.index-estimates', compact('estimates', 'sent', 'draft', 'expired'));
    }

    public function create()
    {
        return view('estimates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
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

        Estimate::create($request->all());

        return redirect()->route('estimates.index')->with('success', 'Estimate created successfully.');
    }

    public function show($id)
    {
        $estimate = Estimate::with('customer')->findOrFail($id);
        return view('estimates.show', compact('estimate'));
    }

    public function edit($id)
    {
        $estimate = Estimate::findOrFail($id);
        return view('estimates.edit', compact('estimate'));
    }

    public function update(Request $request, $id)
    {
        $estimate = Estimate::findOrFail($id);

        $request->validate([
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

        $estimate->update($request->all());

        return redirect()->route('estimates.index')->with('success', 'Estimate updated successfully.');
    }

    public function destroy($id)
    {
        $estimate = Estimate::findOrFail($id);
        $estimate->delete();

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }
}