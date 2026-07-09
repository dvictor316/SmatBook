@extends('layout.landing_nav')

@section('content')
<section style="margin-top: 85px; padding: 90px 20px; background: linear-gradient(135deg,#f8fafc 0%,#eaf2ff 100%);">
    <div class="container" style="max-width: 1200px;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h6 style="letter-spacing: 3px; color: #e11d48; font-weight: 800; text-transform: uppercase;">Other Projects</h6>
                <h1 style="font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; color: #0f172a; line-height: 1.1;">Property234<span style="color:#2563eb;">.com</span></h1>
                <p style="color:#475569; font-size:1.05rem; line-height:1.9; margin-top:16px;">
                    Property234.com is a global real estate listing and workflow platform built for everyone in the real estate value chain.
                    It connects property owners, surveyors, legal advisers, agents, buyers, tenants, and institutional investors in one trusted marketplace.
                </p>
                <p style="color:#475569; font-size:1.02rem; line-height:1.9;">
                    From verified property discovery to document-driven transactions, Property234.com streamlines listing quality, due diligence,
                    stakeholder collaboration, and deal completion across local and cross-border markets.
                </p>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
                    <a href="{{ route('landing.contact') }}" class="btn btn-primary px-4 py-2">Request a Demo</a>
                    <a href="{{ url('/#team') }}" class="btn btn-outline-dark px-4 py-2">Back to Projects</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1200&auto=format&fit=crop" alt="Beautiful modern architectural property exterior for Property234.com" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>
</section>
@endsection
