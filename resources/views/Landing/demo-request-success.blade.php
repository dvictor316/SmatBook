@extends('layout.landing_nav')

@section('title', 'Demo Request Received | SmartProbook')

@section('content')

<section class="contact-header">
    <div class="container text-center">
        <h6 class="section-label">Demo Ready</h6>
        <h1 class="section-title">Your SmartProbook Demo Is <span>Ready Now.</span></h1>
        <p class="section-subtitle">
            Your temporary demo workspace has been created instantly.
            @if(session('demo_email_sent', true))
                The login details have also been sent to your email.
            @else
                Your login details are available below while email delivery is retried.
            @endif
        </p>
    </div>
</section>

<section class="contact-section">
    <div class="container" style="max-width:680px;">
        <div class="card shadow-sm border-0" style="border-radius:12px;padding:2.5rem 2rem;text-align:center;">

            <div style="font-size:4rem;margin-bottom:1rem;">&#10003;</div>

            <h3 style="font-weight:700;color:#1a1a2e;margin-bottom:0.75rem;">Demo Created Successfully!</h3>

            <p style="color:#555;font-size:1.05rem;margin-bottom:1.5rem;">
                Your request has been automatically approved.
                @if(session('demo_email_sent', true))
                    Check your inbox for the same login details, or use the temporary credentials below to enter your demo immediately.
                @else
                    Use the temporary credentials below to enter your demo immediately.
                @endif
            </p>

            @if(session('demo_login_email') && session('demo_plain_password'))
                <div class="alert alert-success text-left" style="border-radius:8px;">
                    <strong>Your instant demo access</strong>
                    <div class="mt-2">Login email: <strong>{{ session('demo_login_email') }}</strong></div>
                    <div>Password: <strong>{{ session('demo_plain_password') }}</strong></div>
                    @if(session('demo_expires_at'))
                        <div>Access expires: <strong>{{ session('demo_expires_at') }}</strong></div>
                    @endif
                </div>
            @endif

            <div class="alert alert-info text-left" style="border-radius:8px;">
                <strong>What happens next?</strong>
                <ul class="mt-2 mb-0">
                    <li>Your demo account has already been created.</li>
                    <li>You can log in now using the button below.</li>
                    <li>Your demo environment will be active for <strong>48 hours</strong>.</li>
                    <li>No payment or credit card is required.</li>
                </ul>
            </div>

            <div class="mt-4">
                <a href="{{ session('demo_login_url', route('login', ['portal' => 1, 'demo' => 1])) }}" class="btn btn-primary" style="padding:0.7rem 2rem;font-weight:600;">
                    <i class="fas fa-sign-in-alt mr-2"></i> Open Demo
                </a>
                <a href="{{ url('/contact') }}" class="btn btn-outline-secondary ml-2" style="padding:0.7rem 2rem;font-weight:600;">
                    <i class="fas fa-envelope mr-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
