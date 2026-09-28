@extends('layout.mainlayout')

@section('content')
@php
    $companyName = $company?->name ?? $company?->company_name ?? auth()->user()->name ?? 'your workspace';
    $expiryDate = optional($subscription?->end_date)->format('d M, Y') ?? 'N/A';
    $planLabel = $subscription?->planLabel() ?? 'Current';
@endphp
<div class="main-wrapper">
    <div class="account-content">
        <div class="container">
            <div class="account-logo text-center mb-4">
                <a href="{{ url('/') }}"><img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook"></a>
            </div>

            <div class="account-box shadow-lg border-0">
                <div class="account-wrapper p-5 text-center">
                    <div class="display-1 text-danger mb-3">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 class="account-title">{{ $isExpiredTrial ? 'Free Trial Ended' : 'Plan Expired' }}</h3>
                    <p class="text-muted">
                        Access to <strong>{{ $companyName }}</strong> is currently paused because your
                        <strong>{{ $planLabel }}</strong> plan ended on
                        <span class="text-danger font-weight-bold">
                            {{ $expiryDate }}
                        </span>.
                    </p>

                    <div class="renewal-actions mt-4 p-4 bg-light rounded border">
                        <h5>Workspace subscription required</h5>
                        <p class="small text-muted mb-0">Please contact the business owner to renew the workspace. Your records remain safely stored while access is paused.</p>
                    </div>

                    <div class="mt-4 d-flex justify-content-center gap-3">

                        <button onclick="window.print();" class="btn btn-outline-secondary btn-rounded">
                            <i class="fas fa-print"></i> Print Expiry Notice
                        </button>

                        <a href="{{ route('logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                           class="btn btn-link text-muted">
                            Switch Account
                        </a>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .account-content {
        background:
            radial-gradient(circle at 0 0, rgba(215, 169, 40, 0.16) 0%, transparent 34%),
            radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.12) 0%, transparent 35%),
            #f7faff;
    }

    .account-box {
        border: 1px solid #d8e3f5 !important;
        box-shadow: 0 24px 54px -34px rgba(6, 26, 68, 0.55) !important;
    }

    .account-title {
        color: #061a44;
    }

    .renewal-actions {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%) !important;
        border-color: rgba(215, 169, 40, .42) !important;
    }

    .renewal-actions h5 {
        color: #061a44;
        font-weight: 800;
    }

    .renewal-actions .btn-primary {
        background: linear-gradient(135deg, #061a44, #0f3a8a);
        border-color: #0f3a8a;
        color: #fff;
    }

    .renewal-actions .btn-primary:hover,
    .support-context .btn-outline-info:hover,
    .btn-outline-secondary:hover {
        background: #fff;
        border-color: #d7a928;
        color: #0f3a8a;
    }

    .btn-outline-secondary,
    .support-context .btn-outline-info {
        border-color: #0f3a8a;
        color: #0f3a8a;
        background: #fff;
        font-weight: 700;
    }

    @media print {
        .btn, .account-logo, .support-context, hr {
            display: none !important;
        }
        .account-box {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }
        .text-danger { color: #000 !important; font-weight: bold; }
    }
</style>
@endsection
