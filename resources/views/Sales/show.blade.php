@extends('layout.mainlayout')

@section('content')
<style>
    .pos-content-area {
        padding: 24px;
        background: #f6f8fc;
        min-height: 100vh;
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

    .invoice-fit-container {
        width: 100%;
        max-width: 950px; 
        margin: 0 auto; 
    }

    .inv-header {
        background: #f5f8ff;
        padding: 14px 20px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #dfe8f7;
    }
    
    .inv-header h5, 
    .inv-header .inv-date {
        color: #0f172a !important;
        margin-bottom: 0 !important;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .show-card { 
        border: 1px solid #e3eaf4;
        border-radius: 20px; 
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        background: #fff;
    }
    
    .stat-box { 
        padding: 15px; 
        border: 1px solid #e6edf7;
        border-radius: 16px;
        text-align: center;
        background: #fff;
    }
    .stat-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .stat-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; }

    .table thead th {
        background-color: #f5f8ff; 
        color: #5b6b87;
        text-transform: uppercase;
        font-size: 11px;
        padding: 12px;
        letter-spacing: .04em;
    }

    .badge-soft-branch {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    @media print {
        .no-print { display: none !important; }
        .pos-content-area { padding: 0 !important; background: white !important; }
    }
</style>

<div class="pos-content-area">
    <div class="invoice-fit-container">
        
        {{-- Show View Header --}}
        <div class="report-header no-print">
            <div>
                <h3 class="fw-bold mb-0" style="color: #0f172a;">Sale Details</h3>
                <p class="text-muted small mb-0">Invoice Information & Payment Tracking</p>
                <div class="mt-2">
                    <span class="badge badge-soft-branch px-3 py-2">
                        <i class="fas fa-code-branch me-2 text-primary"></i>
                        Active Branch: {{ $sale->branch_label ?? $activeBranch['name'] ?? 'Workspace Default' }}
                    </span>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary px-3 shadow-sm">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-money-bill-wave me-1"></i> Record Pay
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary px-3 shadow-sm">Back</a>
            </div>
        </div>

        <div class="card show-card">
            {{-- Slim Header with White Font --}}
            <div class="inv-header">
                <h5>INVOICE #{{ $sale->invoice_no }}</h5>
                <span class="inv-date">Date: {{ $sale->created_at->format('M d, Y') }}</span>
            </div>

            <div class="card-body p-4">
                @php 
                    $appliedAmount = (float) ($sale->amount_paid ?? $sale->paid ?? $sale->payments->sum('amount'));
                    $tenderedAmount = $appliedAmount + max(0, (float) ($sale->change_amount ?? 0));
                    $displayBalance = max(0, (float) $sale->total - $appliedAmount);
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-label">Customer</div>
                            <div class="stat-value text-dark" style="font-size: 0.9rem;">{{ $sale->customer_name }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-label">Branch</div>
                            <div class="stat-value text-dark" style="font-size: 0.9rem;">{{ $sale->branch_label ?? 'Workspace Default' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-label">Grand Total</div>
                            <div class="stat-value">₦{{ number_format($sale->total, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box border-success" style="background-color: #f0fdf4;">
                            <div class="stat-label text-success">Tendered</div>
                            <div class="stat-value text-success">₦{{ number_format($tenderedAmount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box border-success" style="background-color: #ecfdf5;">
                            <div class="stat-label text-success">Applied</div>
                            <div class="stat-value text-success">₦{{ number_format($appliedAmount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box border-danger" style="background-color: #fef2f2;">
                            <div class="stat-label text-danger">Balance</div>
                            <div class="stat-value text-danger">₦{{ number_format($displayBalance, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box border-info" style="background-color: #eff6ff;">
                            <div class="stat-label text-primary">Change</div>
                            <div class="stat-value text-primary">₦{{ number_format($sale->change_amount ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4 shadow-sm rounded">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Item Name</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                            <tr>
                                <td class="ps-3 fw-bold text-dark">{{ $item->product->name }}</td>
                                <td class="text-center">{{ $item->qty }}</td>
                                <td class="text-end pe-3 fw-bold">₦{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history me-2 text-primary"></i>Payment Logs</h6>
                        <div class="ps-3 border-start border-3 border-info">
                            @forelse($sale->payments as $payment)
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <span class="small fw-bold d-block">{{ $payment->created_at->format('d M, Y') }}</span>
                                    <span class="badge bg-light text-muted border" style="font-size: 9px;">{{ strtoupper($payment->method) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-success fw-bold">₦{{ number_format($payment->amount, 2) }}</span>
                                    <form action="{{ route('payments.destroy', $payment->id) }}" method="POST" class="no-print">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link text-danger p-0" onclick="return confirm('Delete this log?')"><i class="fas fa-trash-alt fa-xs"></i></button>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-light py-2 small border">No history found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="badge {{ $sale->payment_status == 'paid' ? 'bg-success' : 'bg-danger' }} px-3 py-2 text-uppercase shadow-sm">
                        {{ $sale->payment_status }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold">Record Payment</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                <div class="modal-body p-4 text-center">
                    <small class="text-muted d-block fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Remaining Due</small>
                    <h4 class="fw-bold text-danger mb-4">₦{{ number_format($displayBalance, 2) }}</h4>
                    
                    <div class="text-start mb-3">
                        <label class="small fw-bold">Amount</label>
                        <input type="number" name="amount" step="0.01" max="{{ $displayBalance }}" class="form-control" required>
                    </div>
                    <div class="text-start mb-4">
                        <label class="small fw-bold">Method</label>
                        <select name="method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="pos">POS</option>
                        </select>
                    </div>
                    <div class="text-start mb-4">
                        <label class="small fw-bold">Payment Channel</label>
                        <select name="payment_account_id" class="form-select">
                            <option value="">Auto / Not specified</option>
                            @foreach(($bankAccounts ?? []) as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose the bank, POS terminal, wallet, or other collection channel that received this payment.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
