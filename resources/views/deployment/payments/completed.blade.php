@extends('layout.mainlayout')

@section('page-title', 'Completed Payments')

@section('content')
<div class="sb-shell" id="payments-wrapper">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Completed Payments</h4>
            <div class="d-flex gap-2">
                <a href="#" class="download-item btn btn-sm btn-outline-primary">Download CSV</a>
                <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
                <a href="{{ route('deployment.payments.index') }}" class="btn btn-sm btn-outline-dark">
                    <i class="fas fa-arrow-left me-1"></i> All Payments
                </a>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Company</th><th>Amount</th><th>Paid At</th></tr></thead>
                    <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td>#{{ $p->id }}</td>
                            <td>{{ $p->company->name ?? 'N/A' }}</td>
                            <td>₦{{ number_format((float)($p->amount ?? 0), 0) }}</td>
                            <td>{{ !empty($p->paid_at) ? \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No completed payments.</td></tr>
                    @endforelse
                    </tbody>
                </table>
        </div>
        @if(method_exists($payments, 'links'))
            <div class="card-footer bg-white">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
