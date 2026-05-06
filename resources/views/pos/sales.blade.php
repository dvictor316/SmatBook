@extends('layout.mainlayout')

@section('content')
<style>
    .pos-content-area {
        padding: 24px;
        min-height: 100vh;
        background: #f6f8fc;
    }

    @media (max-width: 991.98px) {
        .pos-content-area { padding: 16px; }
    }

    .report-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dfe7f3;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }

    .header-search-container {
        position: relative;
        width: 300px;
    }

    .header-search-input {
        height: 40px;
        border-radius: 12px;
        border: 1px solid #d9e2ef;
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
        border: 1px solid #e3eaf4;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .sales-table-card {
        border: 1px solid #e3eaf4;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }

    .summary-card {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
        border: 0;
        border-radius: 20px;
        box-shadow: 0 16px 40px rgba(29, 78, 216, 0.18);
        color: #fff;
    }

    .summary-card .summary-label {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

.summary-card .summary-value {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.1;
    color: #fde047;
    text-shadow: 0 0 16px rgba(253, 224, 71, 0.32);
}

    .summary-card .summary-subtle {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.88rem;
    }

    .summary-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .summary-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .summary-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
    }

    .summary-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.18);
    }

    .summary-filter-grid .form-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .summary-filter-grid .form-control,
    .summary-filter-grid .input-group-text,
    .summary-filter-grid .btn {
        border-radius: 12px;
        min-height: 42px;
    }

    .summary-filter-grid .input-group-text,
    .summary-filter-grid .form-control {
        border-color: rgba(255, 255, 255, 0.22);
    }

    .summary-filter-grid .form-control,
    .summary-filter-grid .input-group-text {
        background: rgba(255, 255, 255, 0.96);
    }

    .summary-filter-grid .btn-light {
        color: #0f172a;
        font-weight: 700;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .pos-content-area {
            padding: 0;
            background: #fff;
        }

        .summary-card,
        .sales-table-card {
            box-shadow: none;
            border: 1px solid #d1d5db;
        }
    }

    .table thead th {
        background-color: #f5f8ff;
        color: #5b6b87;
        text-transform: uppercase;
        font-size: 11px;
        padding: 15px;
        border: none;
        letter-spacing: 0.04em;
        font-weight: 800;
    }

    .table tbody td {
        border-color: #edf2f7;
    }

    .table tbody tr:hover {
        background: #fafcff;
    }

    .status-badge {
        font-weight: 700;
        padding: 7px 12px;
        border-radius: 50px;
        font-size: 10px;
    }

    .btn-soft-clear {
        background: #fff;
        color: #475569;
        border: 1px solid #d9e2ef;
    }

    .btn-soft-clear:hover {
        background: #f8fafc;
        color: #334155;
    }

    .badge-soft-branch {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    .sale-avatar {
        width: 28px;
        height: 28px;
        background: #eef4ff;
        color: #3156c8;
        font-size: 11px;
        font-weight: 700;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .status-partial {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }

    .status-unpaid {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
    }
</style>

@php
    $selectedSaleDate = request('sale_date');
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $filterDateLabel = 'All recorded sales';

    if ($selectedSaleDate) {
        $filterDateLabel = 'Sales for ' . \Illuminate\Support\Carbon::parse($selectedSaleDate)->format('D, M d, Y');
    } elseif ($dateFrom && $dateTo) {
        $filterDateLabel = 'Sales from '
            . \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y')
            . ' to '
            . \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y');
    } elseif ($dateFrom) {
        $filterDateLabel = 'Sales from ' . \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y');
    } elseif ($dateTo) {
        $filterDateLabel = 'Sales up to ' . \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y');
    }
@endphp

<div class="pos-content-area">
    <div class="report-header">
        <div>
                <h3 class="fw-bold mb-0" style="color: #0f172a;">POS Sales</h3>
                <p class="text-muted small mb-0">All POS sales listed in purchase order sequence.</p>
                <div class="mt-2">
                    <span class="badge badge-soft-branch px-3 py-2">
                        <i class="fas fa-code-branch me-2 text-primary"></i>
                        Active Branch: {{ $activeBranch['name'] ?? 'All Business Activity' }}
                    </span>
                </div>
        </div>
        <div class="d-none d-md-flex gap-3 align-items-center">
            <span class="badge bg-white text-dark border p-2 shadow-sm">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                {{ $selectedSaleDate ? \Illuminate\Support\Carbon::parse($selectedSaleDate)->format('D, M d, Y') : date('D, M d, Y') }}
            </span>
            <div class="header-search-container">
                <i class="fas fa-search header-search-icon"></i>
                <input type="text" id="quick-invoice-id-search" class="form-control header-search-input" placeholder="Quick Search Invoice ID...">
            </div>
        </div>
    </div>

    <div class="card summary-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="summary-toolbar no-print">
                <div class="summary-label mb-0">Sales Summary & Filters</div>
                <div class="summary-actions">
                    <button type="button" class="summary-btn" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        <span>Print</span>
                    </button>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="summary-btn">
                        <i class="fas fa-file-excel"></i>
                        <span>Excel</span>
                    </a>
                </div>
            </div>

            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="summary-label">Total Sales Amount</div>
                    <div class="summary-value">₦{{ number_format((float) ($totalRevenue ?? 0), 2) }}</div>
                    <div class="summary-subtle mt-2">{{ $filterDateLabel }}</div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="summary-label">Sales Count</div>
                    <div class="fs-2 fw-bold">{{ number_format((int) ($totalSalesCount ?? 0)) }}</div>
                    <div class="summary-subtle mt-2">Filtered POS transactions</div>
                </div>
            </div>

            <form action="{{ route('pos.sales') }}" method="GET" class="row g-3 align-items-end mt-1 summary-filter-grid no-print">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Invoice No</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-hashtag text-muted"></i></span>
                        <input type="text" name="invoice_no" class="form-control" placeholder="e.g. INV-100" value="{{ request('invoice_no') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Customer</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="customer_name" class="form-control" placeholder="Customer name" value="{{ request('customer_name') }}">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Specific Date</label>
                    <input type="date" name="sale_date" class="form-control form-control-sm" value="{{ request('sale_date') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-lg-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-light btn-sm px-4">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('pos.sales') }}" class="btn btn-outline-light btn-sm px-4">
                        <i class="fas fa-sync me-1"></i> Reset
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
                            <th>Customer</th>
                            <th>Items</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Status</th>
                            <th>Date / Time</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                        @php
                            $itemsCount = $sale->items?->count() ?? 0;
                            $itemsQty = $sale->items?->sum('qty') ?? 0;
                            $badgeStyle = match($sale->payment_status) {
                                'paid' => 'status-paid',
                                'partial' => 'status-partial',
                                'unpaid' => 'status-unpaid',
                                default => 'bg-light text-secondary border'
                            };
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">#{{ $sale->invoice_no }}</div>
                                <small class="text-muted" style="font-size: 10px;">ID: {{ $sale->id }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 sale-avatar">
                                        {{ substr($sale->customer_name ?? 'W', 0, 1) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $sale->customer_name ?? 'Walk-in Customer' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $itemsCount }} item(s)</div>
                                <small class="text-muted" style="font-size: 10px;">Qty: {{ number_format($itemsQty, 2) }}</small>
                            </td>
                            <td class="text-end fw-bold text-dark" style="font-size: 15px;">
                                ₦{{ number_format($sale->total, 2) }}
                            </td>
                            <td class="text-center">
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
                                    <button class="btn btn-sm btn-white border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Action
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('sales.show', $sale->id) }}">View Items</a></li>
                                        <li><a class="dropdown-item" href="{{ route('sales.edit', $sale->id) }}">Edit Sale</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('sales.destroy', $sale->id) }}" onsubmit="return confirm('Delete this sale?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete Sale</button>
                                            </form>
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
                <h5 class="text-muted">No POS sales found.</h5>
                <a href="{{ route('pos.sales') }}" class="btn btn-soft-clear btn-sm mt-2">Clear All Filters</a>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
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
</script>
@endsection
