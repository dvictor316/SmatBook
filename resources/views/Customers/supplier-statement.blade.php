@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h5>Supplier Statement</h5>
                    <p class="text-muted mb-0">Chronological payable statement for {{ $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name ?? 'Supplier' }}.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('suppliers.pay', $supplier->id) }}" class="btn btn-outline-primary">
                        <i class="fe fe-credit-card me-1"></i>Pay Supplier
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="fe fe-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted mb-1">Total Billed</div><h4 class="mb-0">₦{{ number_format((float) ($summary['total_billed'] ?? 0), 2) }}</h4></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted mb-1">Total Paid</div><h4 class="mb-0">₦{{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</h4></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted mb-1">Balance Due</div><h4 class="mb-0">₦{{ number_format((float) ($summary['balance_due'] ?? 0), 2) }}</h4></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                                    <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $entry['type']) }}</span></td>
                                    <td>{{ $entry['reference'] }}</td>
                                    <td>{{ $entry['description'] }}</td>
                                    <td class="text-end">₦{{ number_format((float) ($entry['debit'] ?? 0), 2) }}</td>
                                    <td class="text-end">₦{{ number_format((float) ($entry['credit'] ?? 0), 2) }}</td>
                                    <td class="text-end fw-semibold">₦{{ number_format((float) ($entry['balance'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No supplier statement entries available yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
