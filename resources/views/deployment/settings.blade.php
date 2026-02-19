// Page: resources/views/deployment/settings.blade.php

@extends('layout.mainlayout')

@section('title', 'Manager Settings')

@section('content')

{{-- 
    CUSTOM STYLES: SIDEBAR AWARENESS & UI 
--}}
<style>
    :root {
        --side-w: 270px;
        --side-w-collapsed: 70px;
        --nav-h: 70px;
    }

    /* 1. LAYOUT WRAPPER (Handles Sidebar Offset) */
    #settings-wrapper {
        transition: margin-left 0.3s ease, padding-top 0.3s ease;
        padding: 1.5rem;
        /* Critical: Clear fixed navbar */
        padding-top: 110px; 
        min-height: 100vh;
        background: #f8fafc;
    }

    /* Desktop: Sidebar Open */
    @media (min-width: 992px) {
        #settings-wrapper { 
            margin-left: var(--side-w); 
            width: calc(100% - var(--side-w)); 
        }
        /* Handle collapsed sidebar state if template supports it */
        body.sidebar-icon-only #settings-wrapper { 
            margin-left: var(--side-w-collapsed); 
            width: calc(100% - var(--side-w-collapsed)); 
        }
        body.sidebar-collapsed #settings-wrapper,
        body.mini-sidebar #settings-wrapper {
            margin-left: var(--side-w-collapsed);
            width: calc(100% - var(--side-w-collapsed));
        }
    }

    /* Mobile: Full Width */
    @media (max-width: 991.98px) {
        #settings-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* 2. CARD STYLING */
    .settings-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* Print Optimization */
    @media print {
        #settings-wrapper { margin: 0 !important; padding: 0 !important; }
        .no-print, .btn, .breadcrumb { display: none !important; }
        .settings-card { border: none !important; box-shadow: none !important; }
    }
</style>

<div id="settings-wrapper">
    
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none">System</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">Manager Settings</h3>
        </div>
        <button onclick="window.print()" class="btn btn-light border bg-white shadow-sm no-print">
            <i class="fas fa-print me-2 text-secondary"></i> Print Config
        </button>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            
            {{-- Settings Form Card --}}
            <div class="settings-card p-4">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3">
                        <i class="fas fa-user-cog fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Profile & Environment</h5>
                        <p class="text-muted small mb-0">Manage your deployment credentials</p>
                    </div>
                </div>

                <form action="{{ route('deployment.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    {{-- Section 1: Environment --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Domain Environment</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-globe text-muted"></i></span>
                            <input type="text" class="form-control bg-light" value="{{ env('SESSION_DOMAIN', 'Not Configured') }}" readonly>
                            <span class="input-group-text bg-light border-start-0 text-success fw-bold small">ACTIVE</span>
                        </div>
                        <div class="form-text">Defined in environment configuration. Cannot be changed here.</div>
                    </div>

                    {{-- Section 2: Personal Info --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Email Address</label>
                            <input type="email" class="form-control bg-light" value="{{ auth()->user()->email }}" disabled>
                        </div>
                    </div>

                    {{-- Section 3: Preferences --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-3">System Notifications</label>
                        
                        <div class="card bg-light border-0 p-3">
                            <div class="form-check form-switch d-flex justify-content-between ps-0 align-items-center">
                                <label class="form-check-label ms-2" for="notifyDeploy">
                                    <span class="d-block fw-bold text-dark">Deployment Alerts</span>
                                    <span class="small text-muted">Receive email notifications when new tenants are provisioned.</span>
                                </label>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" id="notifyDeploy" name="notify_deploy" {{ auth()->user()->notify_deploy ? 'checked' : '' }} style="width: 3em; height: 1.5em;">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-2 border-top">
                        <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm fw-bold">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Sidebar Toggle Listener script --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ensure content resizes if sidebar is toggled via navbar button
        const toggler = document.querySelector('.navbar-toggler');
        if(toggler) {
            toggler.addEventListener('click', () => {
                // Just a safeguard to trigger layout recalc if needed
                setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
            });
        }
    });
</script>

@endsection
