@extends('layout.mainlayout')

@section('content')

<style>
    :root {
        --deploy-sidebar-w: 270px;
        --deploy-sidebar-collapsed: 70px;
    }

    /* 1. LAYOUT WRAPPER */
    #payments-wrapper {
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
        #payments-wrapper { 
            margin-left: var(--deploy-sidebar-w); 
            width: calc(100% - var(--deploy-sidebar-w)); 
        }
    }

    /* DESKTOP: Toggled State (Sidebar Collapsed) */
    @media (min-width: 992px) {
        body.sidebar-icon-only #payments-wrapper { 
            margin-left: var(--deploy-sidebar-collapsed); 
            width: calc(100% - var(--deploy-sidebar-collapsed)); 
        }
        body.sidebar-collapsed #payments-wrapper,
        body.mini-sidebar #payments-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    /* MOBILE: Full Width */
    @media (max-width: 991.98px) {
        #payments-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* Table Styling */
    .payment-table thead {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        color: #6c757d;
        font-weight: 700;
    }

    /* Print Optimization */
    @media print {
        #payments-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .no-print, .btn, .navbar, .sidebar { display: none !important; }
        .card { border: none !important; shadow: none !important; }
        body { background-color: white !important; }
    }
</style>

<div id="payments-wrapper">
    <div class="container-fluid px-0">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small text-muted">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Financials</a></li>
                        <li class="breadcrumb-item active">Payments</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-1">Payments Ledger</h4>
                <p class="text-muted small mb-0">Transactions on: <span class="fw-bold text-primary">{{ env('SESSION_DOMAIN', 'System') }}</span></p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm bg-white">
                    <i class="fas fa-print me-2"></i>Print Ledger
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Recent Transactions</h6>
                <div class="input-group input-group-sm w-auto no-print">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" placeholder="Search ref ID...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle payment-table mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Reference</th>
                                <th>Company / Payer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th class="text-end pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            <tr>
                                <td class="ps-4">
                                    <code class="text-dark bg-light px-2 py-1 rounded border">#{{ $payment->transaction_id ?? $payment->id }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light text-success rounded-circle me-2 d-flex align-items-center justify-content-center border" style="width: 30px; height: 30px;">
                                            <i class="fas fa-building small"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $payment->company->name ?? 'N/A' }}</div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">{{ $payment->company->email ?? 'No Email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-dark">₦{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if(strtolower((string)$payment->payment_status) == 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                            PAID
                                        </span>
                                    @elseif(strtolower((string)$payment->payment_status) == 'failed')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                            FAILED
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                            {{ strtoupper($payment->payment_status) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-muted text-uppercase">
                                        <i class="far fa-credit-card me-1"></i> {{ $payment->payment_gateway ?? $payment->payment_method ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    {{ $payment->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fas fa-receipt fa-2x opacity-25"></i></div>
                                    No payment records found on {{ env('SESSION_DOMAIN') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(method_exists($payments, 'links'))
                <div class="card-footer bg-white py-3 border-top">
                    {{ $payments->links() }}
                </div>
            @endif
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

    // Custom print handler for financial records
    window.onbeforeprint = function() {
        console.log("Preparing payment ledger for print on {{ env('SESSION_DOMAIN') }}");
    };
</script>
@endsection
