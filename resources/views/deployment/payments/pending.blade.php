@extends('layout.mainlayout')

@section('page-title', 'Pending Payments')

@section('content')
<style>
    :root {
        --deploy-sidebar-w: 270px;
        --deploy-sidebar-collapsed: 70px;
    }

    #payments-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        padding: 1.5rem;
        padding-top: 110px;
        min-height: 100vh;
        background: #f8fafc;
        width: 100%;
    }

    @media (min-width: 992px) {
        #payments-wrapper {
            margin-left: var(--deploy-sidebar-w);
            width: calc(100% - var(--deploy-sidebar-w));
        }

        body.sidebar-icon-only #payments-wrapper,
        body.sidebar-collapsed #payments-wrapper,
        body.mini-sidebar #payments-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    @media (max-width: 991.98px) {
        #payments-wrapper {
            margin-left: 0;
            width: 100%;
            padding-top: 100px;
        }
    }

    .btn-proceed-payment,
    .btn-proceed-payment:hover,
    .btn-proceed-payment:focus,
    .btn-proceed-payment:active,
    .btn-proceed-payment.active {
        background: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #ffffff !important;
    }

    .btn-proceed-payment:focus,
    .btn-proceed-payment:active {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
    }
</style>

<div id="payments-wrapper">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Pending Payments</h4>
                <p class="text-muted small mb-0">Complete checkout to activate pending customers.</p>
            </div>
            <a href="{{ route('deployment.payments.index') }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> All Payments
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Billing Cycle</th>
                            <th>Company</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-dark border">
                                    {{ ucfirst(strtolower((string) ($p->billing_cycle ?? 'monthly'))) }}
                                </span>
                            </td>
                            <td>{{ $p->company->name ?? 'N/A' }}</td>
                            <td>₦{{ number_format((float)($p->amount ?? 0), 0) }}</td>
                            <td>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                    {{ strtoupper($p->payment_status ?? 'PENDING') }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('saas.checkout', $p->id) }}" class="btn btn-sm btn-proceed-payment">
                                    <i class="fas fa-credit-card me-1"></i> Proceed to Payment
                                </a>
                                @if(!empty($p->company_id))
                                    <a href="{{ route('deployment.companies.view', $p->company_id) }}" class="btn btn-sm btn-outline-dark">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <form action="{{ route('deployment.companies.delete', $p->company_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this pending customer and all related records?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">No pending payments.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($payments, 'links'))
            <div class="mt-3">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
