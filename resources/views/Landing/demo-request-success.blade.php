@extends('layout.landing_nav')

@section('title', 'Demo Request Received | SmartProbook')

@section('content')

<section class="contact-header">
    <div class="container text-center">
        <h6 class="section-label">Request Received</h6>
        <h1 class="section-title">Thank You! We'll Be In <span>Touch Soon.</span></h1>
        <p class="section-subtitle">Your demo request has been submitted. Our team will review it and send your credentials by email within 24 hours.</p>
    </div>
</section>

<section class="contact-section">
    <div class="container" style="max-width:680px;">
        <div class="card shadow-sm border-0" style="border-radius:12px;padding:2.5rem 2rem;text-align:center;">

            <div style="font-size:4rem;margin-bottom:1rem;">&#10003;</div>

            <h3 style="font-weight:700;color:#1a1a2e;margin-bottom:0.75rem;">Request Submitted Successfully!</h3>

            <p style="color:#555;font-size:1.05rem;margin-bottom:1.5rem;">
                We've received your request and will review it shortly.
                Once approved, you'll receive an email with your login credentials and a direct link to your personal demo environment.
            </p>

            <div class="alert alert-info text-left" style="border-radius:8px;">
                <strong>What happens next?</strong>
                <ul class="mt-2 mb-0">
                    <li>Our team reviews your request (usually within 24 hours).</li>
                    <li>If approved, you'll get an email with your credentials.</li>
                    <li>Your demo environment will be active for <strong>48 hours</strong>.</li>
                    <li>No payment or credit card is required.</li>
                </ul>
            </div>

            <div class="mt-4">
                <a href="{{ url('/') }}" class="btn btn-primary" style="padding:0.7rem 2rem;font-weight:600;">
                    <i class="fas fa-home mr-2"></i> Back to Home
                </a>
                <a href="{{ url('/contact') }}" class="btn btn-outline-secondary ml-2" style="padding:0.7rem 2rem;font-weight:600;">
                    <i class="fas fa-envelope mr-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
