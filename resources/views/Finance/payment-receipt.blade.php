@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Payment Receipt</h4>
                    <button class="btn btn-primary" onclick="window.print()">Print</button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Reference:</strong> {{ $payment->payment_id ?? ('PAY-' . $payment->id) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Date:</strong> {{ optional($payment->created_at)->format('d M Y H:i') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Method:</strong> {{ $payment->method ?? 'N/A' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Sale:</strong> {{ $payment->sale?->invoice_no ?? 'Direct Entry' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Created By:</strong> {{ $payment->creator?->name ?? 'System' }}
                    </div>
                    <div class="col-12">
                        <strong>Note:</strong> {{ $payment->note ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
