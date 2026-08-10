<?php

namespace App\Http\Controllers;

use App\Mail\DemoApprovedMail;
use App\Mail\DemoRequestNotificationMail;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Services\DemoProvisioningService;
use App\Support\AppMailer;
use App\Support\DemoSettings;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\RateLimiter;

class DemoRequestController extends Controller
{
    public function __construct(
        private readonly DemoSettings $demoSettings,
        private readonly DemoProvisioningService $provisioner
    ) {
    }

    /**
     * Show the public "Request a Demo" form.
     */
    public function create()
    {
        abort_unless($this->demoSettings->isEnabled(), 404);

        return view('Landing.demo-request');
    }

    /**
     * Store a new demo request submitted from the public form.
     */
    public function store(Request $request)
    {
        if (! $this->demoSettings->isEnabled()) {
            return back()->withErrors([
                'email' => 'Demo access is currently disabled. Please contact support for a guided walkthrough.',
            ]);
        }

        // Rate limit: 3 requests per IP per hour
        $key = 'demo-request:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Too many attempts. Please wait {$seconds} seconds before trying again."])->withInput();
        }
        RateLimiter::hit($key, 3600);

        $validated = $request->validate([
            'full_name'       => 'required|string|max:100',
            'company_name'    => 'required|string|max:150',
            'business_type'   => 'nullable|string|max:100',
            'email'           => 'required|email|max:150',
            'phone'           => 'required|string|max:30',
            'country'         => 'required|string|max:100',
            'number_of_users' => 'nullable|integer|min:1|max:10000',
            'purpose'         => 'required|string|max:1000',
        ]);

        // A fresh public request should supersede any old pending/active demo
        // for the same email, so the requester always receives current access.
        $existing = DemoRequest::where('email', $validated['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            try {
                $this->provisioner->expireDemo($existing);
            } catch (\Throwable $e) {
                logger()->warning('Previous demo request could not be expired before renewal: ' . $e->getMessage(), [
                    'demo_request_id' => $existing->id,
                    'email' => $validated['email'],
                ]);
            }

            $existing->forceFill([
                'status' => 'expired',
                'admin_note' => trim((string) $existing->admin_note . "\nVoided by a newer public demo request on " . now()->toDateTimeString()),
                'expires_at' => now(),
            ])->save();
        }

        $demoRequest = DemoRequest::create($validated);

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
                'admin_note'      => 'Automatically approved and provisioned after public demo request.',
                'approved_by'     => null,
                'approved_at'     => now(),
                'expires_at'      => $company->demo_expires_at,
                'demo_company_id' => $company->id,
                'demo_user_id'    => $user->id,
            ]);

            $loginUrl = route('login', ['portal' => 1, 'demo' => 1]);

            $credentialEmailSent = $this->sendDemoMail(
                $demoRequest->email,
                new DemoApprovedMail($demoRequest->fresh(), $plainPassword, $loginUrl, $loginEmail),
                'Instant demo credential email failed',
                ['demo_request_id' => $demoRequest->id]
            );

            ActivityLog::record('Demo', 'auto_approved', "Demo request auto-approved for {$demoRequest->email}", [
                'company_id' => $company->id,
                'user_id'    => $user->id,
                'properties' => ['demo_request_id' => $demoRequest->id],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Instant demo provisioning failed: ' . $e->getMessage(), [
                'demo_request_id' => $demoRequest->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'We could not create your instant demo right now. Please try again or contact support.',
            ])->withInput();
        }

        // Notify the super-admin for visibility; no manual approval is required.
        $adminEmail = config('internal.admin_email', config('mail.admin_inbox', 'smartprobookoffice@gmail.com'));
        $this->sendDemoMail(
            $adminEmail,
            new DemoRequestNotificationMail($demoRequest->fresh()),
            'DemoRequest admin notification failed',
            ['demo_request_id' => $demoRequest->id]
        );

        return redirect()->route('demo.request.success')
            ->with('success', $credentialEmailSent
                ? 'Your demo is ready. We have sent the login details to your email.'
                : 'Your demo is ready. Your login details are shown below; email delivery may be delayed.')
            ->with('demo_login_url', $loginUrl)
            ->with('demo_login_email', $loginEmail)
            ->with('demo_plain_password', $plainPassword)
            ->with('demo_email_sent', $credentialEmailSent)
            ->with('demo_expires_at', optional($company->demo_expires_at)->format('D, d M Y H:i'));
    }

    /**
     * Thank-you page shown after a successful submission.
     */
    public function success()
    {
        abort_unless($this->demoSettings->isEnabled(), 404);

        if (!session('success')) {
            return redirect()->route('demo.request.form');
        }
        return view('Landing.demo-request-success');
    }

    private function sendDemoMail(string $recipient, Mailable $mailable, string $failureMessage, array $context = []): bool
    {
        AppMailer::bootCurrentSettings();

        try {
            AppMailer::sendMailable($recipient, $mailable);

            return true;
        } catch (\Throwable $e) {
            logger()->warning($failureMessage . ': ' . $e->getMessage(), $context + [
                'recipient' => $recipient,
            ]);

            return false;
        }
    }
}
