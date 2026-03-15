@php
    $page = 'quotation-report';
    $reportDate = date('d-M-Y');
    $quotationExists = isset($quotationreports) && $quotationreports->count() > 0;
    $grandTotal = $quotationreports->sum('total');
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    .page-wrapper { background-color: #f4f7f6; }
    .pagination { margin-bottom: 0; }
    .page-link { padding: 0.5rem 0.85rem; color: #6366f1; }
    .page-item.active .page-link { background-color: #6366f1; border-color: #6366f1; }
    
    @media print {
        .no-print, .pagination-container, .dt-buttons { display: none !important; }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ __('Quotation Report') }}</h3>
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill">
                        <i class="feather-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Quotation Report',
            'periodLabel' => request('from_date') || request('to_date')
                ? 'Filtered Quotation Window'
                : 'All Recorded Quotations',
        ])

        {{-- Filter Card --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body">
                <form action="{{ \Illuminate\Support\Facades\Route::has('reports.quotation') ? route('reports.quotation') : route('quotation') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="small fw-bold">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Quotation ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotationreports as $quotation)
                            @php
                                $customerName = $quotation->customer->name
                                    ?? $quotation->customer->customer_name
                                    ?? 'Walk-in Customer';
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $quotation->quotation_id }}</td>
                                <td>{{ $customerName }}</td>
                                <td>{{ \Carbon\Carbon::parse($quotation->created_at)->format('d M, Y') }}</td>
                                <td class="fw-bold text-dark">₦{{ number_format($quotation->total, 2) }}</td>
                                <td>
                                    <span class="badge {{ $quotation->status == 'Sent' ? 'bg-info' : 'bg-secondary' }}">
                                        {{ $quotation->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No quotations found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Bootstrap 5.3 Pagination --}}
                @if($quotationExists)
                <div class="card-footer bg-white border-top-0 pt-0 pb-4 no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Showing {{ $quotationreports->firstItem() }} to {{ $quotationreports->lastItem() }} of {{ $quotationreports->total() }}
                        </div>
                        <div class="pagination-container">
                            {{ $quotationreports->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
