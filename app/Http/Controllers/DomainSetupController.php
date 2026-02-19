<?php

namespace App\Http\Controllers;

use App\Models\Domain; // Uses the main Domain model now
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainSetupController extends Controller
{
    /**
     * SHOW WIZARD: The clean, centered setup page.
     * URL: /setup-domain
     */
    public function create()
    {
        $user = Auth::user();

        // Prevent users from submitting multiple domain requests if one already exists
        $alreadySubmitted = Domain::where('email', $user->email)
            ->orWhere('customer_id', $user->id)
            ->exists();

        if ($alreadySubmitted) {
            return redirect()->route('home')->with('info', 'Your domain setup is already in progress.');
        }

        // Retrieve plan name from session (set during PaymentController callback)
        $selectedPlan = session('selected_plan', 'Standard');

        return view('user.setup-wizard', compact('selectedPlan'));
    }

    /**
     * STORE: Save the request to the 'domains' table and redirect to dashboard.
     * URL: /setup-domain/save
     */
    public function store(Request $request)
    {
        // Validation matches your 'domain_name' column requirements (Unique)
        $request->validate([
            'domain_name' => 'required|string|unique:domains,domain_name|max:191',
            'employees'   => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        // Create the record in the 'domains' table matching your 12-column schema
        Domain::create([
            'customer_name' => $user->name,
            'email'         => $user->email,
            'domain_name'   => $request->domain_name, // Column name from your SQL describe
            'customer_id'   => $user->id,
            'package_name'  => session('selected_plan', 'Standard'),
            'package_type'  => 'Yearly',
            'employees'     => $request->employees ?? 0,
            'status'        => 'Pending', // Default from your schema
            'expiry_date'   => null,      // Admin sets this upon activation
        ]);

        // Clear the session data after successful creation
        session()->forget('selected_plan');

        return redirect()->route('home')->with('success', 'Workspace setup initiated! Our admin will activate your domain shortly.');
    }
}