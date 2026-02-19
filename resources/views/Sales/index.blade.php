@extends('layout.mainlayout')

@section('content')
<style>
    /* Dynamic Sidebar Adjustment */
    .pos-content-area {
        margin-left: 250px; 
        padding: 30px;
        transition: all 0.3s ease-in-out;
        background-color: #fdfaf0; 
        min-height: 100vh;
        margin-top: 60px;
    }

    body.mini-sidebar .pos-content-area { margin-left: 80px; }

    @media (max-width: 1200px) {
        .pos-content-area { margin-left: 0 !important; padding: 15px; }
    }

    /* Professional Header & Search */
    .report-header {
        border-left: 5px solid #d4af37;
        padding-left: 15px;
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
        height: 40px;
        border-radius: 8px;
        border: 1.5px solid #d4af37;
        padding-left: 35px;
        font-size: 13px;
        background: #fff;
    }

    .header-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #996515;
    }

    .filter-card {
        background: #ffffff;
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }

    .sales-table-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .table thead th {
        background-color: #0369a1; 
        color: #ffffff;
        text-transform: uppercase;
        font-size: 11px;
        padding: 15px;
        border: none;
    }

    .status-badge {
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 10px;
    }

    .btn-gold {
        background-color: #996515;
        color: white;
        border: none;
    }
    .btn-gold:hover { background-color: #d4af37; color: white; }
</style>

<div class="pos-content-area">
    {{-- Page Header with Quick ID Search --}}
    <div class="report-header">
        <div>
            <h3 class="fw-bold mb-0" style="color: #0369a1;">Sales Transactions</h3>
            <p class="text-muted small mb-0">Manage history and track invoice statuses.</p>
        </div>
        
        <div class="d-none d-md-flex gap-3 align-items-center">
            {{-- THE QUICK SEARCH BOX --}}
            <div class="header-search-container">
                <i class="fas fa-search header-search-icon"></i>
                <input type="text" id="quick-invoice-id-search" class="form-control header-search-input" placeholder="Quick Search Invoice ID...">
            </div>
            
            <span class="badge bg-white text-dark border p-2 shadow-sm">
                <i class="fas fa-calendar-alt text-warning me-2"></i>{{ date('D, M d, Y') }}
            </span>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card filter-card mb-4">
        <div class="card-body p-4">
            <form action="{{ route('sales.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Invoice No</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-hashtag text-warning"></i></span>
                        <input type="text" name="invoice_no" class="form-control border-start-0" placeholder="e.g. INV-100" value="{{ request('invoice_no') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Customer</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-warning"></i></span>
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
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="card sales-table-card">
        <div class="card-body p-0">
            @if($sales->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Invoice Info</th>
                            <th>Customer Name</th>
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
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; background: #f0f9ff; color: #0369a1; font-size: 11px; font-weight: bold;">
                                        {{ substr($sale->customer_name ?? 'W', 0, 1) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $sale->customer_name ?? 'Walk-in Customer' }}</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-primary" style="font-size: 15px;">
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
                                <div class="btn-group shadow-sm border rounded">
                                    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-white text-primary" title="View Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-sm btn-white text-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
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
                <a href="{{ route('sales.index') }}" class="btn btn-gold btn-sm mt-2">Clear All Filters</a>
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
                    // Redirect directly to the show route for that ID
                    let url = "{{ route('sales.show', ':id') }}";
                    window.location.href = url.replace(':id', invoiceId);
                }
            }
        });
    });
</script>
@endsection