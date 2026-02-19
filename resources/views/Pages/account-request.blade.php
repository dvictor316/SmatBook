@extends('layout.mainlayout')

@section('content')
<div id="main-content-wrapper" class="transition-all" style="margin-left: 250px; min-height: 100vh; background-color: #f4f7f6;">
    <div class="container-fluid py-4 px-md-5">
        
        <button id="sidebar-toggle" class="btn btn-white shadow-sm mb-4 border" onclick="toggleSidebar()">
            <i class="fas fa-bars text-primary"></i>
        </button>

        <div class="row justify-content-center align-items-center" style="min-height: 75vh;">
            <div class="col-12 col-xl-10">
                <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
                    <div class="progress" style="height: 4px;">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>

                    <div class="card-body p-0">
                        <div class="row no-gutters">
                            <div class="col-lg-5 bg-white p-5 d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <div class="status-icon-wrap mb-3">
                                        <i class="fas fa-sync fa-spin fa-3x text-primary"></i>
                                    </div>
                                    <h2 class="h4 font-weight-bold text-dark">Syncing Account</h2>
                                    <p class="text-muted small px-lg-4">
                                        We are validating your subscription for <strong>{{ env('SESSION_DOMAIN', 'null') }}</strong>.
                                    </p>
                                </div>

                                <div class="refresh-circle mx-auto d-flex align-items-center justify-content-center mb-3 shadow-sm border">
                                    <div class="text-center">
                                        <h1 id="countdown" class="display-4 font-weight-bold text-primary mb-0">30</h1>
                                        <span class="text-uppercase small font-weight-bold text-muted" style="letter-spacing: 1px;">Secs</span>
                                    </div>
                                </div>
                                <p class="small text-secondary italic">Auto-refreshing page...</p>
                            </div>

                            <div class="col-lg-7 p-5" style="background-color: #fbfcfe;">
                                <div class="mb-5">
                                    <h4 class="font-weight-bold text-dark mb-1">Support & Assistance</h4>
                                    <p class="text-muted">Need help? Connect with our team through the channels below.</p>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <a href="{{ url('/messages.chat') }}" class="btn btn-white border shadow-sm btn-lg btn-block py-3 text-left transition-hover">
                                            <i class="fas fa-comments text-info mr-2"></i>
                                            <span class="d-block font-weight-bold small text-dark">Chat & Message</span>
                                            <span class="text-muted extra-small">Internal system</span>
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="mailto:support@{{ env('SESSION_DOMAIN', 'null') }}?subject=Account Status ID: {{ Auth::user()->id ?? 'N/A' }}" 
                                           class="btn btn-white border shadow-sm btn-lg btn-block py-3 text-left transition-hover">
                                            <i class="fas fa-envelope-open text-primary mr-2"></i>
                                            <span class="d-block font-weight-bold small text-dark">Email Support</span>
                                            <span class="text-muted extra-small">24/7 Response</span>
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <button onclick="printPage()" class="btn btn-outline-secondary btn-block py-2 border-dashed">
                                            <i class="fas fa-print mr-2"></i> Print Status Information
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-5 pt-4 border-top">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="small">
                                            <span class="text-muted">User ID:</span> 
                                            <span class="badge badge-light border text-dark">{{ Auth::user()->id ?? 'Guest' }}</span>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-globe mr-1"></i> {{ env('SESSION_DOMAIN', 'null') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Responsive Sidebar Handling */
    #main-content-wrapper {
        transition: all 0.3s ease-in-out;
    }
    
    .sidebar-collapsed {
        margin-left: 0 !important;
    }

    /* Modern Elements */
    .refresh-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: white;
    }
    .transition-hover:hover {
        transform: translateY(-3px);
        background-color: #ffffff;
        border-color: #0d6efd !important;
    }
    .extra-small { font-size: 0.75rem; }
    .border-dashed { border-style: dashed !important; }
    .rounded-lg { border-radius: 1.25rem !important; }

    /* Mobile Adaptations */
    @media (max-width: 992px) {
        #main-content-wrapper { margin-left: 0 !important; }
        .col-lg-5 { border-bottom: 1px solid #eee; }
    }

    @media print {
        #main-content-wrapper { margin-left: 0 !important; }
        #sidebar-toggle, .btn, .refresh-circle, .progress { display: none !important; }
    }
</style>

<script>
    /**
     * Sidebar Toggle Logic
     */
    function toggleSidebar() {
        const wrapper = document.getElementById('main-content-wrapper');
        wrapper.classList.toggle('sidebar-collapsed');
    }

    /**
     * Printing Script - Requested: [2025-12-30]
     */
    function printPage() {
        window.print();
    }

    /**
     * Countdown & UI Refresh
     */
    let secondsLeft = 30;
    const countdownElement = document.getElementById('countdown');
    const progressBar = document.getElementById('progress-bar');

    const timer = setInterval(() => {
        secondsLeft--;
        if (countdownElement) countdownElement.innerText = secondsLeft;
        
        // Update progress bar percentage
        if (progressBar) {
            let percentage = (secondsLeft / 30) * 100;
            progressBar.style.width = percentage + '%';
        }

        if (secondsLeft <= 0) {
            clearInterval(timer);
            window.location.reload();
        }
    }, 1000);
</script>
@endsection