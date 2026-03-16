@extends('layout.mainlayout')

@section('content')

{{-- 
    CUSTOM STYLES: SIDEBAR AWARENESS & UI 
    - Width adjusted to 270px to match Deployment Manager Sidebar
    - Added logic for 'sidebar-icon-only' toggle state
--}}
<style>
    :root {
        --deploy-sidebar-w: 270px;
        --deploy-sidebar-collapsed: 70px;
    }

    /* 1. LAYOUT WRAPPER */
    #companies-wrapper {
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
        #companies-wrapper { 
            margin-left: var(--deploy-sidebar-w); 
            width: calc(100% - var(--deploy-sidebar-w)); 
        }
    }

    /* DESKTOP: Toggled State (Sidebar Collapsed) */
    @media (min-width: 992px) {
        body.sidebar-icon-only #companies-wrapper { 
            margin-left: var(--deploy-sidebar-collapsed); 
            width: calc(100% - var(--deploy-sidebar-collapsed)); 
        }
        body.sidebar-collapsed #companies-wrapper,
        body.mini-sidebar #companies-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    /* MOBILE: Full Width */
    @media (max-width: 991.98px) {
        #companies-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* Print Optimization */
    @media print {
        #companies-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .no-print, .btn, .navbar, .sidebar { display: none !important; }
    }
</style>

<div id="companies-wrapper">
    <div class="container-fluid px-0">
        @php
            $companies = $companies ?? collect();
            $isPendingView = request()->routeIs('deployment.companies.pending');
            $isActiveView = request()->routeIs('deployment.companies.active');
        @endphp
        
        {{-- Header Section --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    @if($isPendingView)
                        Pending Companies
                    @elseif($isActiveView)
                        Active Companies
                    @else
                        Companies Management
                    @endif
                </h4>
                <p class="text-muted small mb-0">Platform: <span class="fw-bold text-primary">{{ env('SESSION_DOMAIN', 'Local') }}</span></p>
            </div>
            <div class="no-print d-flex gap-2">
                <button onclick="window.print()" class="btn btn-light border bg-white shadow-sm btn-sm">
                    <i class="fas fa-print me-2 text-secondary"></i> Print Directory
                </button>
                <a href="{{ route('deployment.customers.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-user-plus me-2"></i> Register Customer
                </a>
            </div>
        </div>

        {{-- Companies Table Card --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    @if($isPendingView)
                        Pending Deployment Queue
                    @elseif($isActiveView)
                        Active Deployments Registry
                    @else
                        All Deployments Registry
                    @endif
                </h6>
                <div class="input-group input-group-sm w-auto no-print">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" placeholder="Search companies...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Company Profile</th>
                                <th>Status</th>
                                <th>Domain</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                                            {{ substr($company->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $company->name }}</div>
                                            <div class="small text-muted">{{ $company->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($company->status == 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                            ACTIVE
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                            {{ strtoupper($company->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <code class="text-muted bg-light px-2 py-1 rounded border small">
                                        {{ ($company->domain_prefix ?: strtolower(str_replace(' ', '', $company->name))) . '.' . config('session.domain', 'smatbook.com') }}
                                    </code>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('deployment.companies.view', $company->id) }}" class="btn btn-sm btn-outline-dark border no-print shadow-sm">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    @if(in_array(strtolower((string) $company->status), ['pending', 'awaiting payment', 'awaiting_payment'], true))
                                        <a href="{{ route('deployment.companies.edit', $company->id) }}" class="btn btn-sm btn-outline-primary no-print shadow-sm">
                                            <i class="fas fa-pen me-1"></i> Edit
                                        </a>
                                    @endif
                                    @if(strtolower((string) $company->status) === 'pending' && !empty($company->subscription?->id))
                                        <a href="{{ route('saas.checkout', $company->subscription->id) }}" class="btn btn-sm btn-primary text-white no-print shadow-sm">
                                            <i class="fas fa-credit-card me-1"></i> Proceed to Payment
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="mb-3"><i class="fas fa-building fa-2x text-muted opacity-50"></i></div>
                                    <h6 class="text-muted fw-bold">No Companies Found</h6>
                                    <p class="text-muted small">Get started by adding a new company deployment.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Handle Sidebar Toggle Resize Events
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.querySelector('.navbar-toggler');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
            });
        }
    });

    window.onbeforeprint = function() {
        console.log("Preparing deployment list for {{ env('SESSION_DOMAIN') }}");
    };
</script>
@endsection
