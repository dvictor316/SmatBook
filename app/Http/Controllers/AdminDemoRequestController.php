<?php

namespace App\Http\Controllers;

use App\Mail\DemoApprovedMail;
use App\Mail\DemoRejectedMail;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Services\DemoProvisioningService;
use App\Support\AppMailer;
use App\Support\DemoSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminDemoRequestController extends Controller
{
    public function __construct(
        private DemoProvisioningService $provisioner,
        private DemoSettings $demoSettings
    ) {
    }

    /**
     * List all demo requests, filterable by status.
     */
    public function index(Request $request)
    {
        $status   = $request->get('status', 'pending');
        $query    = DemoRequest::with('approver')
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $demoRequests = $query->paginate(20)->withQueryString();
        $counts = [
            'pending'  => DemoRequest::pending()->count(),
            'approved' => DemoRequest::approved()->count(),
            'rejected' => DemoRequest::rejected()->count(),
            'expired'  => DemoRequest::expired()->count(),
        ];

        $demoConfig = $this->demoSettings->asArray();

        return view('SuperAdmin.demo-requests.index', compact('demoRequests', 'status', 'counts', 'demoConfig'));
    }

    /**
     * Show the detail page for one demo request.
     */
    public function show(int $id)
    {
        $demoRequest = DemoRequest::with(['approver', 'demoCompany', 'demoUser'])->findOrFail($id);
        $demoConfig = $this->demoSettings->asArray();

        return view('SuperAdmin.demo-requests.show', compact('demoRequest', 'demoConfig'));
    }

    /**
     * Approve a demo request: provision demo tenant & user, send approval email.
     */
    public function approve(Request $request, int $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);

        if (!$demoRequest->isPending()) {
            return back()->withErrors(['error' => 'This request has already been ' . $demoRequest->status . '.']);
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->provisioner->provision($demoRequest);

            /** @var \App\Models\Company $company */
            $company = $result['company'];
            /** @var \App\Models\User $user */
            $user = $result['user'];
            $loginEmail = $result['login_email'] ?? $user->email;
            $plainPassword = $result['plain_password'];

            $demoRequest->update([
                'status'          => 'approved',
                'admin_note'      => $request->input('admin_note'),
                'approved_by'     => Auth::id(),
                'approved_at'     => now(),
                'expires_at'      => $company->demo_expires_at,
                'demo_company_id' => $company->id,
                'demo_user_id'    => $user->id,
            ]);

            $loginUrl = route('login', ['portal' => 1, 'demo' => 1]);

            $this->sendDemoApprovalMail($demoRequest, $plainPassword, $loginUrl, $loginEmail);

            ActivityLog::record('Demo', 'approved', "Demo request approved for {$demoRequest->email}", [
                'user_id'    => Auth::id(),
                'properties' => ['demo_request_id' => $demoRequest->id],
            ]);

            return redirect()->route('super_admin.demo_requests.index')
                ->with('success', "Demo approved and credentials sent to {$demoRequest->email}.");
        } catch (\Throwable $e) {
            logger()->error('Demo provisioning failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Provisioning failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a demo request and notify the applicant.
     */
    public function reject(Request $request, int $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);

        if (!$demoRequest->isPending()) {
            return back()->withErrors(['error' => 'This request has already been ' . $demoRequest->status . '.']);
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $demoRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $request->input('admin_note'),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Mail::to($demoRequest->email)
            ->queue(new DemoRejectedMail($demoRequest));

        ActivityLog::record('Demo', 'rejected', "Demo request rejected for {$demoRequest->email}", [
            'user_id'    => Auth::id(),
            'properties' => ['demo_request_id' => $demoRequest->id],
        ]);

        return redirect()->route('super_admin.demo_requests.index')
            ->with('success', "Demo request rejected. Notification sent to {$demoRequest->email}.");
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'auto_reset_on_session_start' => 'nullable|boolean',
            'lifetime_hours' => 'required|integer|min:1|max:720',
            'blocked_route_prefixes' => 'nullable|string|max:2000',
            'blocked_routes' => 'nullable|string|max:2000',
        ]);

        $this->demoSettings->update([
            'enabled' => $request->boolean('enabled'),
            'auto_reset_on_session_start' => $request->boolean('auto_reset_on_session_start'),
            'lifetime_hours' => (int) $validated['lifetime_hours'],
            'blocked_route_prefixes' => $validated['blocked_route_prefixes'] ?? '',
            'blocked_routes' => $validated['blocked_routes'] ?? '',
        ]);

        return redirect()->route('super_admin.demo_requests.index')
            ->with('success', 'Demo mode settings updated.');
    }

    public function reset(int $id)
    {
        $demoRequest = DemoRequest::with('demoCompany')->findOrFail($id);
        $company = $demoRequest->demoCompany;

        if (! $company || ! $company->isDemo()) {
            return back()->withErrors(['error' => 'No provisioned demo workspace was found for this request.']);
        }

        $this->provisioner->resetDemoWorkspace($company, Auth::user());

        return redirect()->route('super_admin.demo_requests.show', $demoRequest->id)
            ->with('success', 'Demo workspace reset and reseeded successfully.');
    }

    public function extend(Request $request, int $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);
        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:720',
        ]);

        $this->provisioner->extendDemo($demoRequest, (int) $validated['hours']);

        ActivityLog::record('Demo', 'extended', "Demo request extended for {$demoRequest->email}", [
            'user_id' => Auth::id(),
            'company_id' => $demoRequest->demo_company_id,
            'properties' => ['hours' => (int) $validated['hours']],
        ]);

        return redirect()->route('super_admin.demo_requests.show', $demoRequest->id)
            ->with('success', 'Demo access extended successfully.');
    }

    public function expire(int $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);

        $this->provisioner->expireDemo($demoRequest);

        return redirect()->route('super_admin.demo_requests.show', $demoRequest->id)
            ->with('success', 'Demo access expired successfully.');
    }

    private function sendDemoApprovalMail(DemoRequest $demoRequest, string $plainPassword, string $loginUrl, string $loginEmail): void
    {
        AppMailer::bootCurrentSettings();
        AppMailer::sendMailable(
            $demoRequest->email,
            new DemoApprovedMail($demoRequest->fresh(), $plainPassword, $loginUrl, $loginEmail)
        );
    }
}
