<?php $page = 'purchase-details'; ?>
@extends('layout.mainlayout')

@section('content')
    @php
        $totalAmount = abs((float) ($purchase->resolved_total_amount ?? $purchase->total_amount ?? 0));
        $paidAmount = (float) ($purchase->paid_amount ?? 0);
        $balanceAmount = max(0, $totalAmount - $paidAmount);
        $taxAmount = abs((float) ($purchase->tax_amount ?? 0));
        $subTotalAmount = max(0, $totalAmount - $taxAmount);
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Purchase Details</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{url('index')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('purchases')}}">Purchases</a></li>
                            <li class="breadcrumb-item active">Details</li>
                        </ul>
                        <div class="mt-2">
                            <span class="badge bg-light border text-primary px-3 py-2">
                                <i class="fas fa-code-branch me-2"></i>
                                Active Branch: {{ $purchase->branch_label ?? $activeBranch['name'] ?? 'Workspace Default' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-print-none">
                            <button onclick="window.print()" class="btn btn-white text-black border me-1">
                                <i class="fe fe-printer"></i> Print
                            </button>
                            <button onclick="generatePDF()" class="btn btn-white text-black border me-1">
                                <i class="fe fe-file-text"></i> PDF
                            </button>
                            <button onclick="generateExcel()" class="btn btn-white text-black border">
                                <i class="fe fe-file"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center" id="invoice-content" data-print-scope>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-table">
                                <div class="card-body">
                                    
                                    <div class="invoice-item invoice-item-one">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="invoice-logo">
                                                    <img src="{{ asset($logo) }}" alt="logo" style="max-height: 70px;">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="invoice-info text-md-end">
                                                    <h1 class="text-uppercase text-primary">Purchase</h1>
                                                    <p class="mb-0">Ref: <strong>{{ $purchase->purchase_no }}</strong></p>
                                                    <p class="mb-0">Branch: <strong>{{ $purchase->branch_label ?? 'Workspace Default' }}</strong></p>
                                                    <p>Status: 
                                                        <span class="badge {{ $purchase->status == 'paid' ? 'bg-success-light' : 'bg-warning-light' }}">
                                                            {{ ucfirst($purchase->status ?? 'Pending') }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-item invoice-item-date border-bottom mb-3 pb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="text-start invoice-details">
                                                    Date<span>: </span><strong>{{ $purchase->created_at->format('d M, Y') }}</strong>
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="invoice-info">
                                                    <strong class="customer-text-one">Supplier:</strong>
                                                    <p class="invoice-details-two">
                                                        {{ $purchase->supplier->name ?? $purchase->vendor->name ?? 'N/A' }}<br>
                                                        {{ $purchase->supplier->address ?? $purchase->vendor->address ?? 'No Address Provided' }}<br>
                                                        {{ $purchase->supplier->email ?? $purchase->vendor->email ?? '' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="invoice-info text-md-end">
                                                    <strong class="customer-text-one">Payment Bank:</strong>
                                                    <p class="invoice-details-two">
                                                        {{ $purchase->bank->name ?? 'Cash/Other' }}<br>
                                                        {{ $purchase->bank->account_number ?? '' }}<br>
                                                        {{ $purchase->bank->account_holder_name ?? '' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-item invoice-table-wrap">
                                        <div class="table-responsive">
                                            <table class="table table-center table-hover mb-4" id="items-table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Qty</th>
                                                        <th>Rate</th>
                                                        <th>Tax</th>
                                                        <th class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($purchase->items as $item)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                            <p class="small text-muted mb-0">{{ $item->product->sku ?? '' }}</p>
                                                        </td>
                                                        <td class="text-center">{{ $item->qty }}</td>
                                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                                        <td>{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                                                        <td class="text-end font-weight-bold">{{ number_format($item->total_amount, 2) }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="5" class="text-center p-4">No items recorded.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-7 col-md-6">
                                            <div class="invoice-terms">
                                                <h6>Notes:</h6>
                                                <p class="mb-0 text-muted">{{ $purchase->notes ?? 'No special notes.' }}</p>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-6">
                                            <div class="invoice-total-card">
                                                <div class="invoice-total-box">
                                                    <div class="invoice-total-inner">
                                                        <p>Subtotal <span>{{ number_format($subTotalAmount, 2) }}</span></p>
                                                        <p>Tax <span>{{ number_format($taxAmount, 2) }}</span></p>
                                                        <p>Amount Paid <span>{{ number_format($paidAmount, 2) }}</span></p>
                                                        <p>Balance Due <span>{{ number_format($balanceAmount, 2) }}</span></p>
                                                    </div>
                                                    <div class="invoice-total-footer bg-light p-2">
                                                        @if($balanceAmount > 0)
                                                            <h4 class="mb-0">Purchase Value <span>{{ number_format($totalAmount, 2) }}</span></h4>
                                                        @else
                                                            <h4 class="mb-0 text-success">Settled in Full <span>₦0.00 Due</span></h4>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-lg-7 col-md-6">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body">
                                                    <h5 class="mb-3">Record Payment</h5>
                                                    <form method="POST" action="{{ route('purchases.record-payment', $purchase->id) }}">
                                                        @csrf
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Amount</label>
                                                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control" value="{{ old('amount', $balanceAmount) }}" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Payment Account</label>
                                                                <select name="payment_account_id" class="form-select">
                                                                    <option value="">Cash/Other</option>
                                                                    @foreach($banks as $bank)
                                                                        <option value="{{ $bank->id }}">{{ $bank->name ?? $bank->bank_name ?? 'Bank' }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Reference</label>
                                                                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" maxlength="191">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Notes</label>
                                                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="500">
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-3">
                                                            <div class="me-auto">
                                                                <span class="text-muted">Paid:</span>
                                                                <strong>{{ number_format($paidAmount, 2) }}</strong>
                                                                <span class="text-muted ms-3">Balance:</span>
                                                                <strong>{{ number_format($balanceAmount, 2) }}</strong>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary">Record Payment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-6">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body">
                                                    <h5 class="mb-3">Payment History</h5>
                                                    @forelse(($purchase->supplierPayments ?? collect())->sortByDesc('payment_date') as $payment)
                                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                            <div>
                                                                <div class="fw-semibold">{{ $payment->reference ?? ('PAY-' . $payment->id) }}</div>
                                                                <small class="text-muted">
                                                                    {{ optional($payment->payment_date)->format('d M Y') ?? optional($payment->created_at)->format('d M Y') }}
                                                                    @if(!empty($payment->method))
                                                                        • {{ $payment->method }}
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="fw-bold text-success">{{ number_format((float) ($payment->amount ?? 0), 2) }}</div>
                                                                <form method="POST" action="{{ route('purchases.destroy-payment', [$purchase->id, $payment->id]) }}" onsubmit="return confirm('Delete this payment and reverse its purchase payment entry?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="text-muted">No payments recorded yet.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-sign text-end mt-5">
                                        <p class="mb-0">Authorized By</p>
                                        <img src="{{ asset('assets/img/signature.png') }}" alt="sign" width="120">
                                        <span class="d-block mt-2">{{ auth()->user()->name ?? 'Administrator' }}</span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            
            <script>
        function generatePDF() {
            window.smartProbookExportElementToPdf('#invoice-content', {
                filename: 'Purchase_{{ $purchase->purchase_no }}.pdf',
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait',
                margin: 10,
            });
        }

        function generateExcel() {
            let table = document.getElementById("items-table");
            let html = table.outerHTML;
            let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            let link = document.createElement("a");
            link.download = "Purchase_{{ $purchase->purchase_no }}.xls";
            link.href = url;
            link.click();
        }
    </script>

    <style>
        @media print {
            .sidebar, .header, .page-header .d-print-none, .breadcrumb { display: none !important; }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            .card { border: none !important; }
        }
    </style>
@endsection
