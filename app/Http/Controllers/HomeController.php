<?php

namespace App\Http\Controllers;

use App\Models\{Company, Subscription, User, DeploymentManager};
use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, DB, Log, Schema, Storage, Hash};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX — Master router for all user types
    |
    | 5 destination types:
    |  1. Super Admin          → /superadmin/dashboard
    |  2. Deployment Manager   → /deployment/dashboard
    |  3. New customer         → /saas/checkout/{id}   (unpaid subscription)
    |  4. Setup-pending tenant → /saas/setup/{id}      (no domain yet)
    |  5. Active tenant        → https://subdomain.smatbook.com
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            Log::warning('Home accessed without authentication');
            return redirect()->route('login');
        }

        Log::info('=== HOME ROUTE ACCESSED ===', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'role'    => $user->role,
        ]);

        if ($this->isTempOpenAccess()) {
            if ($this->isSuperAdmin($user)) {
                if (session('workspace_context') === 'business') {
                    return redirect()->route('workspace.business');
                }
                return redirect()->route('super_admin.dashboard');
            }

            $isDeploymentManager = DeploymentManager::where('user_id', $user->id)->exists()
                || in_array(strtolower((string) ($user->role ?? '')), ['deployment_manager', 'manager'], true);

            if ($isDeploymentManager) {
                return redirect()->route('deployment.dashboard');
            }

            return redirect()->route('user.dashboard');
        }

        // ── PRIORITY 1: Check deployment_managers table FIRST ──
        // Must happen before role check — deployment managers may have
        // role='administrator' which would otherwise match super admin.
        $isDeploymentManager = DeploymentManager::where('user_id', $user->id)->exists();

        if ($isDeploymentManager) {
            Log::info('User is DEPLOYMENT MANAGER (found in deployment_managers table)', [
                'user_id' => $user->id,
            ]);
            return $this->handleDeploymentManagerRedirect($user);
        }

        // ── PRIORITY 2: Super Admin ──
        if ($this->isSuperAdmin($user)) {
            Log::info('User is SUPER ADMIN', ['user_id' => $user->id]);
            if (session('workspace_context') === 'business') {
                Log::info('Super admin requested BUSINESS WORKSPACE context', ['user_id' => $user->id]);
                return redirect()->route('workspace.business');
            }
            return redirect()->route('super_admin.dashboard');
        }

        // ── PRIORITY 3: Regular Tenant ──
        Log::info('User is REGULAR TENANT', ['user_id' => $user->id]);
        return $this->handleRegularUserRedirect($user);
    }

    /*
    |--------------------------------------------------------------------------
    | DEPLOYMENT MANAGER REDIRECT
    |--------------------------------------------------------------------------
    */
    private function handleDeploymentManagerRedirect($user)
    {
        $manager = DeploymentManager::where('user_id', $user->id)->first();

        Log::info('Processing deployment manager', [
            'user_id'     => $user->id,
            'status'      => $manager->status ?? 'no_record',
            'is_verified' => $user->is_verified,
        ]);

        // No record or needs verification
        if (!$manager || $manager->status === 'pending_info' || !$user->is_verified) {
            Log::info('→ Redirecting to verification form');
            return redirect()->route('manager.verification.form');
        }

        // Suspended / Inactive / Rejected → log out
        if (in_array($manager->status, ['suspended', 'inactive', 'rejected'])) {
            Log::warning('Manager account restricted', ['status' => $manager->status]);
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been ' . $manager->status . '. Please contact support.');
        }

        // Pending approval
        if ($manager->status === 'pending') {
            Log::info('→ Manager pending approval');
            return redirect()->route('manager.pending.notice');
        }

        // Active
        if ($manager->status === 'active') {
            Log::info('→ Redirecting to DEPLOYMENT DASHBOARD');
            return redirect()->route('deployment.dashboard');
        }

        // Fallback
        return redirect()->route('manager.pending.notice');
    }

    /*
    |--------------------------------------------------------------------------
    | REGULAR USER REDIRECT
    |
    | Flow:
    |  no subscription          → /membership-plans
    |  unpaid subscription       → /saas/checkout/{id}   ← handles deployment-created customers
    |  paid but inactive         → /saas/setup/{id}
    |  paid, active, no company  → /saas/setup/{id}
    |  paid, active, has company → https://subdomain.smatbook.com
    |--------------------------------------------------------------------------
    */
    private function handleRegularUserRedirect($user)
    {
        // Fetch latest subscription regardless of payment status
        $subscription = Subscription::where('user_id', $user->id)
            ->latest()
            ->first();

        Log::info('Regular user redirect', [
            'user_id'          => $user->id,
            'has_subscription' => (bool) $subscription,
            'payment_status'   => $subscription?->payment_status,
            'sub_status'       => $subscription?->status,
        ]);

        // No subscription at all
        if (!$subscription) {
            Log::info('→ No subscription, redirecting to plans');
            return redirect()->route('membership-plans')
                ->with('info', 'Please select a plan to get started.');
        }

        // Unpaid → checkout (covers deployment-created customers on first login)
        if ($subscription->payment_status !== 'paid') {
            Log::info('→ Unpaid subscription, redirecting to checkout', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.checkout', $subscription->id);
        }

        // Paid but expired
        if ($subscription->isExpired()) {
            Log::info('→ Expired subscription, redirecting to plans', [
                'subscription_id' => $subscription->id,
                'end_date' => (string) $subscription->end_date,
            ]);
            return redirect()->route('membership-plans')
                ->with('error', 'Your subscription has expired. Please renew to continue.');
        }

        // Paid but subscription not yet Active
        if ($subscription->status !== 'Active') {
            Log::info('→ Inactive subscription, redirecting to setup', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.setup', $subscription->id);
        }

        // Active subscription — check company/domain exists
        $company = Company::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('owner_id', $user->id);
        })->first();

        if (!$company || empty($company->domain_prefix)) {
            Log::info('→ No company/domain, redirecting to setup', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.setup', $subscription->id);
        }

        // All good — send to workspace subdomain
        $mainDomain   = config('session.domain', env('SESSION_DOMAIN', 'smatbook.com'));
        $workspaceUrl = 'https://' . $company->domain_prefix . '.' . $mainDomain;

        Log::info('→ Redirecting to workspace', [
            'url'             => $workspaceUrl,
            'subscription_id' => $subscription->id,
        ]);

        // Store plan in session so sidebar knows which plan to show
        session([
            'user_plan' => strtolower($subscription->plan ?? $subscription->plan_name ?? 'basic'),
        ]);

        return redirect()->away($workspaceUrl);
    }

    /*
    |--------------------------------------------------------------------------
    | IS SUPER ADMIN — strict check, never matches deployment managers
    |
    | Only 'super_admin' / 'superadmin' roles + Victor's email.
    | 'administrator' is NOT included — deployment managers carry that role.
    |--------------------------------------------------------------------------
    */
    private function isSuperAdmin($user): bool
    {
        if ($user->email === 'donvictorlive@gmail.com') {
            return true;
        }

        return in_array(strtolower($user->role ?? ''), ['super_admin', 'superadmin']);
    }

    private function isTempOpenAccess(): bool
    {
        return (bool) env('TEMP_OPEN_ACCESS', false);
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD — Tenant plan-based dashboard view
    | Route: GET /dashboard   name: dashboard (or home.dashboard)
    |--------------------------------------------------------------------------
    */
    public function dashboard()
    {
        $user = Auth::user();

        $company = Company::where('user_id', $user->id)
            ->orWhere('owner_id', $user->id)
            ->first();

        $subscription = Subscription::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->latest()
            ->first();

        if (!$subscription || !$company) {
            return redirect()->route('membership-plans');
        }

        if ($subscription->isExpired()) {
            return redirect()->route('membership-plans')
                ->with('error', 'Your subscription expired on ' . optional($subscription->end_date)->format('M d, Y') . '. Please renew.');
        }

        if (strtolower((string) $subscription->status) !== 'active') {
            return redirect()->route('saas.setup', $subscription->id);
        }

        $planName = strtolower($subscription->plan ?? $subscription->plan_name ?? 'basic');

        // Store in session so layout/sidebar knows which plan sidebar to include
        session(['user_plan' => $planName]);

        return view('dashboard', compact('user', 'company', 'subscription'));
    }

    // Quotations / Delivery pages used by sidebar links
    public function quotations()
    {
        $quotations = Schema::hasTable('quotations')
            ? Quotation::with('customer')->latest()->paginate(20)
            : new LengthAwarePaginator([], 0, 20);
        return view('Quotations.quotations', compact('quotations'));
    }

    public function add_quotations()
    {
        $customers = collect();
        if (Schema::hasTable('customers')) {
            $customerNameColumn = Schema::hasColumn('customers', 'name') ? 'name' : 'customer_name';
            $customers = Customer::orderBy($customerNameColumn)->get();
        }
        return view('Quotations.add-quotations', compact('customers'));
    }

    public function edit_quotations($id = null)
    {
        $quotation = Schema::hasTable('quotations')
            ? ($id ? Quotation::findOrFail($id) : Quotation::latest()->first())
            : null;
        $customers = collect();
        if (Schema::hasTable('customers')) {
            $customerNameColumn = Schema::hasColumn('customers', 'name') ? 'name' : 'customer_name';
            $customers = Customer::orderBy($customerNameColumn)->get();
        }
        return view('Quotations.edit-quotations', compact('quotation', 'customers'));
    }

    public function storeQuotation(Request $request)
    {
        $customerValidation = Schema::hasTable('customers') ? 'nullable|exists:customers,id' : 'nullable';
        $validated = $request->validate([
            'quotation_id' => 'nullable|string|max:100',
            'customer_id' => $customerValidation,
            'total' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['quotation_id'])) {
            $validated['quotation_id'] = 'QTN-' . str_pad((string) ((Quotation::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
        }
        $validated['status'] = $validated['status'] ?? 'Pending';

        Quotation::create($validated);

        return redirect()->route('quotations')->with('success', 'Quotation created successfully.');
    }

    public function updateQuotation(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);
        $customerValidation = Schema::hasTable('customers') ? 'nullable|exists:customers,id' : 'nullable';

        $validated = $request->validate([
            'quotation_id' => 'required|string|max:100|unique:quotations,quotation_id,' . $quotation->id,
            'customer_id' => $customerValidation,
            'total' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
        ]);

        $validated['status'] = $validated['status'] ?? 'Pending';
        $quotation->update($validated);

        return redirect()->route('quotations')->with('success', 'Quotation updated successfully.');
    }

    public function destroyQuotation($id)
    {
        $quotation = Quotation::findOrFail($id);
        $quotation->delete();
        return redirect()->route('quotations')->with('success', 'Quotation deleted successfully.');
    }

    public function delivery_challans()
    {
        return view('Quotations.delivery-challans');
    }

    public function add_delivery_challans()
    {
        return view('Quotations.add-delivery-challans');
    }

    public function edit_delivery_challans()
    {
        return view('Quotations.edit-delivery-challans');
    }

    /*
    |--------------------------------------------------------------------------
    | PROFILE — View profile page
    | Route: GET /profile   name: profile (or home.profile)
    |--------------------------------------------------------------------------
    */
    public function profile()
    {
        $user = Auth::user();

        $profileFields = ['name', 'email', 'role', 'profile_photo', 'cover_photo'];
        $filledFields  = 0;

        foreach ($profileFields as $field) {
            if (!empty($user->$field)) {
                $filledFields++;
            }
        }

        $completeness = ($filledFields / count($profileFields)) * 100;

        return view('Pages.profile', compact('user', 'completeness'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE IMAGES — profile photo + cover photo
    | Route: POST /profile/images   name: profile.images (or similar)
    |--------------------------------------------------------------------------
    */
    public function updateProfileImages(Request $request)
    {
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cover_photo'   => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('profile_photo')
                ->store('users/profiles', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($user->cover_photo) {
                Storage::disk('public')->delete($user->cover_photo);
            }
            $user->cover_photo = $request->file('cover_photo')
                ->store('users/covers', 'public');
        }

        $user->save();

        return back()->with('success', 'Profile imagery updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE — Name and other text fields
    | Route: POST /profile/update   name: profile.update (or similar)
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->update($request->only(['name']));

        return back()->with('success', 'Profile details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    | Route: POST /profile/password   name: profile.password (or similar)
    |--------------------------------------------------------------------------
    */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|confirmed|min:8',
        ]);

        $user = Auth::user();

        // Verify current password before allowing change
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is incorrect.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password changed successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | MISC PAGES
    |--------------------------------------------------------------------------
    */
    public function blankpage()
    {
        return view('Pages.blank-page');
    }

    public function calendar()
    {
        return view('Applications.calendar');
    }

    public function inbox()
    {
        return view('Applications.inbox');
    }

    public function accountRequest(Request $request)
    {
        return view('pages.account-request');
    }
}
