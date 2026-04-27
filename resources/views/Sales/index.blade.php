@extends('layout.mainlayout')

@section('content')
<style>
    .pos-content-area {
        margin-left: var(--sb-sidebar-w, 270px); 
        padding: 30px;
        transition: all 0.3s ease-in-out;
        background-color: #f8fafc;
        min-height: 100vh;
        margin-top: 60px;
    }

    body.mini-sidebar .pos-content-area { margin-left: var(--sb-sidebar-collapsed, 80px); }

    @media (max-width: 991.98px) {
        .pos-content-area { margin-left: 0 !important; padding: 15px; }
    }

    .report-header {
        padding: 0;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-search-container {
        position: relative;
        width: 300px;
    }

    .header-search-input {
        height: 42px;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        padding-left: 35px;
        font-size: 13px;
        background: #fff;
        box-shadow: none;
    }

    .header-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .sales-table-card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .table thead th {
        background-color: #f8fafc;
        color: #64748b;
        text-transform: uppercase;
        font-size: 11px;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .status-badge {
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 10px;
    }

    .table tbody td {
        border-color: #eef2f7;
    }

    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #fff;
        color: #2563eb;
        font-weight: 600;
    }

    .page-title {
        color: #0f172a;
        font-size: 1.15rem;
        letter-spacing: -0.02em;
    }

    .amount-text {
        color: #0f172a;
        font-size: 0.95rem;
    }

    .table-action-btn {
        color: #475569;
    }

    .table-action-btn:hover {
        color: #2563eb;
        background: #f8fafc;
    }

    .report-header .text-muted.small {
        font-size: 0.9rem !important;
    }

    .header-search-input {
        font-size: 0.88rem;
    }

    .branch-chip {
        font-size: 0.84rem;
        padding: 8px 12px;
    }

    .filter-card .form-label {
        font-size: 0.72rem !important;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .filter-card .form-control,
    .filter-card .input-group-text,
    .filter-card .btn,
    .filter-card .form-control-sm {
        font-size: 0.86rem;
    }

    .sales-table-card .table tbody td {
        font-size: 0.92rem;
    }

    .sales-table-card .table tbody small {
        font-size: 0.72rem !important;
    }

    .sales-table-card .badge {
        font-size: 0.75rem;
    }
</style>

<div class="pos-content-area">

    <div class="report-header">
        <div>
            <h3 class="fw-bold mb-0 page-title">Sales Transactions</h3>
            <p class="text-muted small mb-0">Manage history and track invoice statuses.</p>
            <div class="mt-2">
                <span class="branch-chip">
                    <i class="fas fa-code-branch me-2"></i>
                    Active Branch: {{ $activeBranch['name'] ?? 'All Business Activity' }}
                </span>
            </div>
        </div>

        <div class="d-none d-md-flex gap-3 align-items-center">

            <div class="header-search-container">
                <i class="fas fa-search header-search-icon"></i>
                <input type="text" id="quick-invoice-id-search" class="form-control header-search-input" placeholder="Quick Search Invoice ID...">
            </div>

            <span class="badge bg-white text-dark border p-2 shadow-sm">
                <i class="fas fa-calendar-alt text-warning me-2"></i>{{ date('D, M d, Y') }}
            </span>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body p-4">
            <form action="{{ route('sales.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Invoice No</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-hashtag text-muted"></i></span>
                        <input type="text" name="invoice_no" class="form-control border-start-0" placeholder="e.g. INV-100" value="{{ request('invoice_no') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Customer</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="customer_name" class="form-control border-start-0" placeholder="Name" value="{{ request('customer_name') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Specific Date</label>
                    <input type="date" name="sale_date" class="form-control form-control-sm" value="{{ request('sale_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card sales-table-card">
        <div class="card-body p-0">
            @if($sales->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Invoice Info</th>
                            <th>Customer Name</th>
                            <th>Branch</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Status</th>
                            <th>Date / Time</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">#{{ $sale->invoice_no }}</div>
                                <small class="text-muted" style="font-size: 10px;">ID: {{ $sale->id }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; background: #f8fafc; color: #475569; font-size: 11px; font-weight: bold;">
                                        {{ substr($sale->customer_name ?? 'W', 0, 1) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $sale->customer_name ?? 'Walk-in Customer' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light border text-dark">
                                    {{ $sale->branch_label ?? 'Workspace Default' }}
                                </span>
                            </td>
                            <td class="text-end fw-bold amount-text">
                                ₦{{ number_format($sale->total, 2) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeStyle = match($sale->payment_status) {
                                        'paid' => 'bg-success-subtle text-success border-success',
                                        'partial' => 'bg-warning-subtle text-warning border-warning',
                                        'unpaid' => 'bg-danger-subtle text-danger border-danger',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                @endphp
                                <span class="badge border status-badge {{ $badgeStyle }}">
                                    {{ strtoupper($sale->payment_status) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-dark small fw-bold">{{ $sale->created_at->format('d M Y') }}</div>
                                <div class="text-muted" style="font-size: 10px;">{{ $sale->created_at->format('H:i A') }}</div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Action
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sales.show', $sale->id) }}">
                                                <i class="fas fa-eye me-2 text-info"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sales.edit', $sale->id) }}">
                                                <i class="fas fa-edit me-2 text-primary"></i>Edit
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-top bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted small mb-0">Record {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }}</p>
                    <div>
                        {{ $sales->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            @else
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-4x text-light mb-3"></i>
                <h5 class="text-muted">No sales records found.</h5>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm mt-2">Clear All Filters</a>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Quick Search Listener
        $('#quick-invoice-id-search').on('keypress', function(e) {
            if(e.which == 13) {
                let invoiceId = $(this).val();
                if(invoiceId) {
                    let url = "{{ route('sales.show', ':id') }}";
                    window.location.href = url.replace(':id', invoiceId);
                }
            }
        });
    });

    // Fix dropdowns being clipped by .table-responsive overflow.
    // position:fixed is viewport-relative — do NOT add window.scrollY.
    // Re-apply on 'shown.bs.dropdown' to override any transform Popper.js injects.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dropdown [data-bs-toggle="dropdown"]').forEach(function (toggle) {
            var menu = toggle.closest('.dropdown').querySelector('.dropdown-menu');
            if (!menu) return;

            function positionMenu() {
                var rect = toggle.getBoundingClientRect();
                menu.style.position  = 'fixed';
                menu.style.zIndex    = '9999';
                menu.style.top       = rect.bottom + 'px';
                menu.style.left      = 'auto';
                menu.style.right     = (window.innerWidth - rect.right) + 'px';
                menu.style.minWidth  = '160px';
                menu.style.transform = 'none';
            }

            toggle.addEventListener('show.bs.dropdown', function () {
                document.body.appendChild(menu);
                positionMenu();
            });

            toggle.addEventListener('shown.bs.dropdown', positionMenu);

            toggle.addEventListener('hide.bs.dropdown', function () {
                toggle.closest('.dropdown').appendChild(menu);
                menu.removeAttribute('style');
            });
        });
    });
</script>
@endsection
