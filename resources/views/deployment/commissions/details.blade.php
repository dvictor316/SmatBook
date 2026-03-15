@extends('layout.mainlayout')

@section('page-title', 'Commission Details')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Commission Details</h4>
            <p class="text-muted mb-0">Review the subscription, client, and payout state tied to this commission.</p>
        </div>
        <a href="{{ route('deployment.commissions.index') }}" class="btn btn-light border">Back to Commissions</a>
    </div>

    @if($commission)
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><strong>Client:</strong> {{ $commission->company_name ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Workspace:</strong> {{ $commission->company_domain ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Plan:</strong> {{ $commission->plan ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Billing Cycle:</strong> {{ ucfirst($commission->billing_cycle ?? 'N/A') }}</div>
                            <div class="col-md-6"><strong>Payment Status:</strong> {{ ucfirst($commission->payment_status ?? 'N/A') }}</div>
                            <div class="col-md-6"><strong>Commission Status:</strong> {{ ucfirst($commission->status ?? 'pending') }}</div>
                            <div class="col-md-6"><strong>Subscription Start:</strong> {{ !empty($commission->start_date) ? \Illuminate\Support\Carbon::parse($commission->start_date)->format('d M Y') : 'N/A' }}</div>
                            <div class="col-md-6"><strong>Subscription End:</strong> {{ !empty($commission->end_date) ? \Illuminate\Support\Carbon::parse($commission->end_date)->format('d M Y') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-uppercase text-muted fw-bold mb-2">Commission Amount</div>
                        <h2 class="fw-bold text-success mb-3">₦{{ number_format((float) ($commission->commission_amount ?? $commission->amount ?? 0), 2) }}</h2>
                        <div class="small text-muted mb-2">Created</div>
                        <div class="fw-semibold">{{ !empty($commission->created_at) ? \Illuminate\Support\Carbon::parse($commission->created_at)->format('d M Y, h:i A') : 'N/A' }}</div>
                        @if(!empty($commission->updated_at))
                            <div class="small text-muted mt-3 mb-2">Last Updated</div>
                            <div class="fw-semibold">{{ \Illuminate\Support\Carbon::parse($commission->updated_at)->format('d M Y, h:i A') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body py-5 text-center text-muted">
                Commission record not found.
            </div>
        </div>
    @endif
</div>
</div>
@endsection
