@extends('layout.master')

@section('content')
<style>
    .trx-card {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 15px 35px rgba(0,35,71,0.05);
        border: 1px solid rgba(197, 160, 89, 0.2);
        overflow: hidden;
    }
    .trx-header {
        background: var(--muji-blue-deep, #002347);
        color: white;
        padding: 20px 30px;
        border-bottom: 4px solid var(--muji-gold, #c5a059);
    }
    .table thead th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #888;
        border-top: none;
    }
    .status-badge {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 5px 12px;
        border-radius: 2px;
        text-transform: uppercase;
    }
</style>

<div class="container py-5">
    <div class="trx-card" data-aos="fade-up">
        <div class="trx-header d-flex justify-content-between align-items-center">
            <div>
                <span class="gold-label" style="color: #c5a059; font-size: 10px; font-weight: 800; text-transform: uppercase;">Financial Ledger</span>
                <h4 class="mb-0 fw-bold">Transaction History</h4>
            </div>
            <button onclick="window.print()" class="btn btn-sm btn-outline-light">
                <i class="fas fa-print me-2"></i> Print Statement
            </button>
        </div>

        <div class="p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Reference</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td class="ps-4 small">{{ $trx->created_at->format('d M Y') }}</td>
                        <td class="fw-bold small text-uppercase">{{ $trx->reference }}</td>
                        <td class="small">{{ $trx->package_name ?? 'Subscription' }}</td>
                        <td class="fw-bold">₦{{ number_format($trx->amount, 2) }}</td>
                        <td class="text-end pe-4">
                            @if($trx->status == 'success' || $trx->status == 'Successful')
                                <span class="status-badge bg-success text-white">Confirmed</span>
                            @else
                                <span class="status-badge bg-warning text-dark">{{ $trx->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <p class="text-muted mb-0">No transaction records found for this account.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-top">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection