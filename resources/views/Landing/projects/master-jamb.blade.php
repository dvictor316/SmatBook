@extends('layout.landing_nav')

@section('content')
<section style="margin-top: 85px; padding: 90px 20px; background: linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);">
    <div class="container" style="max-width: 1200px;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h6 style="letter-spacing: 3px; color: #e11d48; font-weight: 800; text-transform: uppercase;">Other Projects</h6>
                <h1 style="font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; color: #0f172a; line-height: 1.1;">Master <span style="color:#2563eb;">JAMB</span></h1>
                <p style="color:#475569; font-size:1.05rem; line-height:1.9; margin-top:16px;">
                    Master JAMB is an online CBT platform designed for schools, colleges, tutorial centers, and academic programs.
                    It provides an exam-ready digital environment where students can prepare, practice, and sit timed assessments confidently.
                </p>
                <p style="color:#475569; font-size:1.02rem; line-height:1.9;">
                    The platform supports question banks, exam schedules, automated grading, detailed performance analytics,
                    and transparent result tracking for administrators, teachers, and students.
                </p>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
                    <a href="{{ route('landing.contact') }}" class="btn btn-primary px-4 py-2">Request a Demo</a>
                    <a href="{{ url('/#team') }}" class="btn btn-outline-dark px-4 py-2">Back to Projects</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop" alt="Master JAMB CBT" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>
</section>
@endsection
