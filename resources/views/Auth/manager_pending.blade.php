@extends('layout.mainlayout')

{{-- Use these variables to tell your mainlayout to hide the bars --}}
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
    /* CSS Force-Hide in case the layout variables aren't wired up yet */
    .sidebar, .header, .footer { 
        display: none !important; 
    }
    .page-wrapper { 
        margin-left: 0 !important; 
        padding-top: 0 !important;
        background-color: #f4f7f6;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .badge-success-border { border: 1px solid #28a745; color: #28a745; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
    .bg-soft-info { background-color: #e7f3ff; color: #007bff; }
    
    @media print {
        .btn, .mt-5, .text-center small { display: none !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .page-wrapper { background: #fff !important; }
    }
</style>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f7f6;">
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="text-center mb-4">
                    <x-auth-brand-lockup :logo="asset('assets/img/logos.png')" size="lg" :tagline="'Deployment Hub'" />
                </div>

                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="display-1 text-warning mb-3">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="font-weight-bold">Verification Pending</h3>
                            <p class="text-muted">
                                Hello <strong>{{ auth()->user()->name }}</strong>, your Deployment Manager access for 
                                <strong>{{ env('SESSION_DOMAIN', 'SmartProbook') }}</strong> is currently being vetted.
                            </p>
                        </div>

                        <div class="verification-steps mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge badge-success-border rounded-circle mr-3"><i class="fas fa-check"></i></div>
                                <div class="text-success font-weight-bold">Account Created</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge badge-primary rounded-circle mr-3"><i class="fas fa-sync fa-spin"></i></div>
                                <div class="text-primary font-weight-bold">Automated Forensic Review In Progress</div>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <div class="badge badge-light rounded-circle mr-3"><i class="fas fa-lock"></i></div>
                                <div>Dashboard Access Granted</div>
                            </div>
                        </div>

                        <div class="alert alert-custom bg-soft-info border-0 p-3">
                            <small class="d-block">
                                <i class="fas fa-info-circle mr-1"></i>
                                You will be notified at <strong>{{ auth()->user()->email }}</strong> as soon as automated checks are completed. Manual admin review is only triggered when a profile is flagged.
                            </small>
                        </div>

                        <div class="mt-4 d-flex justify-content-center gap-2">
                            {{-- [2025-12-30] Mandatory Print Script Trigger --}}
                            <button onclick="printPage()" class="btn btn-outline-secondary btn-rounded">
                                <i class="fas fa-print"></i> Print Status
                            </button>

                            <a href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                               class="btn btn-danger btn-rounded">
                                <i class="fas fa-power-off"></i> Logout
                            </a>
                        </div>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>

                        <div class="mt-5 text-center pt-3 border-top">
                            <span class="text-muted small">Need to speak with us?</span><br>
                            {{-- [2026-01-17] Support Links - Corrected with Subdomain parameter --}}
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
</div>

<script type="text/javascript">
    function printPage() {
        window.print();
    }
</script>
@endsection
