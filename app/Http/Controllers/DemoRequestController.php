<?php

namespace App\Http\Controllers;

use App\Mail\DemoRequestNotificationMail;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class DemoRequestController extends Controller
{
    /**
     * Show the public "Request a Demo" form.
     */
    public function create()
    {
        return view('Landing.demo-request');
    }

    /**
     * Store a new demo request submitted from the public form.
     */
    public function store(Request $request)
    {
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

        // Prevent duplicate pending requests from same email
        $existing = DemoRequest::where('email', $validated['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'email' => 'A demo request with this email is already pending or active. Please check your inbox or contact support.',
            ])->withInput();
        }

        $demoRequest = DemoRequest::create($validated);

        // Notify the super-admin
        $adminEmail = config('internal.admin_email', 'support@smartprobook.com');
        try {
            Mail::to($adminEmail)->queue(new DemoRequestNotificationMail($demoRequest));
        } catch (\Throwable $e) {
            // Non-fatal: log but don't block the user
            logger()->warning('DemoRequest admin notification failed: ' . $e->getMessage());
        }

        return redirect()->route('demo.request.success')
            ->with('success', 'Your demo request has been received. Our team will review and contact you shortly.');
    }

    /**
     * Thank-you page shown after a successful submission.
     */
    public function success()
    {
        if (!session('success')) {
            return redirect()->route('demo.request.form');
        }
        return view('Landing.demo-request-success');
    }
}
