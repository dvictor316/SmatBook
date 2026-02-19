

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
    #commissions-wrapper {
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
        #commissions-wrapper { 
            margin-left: var(--deploy-sidebar-w); 
            width: calc(100% - var(--deploy-sidebar-w)); 
        }
    }

    /* DESKTOP: Toggled State (Sidebar Collapsed) */
    @media (min-width: 992px) {
        body.sidebar-icon-only #commissions-wrapper { 
            margin-left: var(--deploy-sidebar-collapsed); 
            width: calc(100% - var(--deploy-sidebar-collapsed)); 
        }
        body.sidebar-collapsed #commissions-wrapper,
        body.mini-sidebar #commissions-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    /* MOBILE: Full Width */
    @media (max-width: 991.98px) {
        #commissions-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* Table Styling */
    .commission-table thead {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        color: #6c757d;
        font-weight: 700;
    }

    /* Print Optimization */
    @media print {
        #commissions-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .no-print, .btn, .navbar, .sidebar { display: none !important; }
        .card { border: none !important; shadow: none !important; }
        body { background-color: white !important; }
    }
</style>

<div id="commissions-wrapper">
    <div class="container-fluid px-0">
        
        {{-- Header Section --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Commissions Management</h4>
                <p class="text-muted small mb-0">Reporting for: <span class="fw-bold text-primary">{{ env('SESSION_DOMAIN', 'Primary Domain') }}</span></p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm bg-white">
                    <i class="fas fa-print me-2"></i>Export Report
                </button>
            </div>
        </div>

        {{-- Metrics Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold mb-1">Total Commissions</div>
                                <div class="h4 fw-bold mb-0 text-dark">${{ number_format($totalCommissions ?? 0, 2) }}</div>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded p-3">
                                <i class="fas fa-chart-line fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold mb-1">Paid Out</div>
                                <div class="h4 fw-bold mb-0 text-dark">${{ number_format($paidCommissions ?? 0, 2) }}</div>
                            </div>
                            <div class="bg-success-subtle text-success rounded p-3">
                                <i class="fas fa-check-double fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold mb-1">Pending Approval</div>
                                <div class="h4 fw-bold mb-0 text-dark">${{ number_format($pendingCommissions ?? 0, 2) }}</div>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded p-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Commission Logs Table --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Commission Ledger</h6>
                <div class="input-group input-group-sm w-auto no-print">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" placeholder="Search reference...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle commission-table mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Reference</th>
                                <th>Manager/Agent</th>
                                <th>Amount</th>
                                <th>Percentage</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($commissions as $comm)
                            <tr>
                                <td class="ps-4">
                                    <code class="text-primary bg-primary-subtle px-2 py-1 rounded">#{{ $comm->id }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light text-secondary rounded-circle me-2 d-flex align-items-center justify-content-center border" style="width: 30px; height: 30px;">
                                            {{ substr($comm->manager_name ?? auth()->user()->name ?? 'S', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $comm->manager_name ?? auth()->user()->name ?? 'System' }}</div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">{{ $comm->manager_email ?? auth()->user()->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-dark">${{ number_format((float) ($comm->commission_amount ?? $comm->amount ?? 0), 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ (float) ($comm->rate ?? $comm->commission_rate ?? $rate ?? 0) }}%</span>
                                </td>
                                <td>
                                    @if($comm->status == 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                            PAID
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                            {{ strtoupper($comm->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    {{ !empty($comm->created_at) ? \Illuminate\Support\Carbon::parse($comm->created_at)->format('M d, Y') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fas fa-file-invoice-dollar fa-2x opacity-25"></i></div>
                                    No commission records found on {{ env('SESSION_DOMAIN') }}
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
    // Handle Sidebar Toggle for Layout Adjustments
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.querySelector('.navbar-toggler');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
            });
        }
    });

    // Financial reporting print handler
    window.onbeforeprint = function() {
        console.log("Generating commission statement for {{ env('SESSION_DOMAIN') }}");
    };
</script>
@endsection
