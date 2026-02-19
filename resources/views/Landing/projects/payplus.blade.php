@extends('layout.landing_nav')

@section('content')
<section style="margin-top: 85px; padding: 90px 20px; background: linear-gradient(135deg,#f8fafc 0%,#eaf2ff 100%);">
    <div class="container" style="max-width: 1200px;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h6 style="letter-spacing: 3px; color: #e11d48; font-weight: 800; text-transform: uppercase;">Other Projects</h6>
                <h1 style="font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; color: #0f172a; line-height: 1.1;">Pay<span style="color:#2563eb;">Plus</span></h1>
                <p style="color:#475569; font-size:1.05rem; line-height:1.9; margin-top:16px;">
                    PayPlus is a global payment gateway engineered to power secure transactions for businesses of all sizes.
                    It is designed for collections, payouts, billing, subscriptions, and omni-channel commerce at scale.
                </p>
                <p style="color:#475569; font-size:1.02rem; line-height:1.9;">
                    With merchant APIs, monitoring dashboards, fraud controls, and smart settlement workflows,
                    PayPlus provides a reliable financial rail for local and international transaction processing.
                </p>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
                    <a href="{{ route('landing.contact') }}" class="btn btn-primary px-4 py-2">Request a Demo</a>
                    <a href="{{ url('/#team') }}" class="btn btn-outline-dark px-4 py-2">Back to Projects</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.pexels.com/photos/4968634/pexels-photo-4968634.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="PayPlus Payment Gateway" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>
</section>
@endsection

