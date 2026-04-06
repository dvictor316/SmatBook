@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Receive Payment</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer->id) }}">{{ $customer->customer_name }}</a></li>
                        <li class="breadcrumb-item active">Receive Payment</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Customer
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Customer</p>
                        <h4 class="mb-0">{{ $customer->customer_name }}</h4>
                        <small class="text-muted">{{ $customer->phone ?: ($customer->email ?: 'No contact saved') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Outstanding Invoices</p>
                        <h4 class="mb-0">₦{{ number_format($outstandingInvoicesTotal, 2) }}</h4>
                        <small class="text-muted">{{ $outstandingSales->count() }} open invoice(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Opening Balance</p>
                        <h4 class="mb-0">₦{{ number_format($outstandingOpeningBalance, 2) }}</h4>
                        <small class="text-muted">Customer balance brought forward</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body">
                        <p class="mb-1 text-white-50">Total Amount Due</p>
                        <h3 class="mb-0">₦{{ number_format(($outstandingInvoicesTotal + $outstandingOpeningBalance), 2) }}</h3>
                        <small class="text-white-50">Allocate full or partial collections below</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1">Receive Payment for {{ $customer->customer_name }}</h5>
                <p class="text-muted mb-0">Enter the amount received and save. The system will allocate the payment automatically to outstanding invoices.</p>
            </div>
            <div class="card-body">
                @php $paymentHistory = $paymentHistory ?? collect(); @endphp
                <form method="POST" action="{{ route('customers.store-receive-payment', $customer->id) }}" id="customerReceivePaymentForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deposit To</label>
                            <select name="payment_target" class="form-select">
                                <option value="">Select bank or account</option>
                                @foreach($paymentDestinations as $destination)
                                    <option value="{{ $destination->value }}" @selected(old('payment_target') === $destination->value)>{{ $destination->label }} ({{ $destination->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select name="method" class="form-select">
                                @foreach(['Bank Transfer', 'Cash', 'Cheque', 'POS', 'Other'] as $method)
                                    <option value="{{ $method }}" @selected(old('method', 'Bank Transfer') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Optional reference">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount Received</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    name="received_amount"
                                    class="form-control"
                                    value="{{ old('received_amount') }}"
                                    placeholder="0.00"
                                    data-received-amount
                                >
                            </div>
                            <small class="text-muted">This will be automatically allocated to outstanding invoices.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" rows="2" class="form-control" placeholder="Optional note for this collection">{{ old('note') }}</textarea>
                        </div>
                    </div>





                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Received Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1">Payment History</h5>
                <p class="text-muted mb-0">Recent collections recorded for this customer.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Invoice</th>
                                <th>Deposit To</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentHistory as $payment)
                                <tr>
                                    <td>{{ optional($payment->created_at)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ $payment->reference ?: ($payment->payment_id ?: '-') }}</td>
                                    <td>{{ $payment->sale?->invoice_no ?: ($payment->sale ? 'Invoice #' . $payment->sale->id : 'Opening Balance') }}</td>
                                    <td>{{ $payment->account?->name ?: 'Not specified' }}</td>
                                    <td>{{ $payment->method ?: '-' }}</td>
                                    <td class="text-end fw-semibold">₦{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $payment->status ?: 'Saved' }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No payment history recorded for this customer yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
