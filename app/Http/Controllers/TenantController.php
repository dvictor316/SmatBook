<?php

namespace App\Http\Controllers;

use App\Models\{Company, Subscription, Plan, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Artisan};

class TenantController extends Controller
{
    /**
     * Step 2 of SaaS: Show Setup Page
     * Path: /saas/setup/{subscription_id?}
     */
    public function showSetup($id = null)
    {
        // 1. Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('saas-register-initial');
        }

        // 2. Find the subscription record
        $subscription = $id 
            ? Subscription::find($id) 
            : Subscription::where('user_id', Auth::id())
                ->whereIn('status', ['Pending', 'pending_payment'])
                ->latest()
                ->first();
            
        if (!$subscription) {
            return redirect()->route('saas-register-initial')->with('error', 'Please select a plan first.');
        }

        // 3. SECURITY: Verify Ownership
        if ((int)$subscription->user_id !== (int)Auth::id()) {
            abort(403, 'Unauthorized access to this node.');
        }

        // --- PERSISTENCE LOGIC START ---
        // Mark that the user has reached the Setup Phase (Step 2)
        $company = Company::where('user_id', Auth::id())->first();
        if ($company) {
            $company->update(['onboarding_step' => 2]);
        }
        // --- PERSISTENCE LOGIC END ---

        // 4. SMART REDIRECT: Check if Company (Workspace) is already created
        if ($company && !empty($company->domain_prefix)) {
            return redirect()->route('saas.checkout', ['id' => $subscription->id]);
        }

        return view('Saas.domain_setup', compact('subscription', 'company'));
    }

    /**
     * Finalize Workspace Creation
     */
    public function finalizeSetup(Request $request)
    {
        \Log::info('Setup Submission:', $request->all());

        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'subscriber_name' => 'required|string|max:255',
            'industry'        => 'required|string',
            'domain_prefix'   => 'required|alpha_dash|unique:companies,domain_prefix',
            'employee_size'   => 'required|integer',
        ]);

        return DB::transaction(function () use ($request) {
            $subscription = Subscription::findOrFail($request->subscription_id);

            // 1. Create/Update Company & ADVANCE TO STEP 3 (Payment)
            $company = Company::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'name'            => $request->subscriber_name,
                    'industry'        => $request->industry,
                    'domain_prefix'   => strtolower($request->domain_prefix),
                    'status'          => 'pending_payment',
                    'plan'            => $subscription->plan_name,
                    'email'           => Auth::user()->email,
                    'onboarding_step' => 3, // Set checkpoint to Payment
                ]
            );

            // 2. Update Subscription with Setup Data
            $subscription->update([
                'subscriber_name' => $request->subscriber_name,
                'domain_prefix'   => strtolower($request->domain_prefix),
                'employee_size'   => $request->employee_size,
                'status'          => 'pending_payment'
            ]);

            return redirect()->route('saas.checkout', ['id' => $subscription->id])
                             ->with('success', 'Workspace details saved. Proceed to payment.');
        });
    }

    /**
     * Save the current state of the Tenant's onboarding via AJAX/Fetch
     */
    public function saveState(Request $request)
    {
        $user = Auth::user();
        $company = Company::where('user_id', $user->id)->first();

        if ($company) {
            $company->update([
                'onboarding_step' => $request->query('step'),
                'updated_at' => now()
            ]);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'company_not_found'], 404);
    }
/**
     * The Main Redirector used after Login
     */
    public function handlePostLoginRedirect()
    {
        $user = Auth::user();
        
        // 1. Admin Master Key - Ensure this points to the correct NAMED route
        if ($user->email === 'donvictorlive@gmail.com') {
             return redirect()->route('user.dashboard');
        }

        $company = Company::where('user_id', $user->id)->first();

        // 2. If no company exists, they haven't even started Setup
        if (!$company) {
            return redirect()->route('membership-plans');
        }

        // 3. Check if Fully active (Onboarding Complete)
        // We use the NAMED route 'user.dashboard' to avoid RouteNotFound errors
        if ($company->status === 'active' || (int)$company->onboarding_step === 4) {
            return redirect()->route('user.dashboard');
        }

        // 4. If stuck at Payment (Step 3)
        if ((int)$company->onboarding_step === 3) {
            $sub = Subscription::where('user_id', $user->id)
                ->whereIn('status', ['Pending', 'pending_payment'])
                ->latest()
                ->first();
                
            return redirect()->route('saas.checkout', ['id' => $sub->id ?? 0]);
        }

        // 5. If stuck at Setup (Step 2)
        if ((int)$company->onboarding_step === 2) {
             $sub = Subscription::where('user_id', $user->id)->latest()->first();
             return redirect()->route('saas.setup', ['id' => $sub->id ?? 0]);
        }

        // 6. Fallback: If they have a company but no step, send to setup
        return redirect()->route('saas.setup');
    }
}
