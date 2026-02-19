@extends('layout.mainlayout')

@section('page-title', 'Invoice Details')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Invoice #{{ $invoice->id }}</h4>
        <a href="{{ route('deployment.invoices.index') }}" class="btn btn-light border">Back</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Company:</strong> {{ $invoice->company->name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Subscriber:</strong> {{ $invoice->subscriber_name ?? $invoice->user->name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Plan:</strong> {{ $invoice->plan_name ?? $invoice->plan ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Billing Cycle:</strong> {{ ucfirst($invoice->billing_cycle ?? 'N/A') }}</div>
                <div class="col-md-6"><strong>Amount:</strong> ₦{{ number_format((float)($invoice->amount ?? 0), 0) }}</div>
                <div class="col-md-6"><strong>Payment Status:</strong> {{ $invoice->payment_status ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
