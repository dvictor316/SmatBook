@extends('layout.mainlayout')

@section('page-title', 'Pay Invoice')

@section('content')
<div class="container-fluid py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($invoice)
        <div class="row g-4">
            {{-- Invoice Summary --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-file-invoice me-2 text-primary"></i>Invoice Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted" width="40%">Invoice No</td>
                                <td><strong>{{ $invoice->invoice_no ?? 'INV-'.$invoice->id }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Customer</td>
                                <td><strong>{{ $invoice->customer->name ?? $invoice->customer_name ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Invoice Total</td>
                                <td><strong>₦{{ number_format($invoice->total ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Amount Paid</td>
                                <td class="text-success"><strong>₦{{ number_format($invoice->amount_paid ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Balance Due</td>
                                <td class="text-danger"><strong>₦{{ number_format(max(0, ($invoice->total ?? 0) - ($invoice->amount_paid ?? 0)), 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>
                                    @php $ps = strtolower($invoice->payment_status ?? 'unpaid'); @endphp
                                    <span class="badge
                                        @if($ps === 'paid') bg-success
                                        @elseif($ps === 'partial') bg-warning text-dark
                                        @else bg-danger
                                        @endif">
                                        {{ ucfirst($ps) }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                        @if($invoice->items->count())
                            <h6 class="border-top pt-3 mb-2">Items</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoice->items as $item)
                                            <tr>
                                                <td>{{ optional($item->product)->name ?? $item->product_name ?? 'N/A' }}</td>
                                                <td class="text-center">{{ $item->qty }}</td>
                                                <td class="text-end">₦{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                                <td class="text-end">₦{{ number_format(($item->qty ?? 0) * ($item->unit_price ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small">No items on this invoice.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment Form --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-credit-card me-2 text-success"></i>Make Payment</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $balance = max(0, ($invoice->total ?? 0) - ($invoice->amount_paid ?? 0));
                            $isPaid  = strtolower($invoice->payment_status ?? '') === 'paid' || $balance <= 0;
                        @endphp

                        @if($isPaid)
                            <div class="alert alert-success text-center py-4">
                                <i class="fa fa-check-circle fa-3x mb-2 d-block"></i>
                                <strong>This invoice has been fully paid.</strong>
                            </div>
                        @else
                            <form action="{{ route('invoices.pay', $invoice->id) }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Amount to Pay (₦) <span class="text-danger">*</span></label>
                                    <input type="number"
                                           name="amount"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           step="0.01"
                                           min="0.01"
                                           max="{{ $balance }}"
                                           value="{{ old('amount', number_format($balance, 2, '.', '')) }}"
                                           placeholder="Enter amount">
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Balance due: ₦{{ number_format($balance, 2) }}</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="paymentMethodSelect" class="form-select @error('payment_method') is-invalid @enderror" onchange="toggleBankDetails(this.value)">
                                        <option value="">-- Select Method --</option>
                                        <option value="cash"     {{ old('payment_method') === 'cash'     ? 'selected' : '' }}>Cash</option>
                                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="pos"      {{ old('payment_method') === 'pos'      ? 'selected' : '' }}>POS / Card</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Bank account details (shown when Bank Transfer is selected) --}}
                                @php $hasBanks = isset($banks) && $banks->count() > 0; @endphp
                                <div id="bankDetailsSection" style="display:none;" class="mb-3">
                                    <div class="alert alert-info border-0 py-3 px-3" style="background:#f0f7ff;">
                                        <div class="fw-semibold mb-2" style="color:#0369a1;">
                                            <i class="fa fa-university me-1"></i> Transfer to any of our bank accounts below:
                                        </div>
                                        @if($hasBanks)
                                            @foreach($banks as $bank)
                                                <div class="mb-2 p-2 rounded" style="background:white; border:1px solid #bfdbfe;">
                                                    <div class="fw-bold text-dark">{{ $bank->name }}</div>
                                                    @if($bank->account_holder_name)
                                                        <div class="small text-muted">Acct Name: <strong>{{ $bank->account_holder_name }}</strong></div>
                                                    @endif
                                                    <div class="small text-muted">Acct No: <strong style="font-size:1.05em; color:#111;">{{ $bank->account_number }}</strong></div>
                                                    @if($bank->branch)
                                                        <div class="small text-muted">Branch: {{ $bank->branch }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="small text-muted">No bank account configured. Please contact us for payment details.</div>
                                        @endif
                                        <div class="small mt-2 text-muted">Use the invoice number <strong>{{ $invoice->invoice_no ?? '' }}</strong> as payment reference.</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Notes <span class="text-muted">(optional)</span></label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any payment reference or note...">{{ old('notes') }}</textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-check me-1"></i> Confirm Payment
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-danger">Invoice not found or invalid link.</div>
    @endif

    <div class="mt-4">
        <a href="{{ url()->previous() }}" class="btn btn-light border me-2">
            <i class="fa fa-arrow-left me-1"></i> Go Back
        </a>
        <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
    </div>
</div>

<script>
function toggleBankDetails(val) {
    var el = document.getElementById('bankDetailsSection');
    if (el) el.style.display = (val === 'transfer') ? 'block' : 'none';
}
// Run on page load to handle old() value after form validation failure
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('paymentMethodSelect');
    if (sel) toggleBankDetails(sel.value);
});
</script>
@endsection

