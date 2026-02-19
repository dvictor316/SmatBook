@extends('layout.mainlayout')

@section('page-title', 'Payment Details')

@section('content')
<div class="sb-shell" id="payments-wrapper">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Payment Details</h4>
            <a href="{{ route('deployment.payments.index') }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> All Payments
            </a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Subscription ID:</strong> {{ $payment->id }}</div>
                    <div class="col-md-6"><strong>Company:</strong> {{ $payment->company->name ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Amount:</strong> ₦{{ number_format((float)($payment->amount ?? 0), 0) }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ $payment->payment_status ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Reference:</strong> {{ $payment->transaction_reference ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Paid At:</strong> {{ $payment->paid_at ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
