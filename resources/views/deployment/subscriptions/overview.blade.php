// Page: resources/views/deployment/subscriptions/overview.blade.php

@extends('layout.mainlayout')

@section('title', 'Subscription Overview')

@section('content')

{{-- 
    CUSTOM STYLES: SIDEBAR AWARENESS & UI 
    - Width adjusted to 270px for Deployment Manager Sidebar
    - Added logic for 'sidebar-icon-only' toggle state
--}}
<style>
    :root {
        --deploy-sidebar-w: 270px;
        --deploy-sidebar-collapsed: 70px; /* Standard icon-only width */
        --nav-height: 70px; 
    }

    /* 1. LAYOUT WRAPPER */
    #subscription-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        padding: 1.5rem;
        /* Critical: Clear fixed navbar */
        padding-top: 110px; 
        min-height: 100vh;
        background: #f8fafc;
        width: 100%;
    }

    /* DESKTOP: Default State (Sidebar Open) */
    @media (min-width: 992px) {
        #subscription-wrapper { 
            margin-left: var(--deploy-sidebar-w); 
            width: calc(100% - var(--deploy-sidebar-w)); 
        }
    }

    /* DESKTOP: Toggled State (Sidebar Collapsed) 
       This class is usually added to body by the mainlayout toggler 
    */
    @media (min-width: 992px) {
        body.sidebar-icon-only #subscription-wrapper { 
            margin-left: var(--deploy-sidebar-collapsed); 
            width: calc(100% - var(--deploy-sidebar-collapsed)); 
        }
        body.sidebar-collapsed #subscription-wrapper,
        body.mini-sidebar #subscription-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    /* MOBILE: Full Width */
    @media (max-width: 991.98px) {
        #subscription-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* 2. UI ELEMENTS */
    .stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }
    
    .icon-box {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }

    /* Print Optimization */
    @media print {
        #subscription-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .no-print, .btn, .breadcrumb, .navbar, .sidebar { display: none !important; }
        .stat-card { border: 1px solid #ccc !important; box-shadow: none !important; }
        body { background: white !important; }
    }
</style>

<div id="subscription-wrapper">
    
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Deployment</a></li>
                    <li class="breadcrumb-item active">Subscriptions</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">Subscription Overview</h3>
            <p class="text-muted small mb-0">
                Managed Domain: <span class="fw-bold text-primary">{{ env('SESSION_DOMAIN', 'Primary Cluster') }}</span>
            </p>
        </div>
        
        <div class="d-flex gap-2">
            <button onclick="window.location.reload()" class="btn btn-light border bg-white shadow-sm no-print">
                <i class="fas fa-sync-alt text-muted"></i>
            </button>
            <button onclick="window.print()" class="btn btn-light border bg-white shadow-sm no-print">
                <i class="fas fa-print me-2 text-secondary"></i> Print Report
            </button>
        </div>
    </div>

    {{-- Dynamic Metrics Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Revenue --}}
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Total Revenue</p>
                        <h3 class="fw-bold mb-0 text-dark">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</h3>
                        <small class="text-success fw-bold"><i class="fas fa-arrow-up"></i> Projected</small>
                    </div>
                    <div class="icon-box bg-primary text-white shadow-sm">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Plans --}}
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Active Plans</p>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($stats['active_count'] ?? 0) }}</h3>
                        <small class="text-muted">Tenants</small>
                    </div>
                    <div class="icon-box bg-success text-white shadow-sm">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring Soon --}}
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Expiring Soon</p>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($stats['expiring_count'] ?? 0) }}</h3>
                        <small class="text-warning fw-bold">Action Required</small>
                    </div>
                    <div class="icon-box bg-warning text-white shadow-sm">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending / Issues --}}
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Pending/Failed</p>
                        <h3 class="fw-bold mb-0 text-danger">{{ number_format($stats['pending_count'] ?? 0) }}</h3>
                        <small class="text-danger">Review Log</small>
                    </div>
                    <div class="icon-box bg-danger text-white shadow-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Data Table --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0 text-dark">Active Subscriptions Registry</h6>
            <div class="input-group input-group-sm w-auto no-print">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control bg-light border-start-0" placeholder="Search tenant...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Tenant Profile</th>
                            <th>Plan Type</th>
                            <th>Status</th>
                            <th>Billing Cycle</th>
                            <th>Next Renewal</th>
                            <th class="text-end pe-4">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold border" style="width: 35px; height: 35px;">
                                        {{ substr($sub->company->name ?? 'T', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $sub->company->name ?? 'Unknown Tenant' }}</div>
                                        <div class="small text-muted">{{ $sub->company->email ?? 'no-email@system.com' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-white text-dark border shadow-sm">
                                    {{ $sub->plan_name ?? $sub->plan ?? 'Basic' }}
                                </span>
                            </td>
                            <td>
                                @if(strtolower((string)$sub->status) == 'active')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                        <i class="fas fa-check me-1" style="font-size: 8px;"></i> Active
                                    </span>
                                @elseif(strtolower((string)$sub->status) == 'expired')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                        Expired
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">
                                ₦{{ number_format($sub->amount, 2) }} <span class="text-muted fw-normal small">/{{ strtolower($sub->billing_cycle ?? 'monthly') }}</span>
                            </td>
                            <td>
                                @if(!empty($sub->end_date))
                                    @php $endDate = \Carbon\Carbon::parse($sub->end_date); @endphp
                                    <div class="small text-dark fw-bold">{{ $endDate->format('M d, Y') }}</div>
                                    <div class="small {{ $endDate->isPast() ? 'text-danger' : 'text-muted' }}">
                                        {{ $endDate->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="#" class="btn btn-sm btn-white border shadow-sm hover-primary no-print">
                                    <i class="fas fa-cog text-muted"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="mb-3">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-file-invoice-dollar fa-2x text-muted opacity-50"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-bold">No Active Subscriptions</h6>
                                <p class="text-muted small">Once tenants subscribe, they will appear here.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- Pagination (if available) --}}
        @if(method_exists($subscriptions, 'links'))
            <div class="card-footer bg-white py-3">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    /**
     * Script to handle sidebar toggle events and layout adjustments.
     * Looks for the standard Bootstrap toggler or specific ID.
     */
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtns = document.querySelectorAll('.navbar-toggler, #sidebarToggle');
        
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // The CSS relies on body.sidebar-icon-only or body.sidebar-collapsed
                // We dispatch a resize event to ensure any charts (if added later) redraw correctly
                setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
            });
        });
    });
</script>
@endsection
