@extends('layout.mainlayout')

@php
    $hideSidebar = true;
    $hideHeader = true;
    /* Attempt to capture subdomain from route or host */
    $currentSubdomain = request()->route('subdomain') ?? explode('.', request()->getHost())[0];
    $supportChatUrl = Route::has('messages.index') ? route('messages.index', ['type' => 'chat']) : '#';
    $supportEmailUrl = Route::has('messages.index') ? route('messages.index', ['type' => 'email']) : '#';
@endphp

@section('content')
<style>
    .sidebar, .header, .footer {
        display: none !important; 
    }
    .page-wrapper {
        margin-left: 0 !important; 
        padding-top: 0 !important;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 25%),
            radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.08), transparent 25%),
            linear-gradient(180deg, #f7f9ff 0%, #eef4ff 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .manager-pending-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
    }
    .manager-pending-card {
        border: 1px solid rgba(37, 99, 235, 0.10);
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 28px 65px rgba(15, 23, 42, 0.10);
        background: #fff;
    }
    .manager-pending-brand {
        margin-bottom: 1.5rem;
    }
    .manager-pending-head {
        padding: 28px 30px 10px;
    }
    .manager-pending-body {
        padding: 0 30px 30px;
    }
    .manager-pending-icon {
        width: 92px;
        height: 92px;
        border-radius: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(124,58,237,.14));
        color: #4f46e5;
        font-size: 2.4rem;
        box-shadow: inset 0 0 0 1px rgba(79,70,229,.08);
    }
    .manager-pending-steps {
        background: #f8fbff;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        padding: 20px 18px;
    }
    .badge-success-border {
        border: 1px solid #22c55e;
        color: #16a34a;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0fdf4;
    }
    .badge-progress-border {
        border: 1px solid #4f46e5;
        color: #4f46e5;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
    }
    .bg-soft-info {
        background: linear-gradient(135deg, rgba(37,99,235,.09), rgba(124,58,237,.08));
        color: #1e3a8a;
        border-radius: 18px;
    }
    .manager-pending-btn {
        border-radius: 14px;
        padding: 0.8rem 1.1rem;
        font-weight: 700;
    }

    @media print {
        .btn, .mt-5, .text-center small { display: none !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .page-wrapper { background: #fff !important; }
    }
</style>

<div class="manager-pending-shell">
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="text-center manager-pending-brand">
                    <x-auth-brand-lockup :logo="asset('assets/img/logos.png')" size="lg" :tagline="'Deployment Hub'" />
                </div>

                <div class="manager-pending-card">
                    <div class="manager-pending-head text-center">
                        <div class="manager-pending-icon mx-auto mb-3">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <div class="manager-pending-body">
                        <div class="text-center mb-4">
                            <h3 class="font-weight-bold">Approval Pending</h3>
                            <p class="text-muted">
                                Hello <strong>{{ auth()->user()->name }}</strong>, your Deployment Manager access for 
                                <strong>{{ env('SESSION_DOMAIN', 'SmartProbook') }}</strong> has been received and is waiting for super admin approval.
                            </p>
                        </div>

                        <div class="manager-pending-steps mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge badge-success-border rounded-circle mr-3"><i class="fas fa-check"></i></div>
                                <div class="text-success font-weight-bold">Account Created</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge badge-progress-border rounded-circle mr-3"><i class="fas fa-user-shield"></i></div>
                                <div class="text-primary font-weight-bold">Awaiting Super Admin Review</div>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <div class="badge badge-light rounded-circle mr-3"><i class="fas fa-lock"></i></div>
                                <div>Dashboard Access Granted</div>
                            </div>
                        </div>

                        <div class="alert alert-custom bg-soft-info border-0 p-3">
                            <small class="d-block">
                                <i class="fas fa-info-circle mr-1"></i>
                                You will be notified at <strong>{{ auth()->user()->email }}</strong> as soon as a super admin approves this partner profile. Dashboard access remains locked until that approval is completed.
                            </small>
                        </div>

                        <div class="mt-4 d-flex justify-content-center gap-2">

                            <button onclick="printPage()" class="btn btn-outline-secondary manager-pending-btn">
                                <i class="fas fa-print"></i> Print Status
                            </button>

                            <a href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                               class="btn btn-danger manager-pending-btn">
                                <i class="fas fa-power-off"></i> Logout
                            </a>
                        </div>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>

                        <div class="mt-5 text-center pt-3 border-top">
                            <span class="text-muted small">Need to speak with us?</span><br>

                            <a href="{{ $supportChatUrl }}" class="btn btn-sm btn-link font-weight-bold">Live Chat</a>
                            <span class="text-muted">|</span>
                            <a href="{{ $supportEmailUrl }}" class="btn btn-sm btn-link font-weight-bold">Email Support</a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <small class="text-muted">&copy; 2026 {{ env('SESSION_DOMAIN') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function printPage() {
        window.print();
    }
</script>
@endsection
