<?php $page = 'plan-billing'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* Sidebar Offset & General Layout */
    .page-wrapper {
        margin-left: 270px;
        transition: all 0.3s ease-in-out;
        background-color: #f8fafc;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }
    
    @media (max-width: 1200px) {
        .page-wrapper { margin-left: 0 !important; }
    }

    /* Plan Card Enhancements */
    .packages.card {
        border: 2px solid transparent;
        transition: all 0.3s;
        border-radius: 15px;
        overflow: hidden;
    }
    .packages.card.active {
        border-color: #007bff;
        background: #f0f7ff;
    }
    .icon-frame {
        width: 50px;
        height: 50px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-right: 15px;
    }
    .cancel-subscription {
        color: #dc3545;
        font-size: 0.85rem;
        text-decoration: underline;
    }

    /* Print Optimization */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .sidebar, .header, .owl-nav, .btn-action-icon, .cancel-subscription, .upgrade-btn { 
            display: none !important; 
        }
        .col-xl-9 { width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table-plan-billing { font-size: 12px; }
        .packages.card.active { border: 1px solid #000 !important; background: #fff !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        
        <div class="row">
            <div class="col-xl-3 col-md-4 d-print-none">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="page-header mb-3">
                            <div class="content-page-header">
                                <h5 class="fw-bold text-primary"><i class="fas fa-cog me-2"></i>Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Plan & Billing</h5>
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-print-none">
                                <i class="fas fa-print me-1"></i> Print Statement
                            </button>
                        </div>

                        @php
                            $planName = $currentSubscription->plan_name ?? $currentSubscription->plan ?? 'No Active Plan';
                            $amount = (float) ($currentSubscription->amount ?? 0);
                            $billingCycle = $currentSubscription->billing_cycle ?? 'N/A';
                            $daysRemaining = null;
                            if (!empty($currentSubscription?->end_date)) {
                                try {
                                    $daysRemaining = now()->diffInDays(\Carbon\Carbon::parse($currentSubscription->end_date), false);
                                } catch (\Throwable $e) {
                                    $daysRemaining = null;
                                }
                            }
                        @endphp
                        <div class="mb-4">
                            <div class="packages card active p-3 mb-0">
                                <div class="package-header d-sm-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="icon-frame d-flex align-items-center justify-content-center">
                                            <img src="{{ URL::asset('/assets/img/icons/basic.svg') }}" alt="plan">
                                        </span>
                                        <div>
                                            <span class="badge bg-primary mb-1">Current Plan</span>
                                            <h5 class="mb-0">{{ strtoupper((string) $planName) }}</h5>
                                            <p class="text-muted small mb-0">
                                                @if(!is_null($daysRemaining))
                                                    {{ max(0, $daysRemaining) }} days remaining
                                                @else
                                                    Validity date not set
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-sm-end mt-2 mt-sm-0">
                                        <h4 class="fw-bold text-primary mb-0">₦{{ number_format($amount, 2) }}</h4>
                                        <p class="text-muted small">{{ ucfirst((string) $billingCycle) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <h6 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Billing History</h6>
                                <div class="card-table border rounded shadow-none">
                                    <div class="card-body p-0">
                                        <div class="table-responsive table-plan-billing">
                                            <table class="table table-center table-hover mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Date</th>
                                                        <th>Details</th>
                                                        <th>Status</th>
                                                        <th class="text-end d-print-none">Download</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse (($billingHistory ?? collect()) as $plan)
                                                        <tr>
                                                            <td>#{{ $plan->id }}</td>
                                                            <td>{{ optional($plan->created_at)->format('d M Y') ?? '-' }}</td>
                                                            <td>
                                                                <span class="fw-bold text-dark">{{ strtoupper((string) ($plan->plan_name ?? $plan->plan ?? 'Plan')) }}</span>
                                                                <p class="text-muted small mb-0">{{ ucfirst((string) ($plan->billing_cycle ?? 'N/A')) }}</p>
                                                            </td>
                                                            <td>
                                                                @if(strtolower((string) ($plan->payment_status ?? '')) == 'paid')
                                                                    <span class="badge bg-success-light text-success">Paid</span>
                                                                @else
                                                                    <span class="badge bg-warning-light text-warning">{{ ucfirst((string) ($plan->payment_status ?? 'Unpaid')) }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end d-print-none">
                                                                <a class="btn-action-icon" href="javascript:void(0);" download title="Download Invoice">
                                                                    <i class="fe fe-download"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center py-4 text-muted">No billing records found.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
