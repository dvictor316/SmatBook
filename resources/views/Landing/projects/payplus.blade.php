@extends('layout.landing_nav')

@section('content')
<style>
    .project-detail-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 28px;
    }
    .project-back-link {
        align-items: center;
        background: linear-gradient(135deg, #0f172a, #123d79);
        border: 1px solid rgba(37,99,235,0.28);
        border-radius: 999px;
        box-shadow: 0 18px 44px rgba(15,23,42,0.18);
        color: #fff;
        display: inline-flex;
        font-weight: 800;
        gap: 10px;
        letter-spacing: 0.4px;
        padding: 13px 24px;
        text-decoration: none;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .project-back-link:hover {
        color: #fff;
        box-shadow: 0 22px 54px rgba(15,23,42,0.24);
        transform: translateY(-2px);
    }
    .project-back-link span {
        color: #bfdbfe;
        font-size: 1rem;
    }
</style>
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
                <div class="project-detail-actions">
                    <a href="{{ url('/#team') }}" class="project-back-link"><span>&larr;</span> Back to Projects</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.pexels.com/photos/6863183/pexels-photo-6863183.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="African finance professional managing digital payments on a laptop" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>
</section>
@endsection
