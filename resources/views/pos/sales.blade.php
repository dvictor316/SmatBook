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
            <div class="header-search-container">
                <i class="fas fa-search header-search-icon"></i>
                <input type="text" id="quick-invoice-id-search" class="form-control header-search-input" placeholder="Quick Search Invoice ID...">
            </div>
            <span class="badge bg-white text-dark border p-2 shadow-sm">
                <i class="fas fa-calendar-alt text-primary me-2"></i>{{ date('D, M d, Y') }}
            </span>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body p-4">
            <form action="{{ route('pos.sales') }}" method="GET" class="row g-3 align-items-end">
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('pos.sales') }}" class="btn btn-outline-secondary btn-sm">
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
