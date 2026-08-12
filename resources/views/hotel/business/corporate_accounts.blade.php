@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Corporate Accounts</h3>
                <p class="text-muted mb-0">B2B receivables and company-ledger relationships</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Company</th><th>Folio No</th><th>Total Charges</th><th>Total Payments</th><th>Outstanding</th></tr></thead>
                    <tbody>
                    @forelse($cityLedgers as $folio)
                        <tr>
                            <td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $folio->folio_number }}</td>
                            <td>{{ number_format((float)$folio->total_charges,2) }}</td>
                            <td>{{ number_format((float)$folio->total_payments,2) }}</td>
                            <td>{{ number_format((float)$folio->balance,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No corporate account activity found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $cityLedgers->links() }}</div>
    </div>
</div>
@endsection
