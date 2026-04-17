
@extends('layout.mainlayout')

@section('content')

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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Commissions Management</h4>
                <p class="text-muted small mb-0">Track earned commissions, payout readiness, and transfer history from one place.</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm bg-white">
                    <i class="fas fa-print me-2"></i>Export Report
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold mb-1">Total Commissions</div>
                                <div class="h4 fw-bold mb-0 text-dark">₦{{ number_format($totalCommissions ?? 0, 2) }}</div>
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
                                <div class="h4 fw-bold mb-0 text-dark">₦{{ number_format($paidCommissions ?? 0, 2) }}</div>
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
                                <div class="small text-muted text-uppercase fw-bold mb-1">Available For Payout</div>
                                <div class="h4 fw-bold mb-0 text-dark">₦{{ number_format($pendingCommissions ?? 0, 2) }}</div>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded p-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold mb-1">Processing Payouts</div>
                                <div class="h4 fw-bold mb-0 text-dark">₦{{ number_format($processingPayouts ?? 0, 2) }}</div>
                            </div>
                            <div class="bg-info-subtle text-info rounded p-3">
                                <i class="fas fa-spinner fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">Payout Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('deployment.commissions.payout-profile') }}" class="row g-3" id="payout-profile-form">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bank Name</label>
                                <input type="text" name="payout_bank_name" class="form-control" value="{{ old('payout_bank_name', optional($manager)->payout_bank_name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bank Code</label>
                                <input type="text" name="payout_bank_code" class="form-control" value="{{ old('payout_bank_code', optional($manager)->payout_bank_code ?? '') }}" placeholder="Required for automated transfers">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Name</label>
                                <input type="text" name="payout_account_name" class="form-control" value="{{ old('payout_account_name', optional($manager)->payout_account_name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Number</label>
                                <input type="text" name="payout_account_number" class="form-control" value="{{ old('payout_account_number', optional($manager)->payout_account_number ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payout Provider</label>
                                <select name="payout_provider" class="form-select" required>
                                    <option value="paystack" {{ old('payout_provider', optional($manager)->payout_provider ?? 'paystack') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                                    <option value="flutterwave" {{ old('payout_provider', optional($manager)->payout_provider ?? '') === 'flutterwave' ? 'selected' : '' }}>Flutterwave</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Min. Payout</label>
                                <input type="number" step="0.01" min="0" name="minimum_payout_amount" class="form-control" value="{{ old('minimum_payout_amount', optional($manager)->minimum_payout_amount ?? 5000) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="auto_payout_enabled" name="auto_payout_enabled" value="1" {{ old('auto_payout_enabled', !empty(optional($manager)->auto_payout_enabled)) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="auto_payout_enabled">Enable auto payout</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="small text-muted">
                                    Status:
                                    <span class="fw-bold text-primary text-uppercase">{{ str_replace('_', ' ', optional($manager)->payout_status ?? 'not_configured') }}</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-outline-primary">Save Payout Profile</button>
                                    <button type="submit" form="request-payout-form" class="btn btn-primary" {{ ($pendingCommissions ?? 0) <= 0 ? 'disabled' : '' }}>
                                        Request Payout
                                    </button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('deployment.commissions.request-payout') }}" id="request-payout-form">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">Payout Readiness</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Auto payout</span>
                            <span class="fw-semibold">{{ !empty(optional($manager)->auto_payout_enabled) ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Available balance</span>
                            <span class="fw-semibold">₦{{ number_format($pendingCommissions ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Processing balance</span>
                            <span class="fw-semibold">₦{{ number_format($processingPayouts ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Failed / review balance</span>
                            <span class="fw-semibold">₦{{ number_format($failedPayouts ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Threshold</span>
                            <span class="fw-semibold">₦{{ number_format((float) (optional($manager)->minimum_payout_amount ?? 5000), 2) }}</span>
                        </div>
                        <div class="alert alert-light border mt-3 mb-0 small">
                            Automatic transfers only run when the payout profile is complete, the provider is configured, auto payout is enabled, and the available commission meets the threshold.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">Recent Payouts</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase text-muted">
                        <tr>
                            <th class="ps-4">Reference</th>
                            <th>Gateway</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Processed</th>
                            <th class="pe-4">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayouts ?? [] as $payout)
                            <tr>
                                <td class="ps-4"><code>{{ $payout->payout_reference }}</code></td>
                                <td class="text-capitalize">{{ $payout->gateway ?: 'n/a' }}</td>
                                <td class="fw-bold">₦{{ number_format((float) $payout->amount, 2) }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ str_replace('_', ' ', $payout->status) }}</span></td>
                                <td class="small text-muted">{{ optional($payout->processed_at ?? $payout->created_at)->format('M d, Y H:i') }}</td>
                                <td class="pe-4 small text-muted">{{ $payout->failure_reason ?: 'Healthy payout record' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No payout records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

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
                                <td class="fw-bold text-dark">₦{{ number_format((float) ($comm->commission_amount ?? $comm->amount ?? 0), 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ (float) ($comm->rate ?? $comm->commission_rate ?? $rate ?? 0) }}%</span>
                                </td>
                                <td>
                                    @if($comm->status == 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                            PAID
                                        </span>
                                    @elseif(!empty($comm->payout_id))
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill">
                                            IN PAYOUT
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                            AVAILABLE
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
                                    No commission records found.
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
        console.log("Generating commission statement.");
    };
</script>
@endsection
