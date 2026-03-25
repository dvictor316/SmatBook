// Verification of session domain logic:
// domain => env('SESSION_DOMAIN', null)

@extends('layout.mainlayout')

@section('content') 
@php 
    $page = 'subscription'; 
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Subscription Details</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.subscriptions.transactions') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Purchase #{{ $subscription->id }}</li>
                    </ul>
                </div>
                <div class="col-auto float-right ml-auto">
                    <div class="btn-group btn-group-sm">
                        <button onclick="window.print();" class="btn btn-white">
                            <i class="fa fa-print fa-lg text-primary"></i> Print Receipt
                        </button>
                        <a href="{{ route('purchases.pdf', $subscription->id) }}" class="btn btn-white">
                            <i class="fa fa-file-pdf-o fa-lg text-danger"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h2 class="text-primary" style="font-weight: 800;">SmartProbook</h2>
                                <ul class="list-unstyled mt-2">
                                    <li><strong>SmartProbook SaaS Platform</strong></li>
                                    <li>Onitsha, Anambra State, Nigeria</li>
                                    <li>Support: chat@smartprobook.com</li>
                                </ul>
                            </div>
                            <div class="col-sm-6 text-sm-right">
                                <div class="invoice-details">
                                    <h3 class="text-uppercase text-muted">Receipt #{{ $subscription->id }}</h3>
                                    <ul class="list-unstyled">
                                        <li>Billing Date: <span class="text-dark">{{ $subscription->created_at->format('d M Y') }}</span></li>
                                        <li>Status: <span class="badge {{ $subscription->status_badge ?? 'bg-success-light' }}" style="padding: 8px 12px; font-size: 0.85rem;">{{ strtoupper($subscription->status) }}</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <hr>

                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <h5 class="card-title">Subscriber Information</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="text-muted mb-0">Tenant Name</p>
                                        <p class="h6">{{ $subscription->tenant->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-0">Workspace Slug</p>
                                        <p class="h6 text-primary">{{ $subscription->domain_prefix }}.{{ config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')) }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-0">Payment Method</p>
                                        <p class="h6">{{ strtoupper($subscription->payment_gateway ?? 'Paystack') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-nowrap table-hover table-center mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>Cycle</th>
                                        <th>Start Date</th>
                                        <th>Expiry Date</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>
                                            <h5 class="mb-0">{{ $subscription->plan_name }} Plan</h5>
                                            <small class="text-muted">Software-as-a-Service License</small>
                                        </td>
                                        <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                        <td>{{ $subscription->start_date ? $subscription->start_date->format('d M Y') : 'N/A' }}</td>
                                        <td><span class="text-danger font-weight-bold">{{ $subscription->end_date ? $subscription->end_date->format('d M Y') : 'N/A' }}</span></td>
                                        <td class="text-right">₦{{ number_format($subscription->amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-7">
                                <div class="alert alert-light border">
                                    <p class="mb-1"><strong>Support Context:</strong></p>
                                    <p class="small text-muted mb-0">This receipt confirms access for the tenant workspace. For any queries regarding this transaction, please use the <strong>Chat and Message</strong> feature in your dashboard for immediate assistance.</p>
                                </div>
                            </div>
                            <div class="col-sm-5 col-lg-4 ml-auto">
                                <table class="table table-bordered text-right">
                                    <tbody>
                                        <tr>
                                            <th class="text-left bg-light">Subtotal</th>
                                            <td>₦{{ number_format($subscription->amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left bg-light">Tax (0%)</th>
                                            <td>₦0.00</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left bg-primary text-white">Grand Total</th>
                                            <td class="bg-primary text-white"><strong>₦{{ number_format($subscription->amount, 2) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Universal print logic for the purchase view
    window.onbeforeprint = function() {
        console.log("Preparing print documentation for Domain Prefix: {{ $subscription->domain_prefix }}");
    };

    // Auto-active sidebar logic for "subscription" menu item
    document.addEventListener("DOMContentLoaded", function() {
        const activeLink = document.querySelector('.sidebar-menu a[href*="subscription"]');
        if (activeLink) {
            activeLink.closest('li').classList.add('active');
            const parentLi = activeLink.closest('.submenu');
            if (parentLi) {
                parentLi.style.display = 'block';
            }
        }
    });
</script>
@endsection
