@extends('layout.mainlayout')

@section('page-title', 'Deployment Invoices')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Invoices</h4>
        <div class="d-flex gap-2">
            <a href="#" class="download-item btn btn-outline-primary">Download CSV</a>
            <button onclick="window.print()" class="btn btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
            <a href="{{ route('deployment.invoices.create') }}" class="btn btn-primary">Create Invoice</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Company</th><th>Plan</th><th>Amount</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>#{{ $invoice->id }}</td>
                            <td>{{ $invoice->company->name ?? 'N/A' }}</td>
                            <td>{{ $invoice->plan_name ?? $invoice->plan ?? 'N/A' }}</td>
                            <td>₦{{ number_format((float)($invoice->amount ?? 0), 0) }}</td>
                            <td>{{ strtoupper($invoice->payment_status ?? 'N/A') }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('deployment.invoices.view', $invoice->id) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($invoices, 'links'))
        <div class="mt-3">{{ $invoices->links() }}</div>
    @endif
</div>
</div>
@endsection
