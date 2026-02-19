<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\LandingSetting;
use App\Models\Plan; // Added Plan model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller
{
    public function index()
    {
        $totalInvoices = Sale::count() ?? 0;

        try {
            $settings = LandingSetting::first();
        } catch (\Exception $e) {
            $settings = null;
        }

        return view('Landing.index', compact('totalInvoices', 'settings'));
    }


    public function about()
    {
        return view('Landing.about');
    }

    public function contact()
    {
        return view('Landing.contact');
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'department' => 'nullable|string|max:191',
            'message' => 'required|string|max:5000',
            'company_name' => 'nullable|string|max:191',
        ]);

        try {
            $settings = LandingSetting::first();
            $recipients = array_values(array_filter(array_unique([
                $settings?->contact_email,
                config('mail.from.address'),
                'donvictorlive@gmail.com',
            ]), fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)));

            if (empty($recipients)) {
                return back()->with('error', 'No contact email is configured right now.')->withInput();
            }

            $subject = 'New Landing Contact: ' . ($validated['department'] ?? 'General Inquiry');
            $body = implode("\n", [
                'Name: ' . $validated['fullname'],
                'Email: ' . $validated['email'],
                'Company: ' . ($validated['company_name'] ?? 'N/A'),
                'Department: ' . ($validated['department'] ?? 'General'),
                '',
                'Message:',
                $validated['message'],
            ]);

            Mail::raw($body, function ($message) use ($recipients, $validated, $subject) {
                $message->to($recipients)->subject($subject);
                $message->replyTo($validated['email'], $validated['fullname']);
            });

            return back()->with('success', 'Message sent successfully. Our team will reach out shortly.');
        } catch (\Throwable $e) {
            Log::error('Landing contact submission failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            return back()->with('error', 'We could not send your message at the moment. Please try again.')->withInput();
        }
    }

    public function team()
    {
        return view('Landing.team');
    }

    public function policy()
    {
        return view('Landing.policy');
    }

    public function projectLahome()
    {
        return view('Landing.projects.lahome');
    }

    public function projectMasterJamb()
    {
        return view('Landing.projects.master-jamb');
    }

    public function projectPayplus()
    {
        return view('Landing.projects.payplus');
    }
}
