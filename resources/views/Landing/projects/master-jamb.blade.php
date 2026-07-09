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
                <div class="project-detail-actions">
                    <a href="{{ url('/#team') }}" class="project-back-link"><span>&larr;</span> Back to Projects</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.pexels.com/photos/5940831/pexels-photo-5940831.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="Mature students using laptops for CBT practice and online learning" class="img-fluid rounded-4 shadow" onerror="this.onerror=null;this.src='{{ asset('assets/img/user-5.jpg') }}';">
            </div>
        </div>
    </div>
</section>
@endsection
