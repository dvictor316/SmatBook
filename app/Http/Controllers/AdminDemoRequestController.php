<?php

namespace App\Http\Controllers;

use App\Mail\DemoApprovedMail;
use App\Mail\DemoRejectedMail;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Services\DemoProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminDemoRequestController extends Controller
{
    public function __construct(private DemoProvisioningService $provisioner)
    {
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

        return view('SuperAdmin.demo-requests.index', compact('demoRequests', 'status', 'counts'));
    }

    /**
     * Show the detail page for one demo request.
     */
    public function show(int $id)
    {
        $demoRequest = DemoRequest::with(['approver', 'demoCompany', 'demoUser'])->findOrFail($id);
        return view('SuperAdmin.demo-requests.show', compact('demoRequest'));
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

            $loginUrl = config('app.url') . '/login';

            Mail::to($demoRequest->email)
                ->queue(new DemoApprovedMail($demoRequest, $plainPassword, $loginUrl));

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
}
