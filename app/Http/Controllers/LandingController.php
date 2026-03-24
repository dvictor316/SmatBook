<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\LandingSetting;
use App\Models\Company;
use App\Models\Plan; // Added Plan model
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function index()
    {
        $host = Str::lower((string) request()->getHost());
        $mainDomain = trim((string) config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), ". \t\n\r\0\x0B");
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $centralHosts = collect([
            $mainDomain,
            'www.' . $mainDomain,
            'localhost',
            '127.0.0.1',
            $appUrlHost,
            $appUrlHost ? preg_replace('/^www\./i', '', $appUrlHost) : null,
            $appUrlHost ? 'www.' . preg_replace('/^www\./i', '', $appUrlHost) : null,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => Str::lower((string) $value))
            ->unique()
            ->values()
            ->all();

        if (!in_array($host, $centralHosts, true)) {
            $subdomain = explode('.', $host)[0] ?? null;

            if ($subdomain) {
                $company = Company::query()
                    ->where('domain_prefix', $subdomain)
                    ->orWhere('subdomain', $subdomain)
                    ->first();

                if ($company) {
                    session([
                        'current_tenant_id' => $company->id,
                        'current_tenant_name' => $company->domain_prefix ?: $company->subdomain,
                    ]);

                    return Auth::check()
                        ? redirect('/dashboard')
                        : redirect()->route('login');
                }
            }
        }

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

    public function demo(Request $request)
    {
        $demoEmail = 'demo@smartprobook.local';
        $demoCompanyName = 'SmartProbook Demo Company';
        $demoPrefix = 'demo-hq';

        try {
            $user = DB::transaction(function () use ($demoEmail, $demoCompanyName, $demoPrefix) {
                $user = User::withTrashed()->firstOrNew(['email' => $demoEmail]);

                if (method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }

                $user->fill([
                    'name' => 'SmartProbook Demo',
                    'password' => Hash::make('DemoAccess2026'),
                    'role' => 'admin',
                    'status' => 'active',
                    'is_verified' => 1,
                    'email_verified_at' => now(),
                    'verified_at' => now(),
                    'phone' => '+2348000000000',
                ]);
                $user->save();

                $company = Company::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'owner_id' => $user->id,
                        'name' => $demoCompanyName,
                        'company_name' => $demoCompanyName,
                        'email' => $demoEmail,
                        'phone' => '+2348000000000',
                        'address' => 'Demo HQ, Lagos',
                        'status' => 'active',
                        'country' => 'Nigeria',
                        'currency_code' => 'NGN',
                        'currency_symbol' => '₦',
                        'subdomain' => $demoPrefix,
                        'domain_prefix' => $demoPrefix,
                        'domain' => $demoPrefix,
                        'plan' => 'Professional',
                        'industry' => 'Technology',
                        'subscription_start' => now()->subDays(7),
                        'subscription_end' => now()->addDays(30),
                    ]
                );

                if ((int) $user->company_id !== (int) $company->id) {
                    $user->company_id = $company->id;
                    $user->save();
                }

                Subscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_id' => $company->id,
                        'plan_id' => Plan::query()->whereRaw('LOWER(name) like ?', ['%professional%'])->value('id'),
                        'plan' => 'Professional',
                        'plan_name' => 'Professional',
                        'subscriber_name' => $demoCompanyName,
                        'domain_prefix' => $demoPrefix,
                        'employee_size' => '25-50',
                        'amount' => 19500,
                        'billing_cycle' => 'Monthly',
                        'start_date' => now()->subDays(7),
                        'end_date' => now()->addDays(30),
                        'status' => 'trial',
                        'payment_status' => 'paid',
                        'payment_gateway' => 'demo',
                        'payment_reference' => 'demo-workspace',
                        'transaction_reference' => 'demo-workspace',
                        'activated_at' => now()->subDays(7),
                        'initialized_at' => now()->subDays(7),
                        'paid_at' => now()->subDays(7),
                        'payment_date' => now()->subDays(7),
                    ]
                );

                return $user->fresh();
            });

            Auth::logout();
            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->put('user_plan', 'professional');

            return redirect()->route('user.dashboard')
                ->with('success', 'Demo workspace is ready. Explore the app freely.');
        } catch (\Throwable $e) {
            Log::error('Demo launch failed', ['error' => $e->getMessage()]);

            return redirect()->route('landing.contact')
                ->with('error', 'The live demo could not be launched right now. Please try again shortly.');
        }
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
            $settings = null;
            if (Schema::hasTable('landing_settings')) {
                $settings = LandingSetting::first();
            }

            $recipients = array_values(array_filter(array_unique([
                $settings?->contact_email,
                env('MAIL_ADMIN_INBOX'),
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

            $preferredMailer = strtolower((string) config('mail.default'));
            $smtpReady = trim((string) config('mail.mailers.smtp.host')) !== ''
                && trim((string) config('mail.mailers.smtp.username')) !== ''
                && trim((string) config('mail.mailers.smtp.password')) !== '';
            $deliveryMailer = ($preferredMailer === 'log' && $smtpReady) ? 'smtp' : $preferredMailer;

            Mail::mailer($deliveryMailer)->raw($body, function ($message) use ($recipients, $validated, $subject) {
                $message->to($recipients)->subject($subject);
                $message->replyTo($validated['email'], $validated['fullname']);
            });

            if ($deliveryMailer === 'log') {
                return back()->with('success', 'Request received. Mailer is in LOG mode, so email was captured in logs. Set MAIL_MAILER=smtp with valid credentials for inbox delivery.');
            }

            return back()->with('success', 'Message sent successfully. Our team will reach out shortly.');
        } catch (\Throwable $e) {
            Log::error('Landing contact submission failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            return back()->with('error', 'Request captured, but email delivery failed. Check MAIL settings and try again.')->withInput();
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
