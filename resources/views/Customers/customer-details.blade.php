@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper bg-light">
    <div class="content container-fluid">
        
        {{-- Page Header --}}
        <div class="page-header no-print">
            <div class="content-page-header d-flex justify-content-between align-items-center">
                <h5 class="fw-black text-dark">Customer Profile: <span class="text-primary">{{ $customer->customer_name }}</span></h5>
                <div class="list-btn">
                    <ul class="filter-list">
                        <li>
                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary btn-sm rounded-pill me-2">
                                <i class="fe fe-edit me-2"></i>Edit Profile
                            </a>
                        </li>
                        <li>
                            <button onclick="window.print()" class="btn btn-white btn-sm border rounded-pill">
                                <i class="fe fe-printer me-2"></i>Print Dossier
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Top Summary Card --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-xl-3 col-lg-4 col-md-6 border-end-md">
                        <div class="d-flex align-items-center">
                            <span class="customer-widget-img d-inline-flex">
                                <img class="rounded-circle border border-2 border-primary-light" 
                                     src="{{ $customer->image ? asset('storage/' . $customer->image) : asset('assets/img/profiles/avatar-14.jpg') }}" 
                                     alt="profile-img" 
                                     style="width:70px; height:70px; object-fit:cover;">
                            </span>
                            <div class="ms-3">
                                <h6 class="mb-1 fw-bold">{{ $customer->customer_name }}</h6>
                                <span class="badge {{ $customer->status == 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} rounded-pill">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 border-end-md mt-3 mt-md-0 px-md-4">
                        <h6 class="small text-muted text-uppercase fw-bold mb-1">Contact Info</h6>
                        <p class="text-dark mb-0"><i class="fe fe-mail me-2 text-primary"></i>{{ $customer->email }}</p>
                        <p class="text-dark mb-0"><i class="fe fe-phone me-2 text-success"></i>{{ $customer->phone }}</p>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 border-end-md mt-3 mt-lg-0 px-md-4">
                        <h6 class="small text-muted text-uppercase fw-bold mb-1">Financials</h6>
                        <p class="text-dark mb-0">Currency: <strong>{{ $customer->currency }}</strong></p>
                        <p class="text-dark mb-0">Website: <a href="{{ $customer->website }}" target="_blank" class="text-primary">{{ $customer->website ?? 'N/A' }}</a></p>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 mt-3 mt-xl-0 px-md-4">
                        <h6 class="small text-muted text-uppercase fw-bold mb-1">Customer ID</h6>
                        <p class="h5 fw-bold text-primary mb-0">#CL-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Address Details --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold mb-3 text-primary"><i class="fe fe-map-pin me-2"></i>Billing Address</h6>
                                <p class="mb-1"><strong>{{ $customer->billing_name }}</strong></p>
                                <p class="text-muted small mb-0">
                                    {{ $customer->billing_address_line1 }}<br>
                                    {{ $customer->billing_address_line2 }}
                                    {{ $customer->billing_city }}, {{ $customer->billing_state }}<br>
                                    {{ $customer->billing_country }} - {{ $customer->billing_pincode }}
                                </p>
                            </div>
                            <div class="col-md-6 ps-md-4 mt-4 mt-md-0">
                                <h6 class="fw-bold mb-3 text-orange"><i class="fe fe-truck me-2"></i>Shipping Address</h6>
                                <p class="mb-1"><strong>{{ $customer->shipping_name }}</strong></p>
                                <p class="text-muted small mb-0">
                                    {{ $customer->shipping_address_line1 }}<br>
                                    {{ $customer->shipping_address_line2 }}
                                    {{ $customer->shipping_city }}, {{ $customer->shipping_state }}<br>
                                    {{ $customer->shipping_country }} - {{ $customer->shipping_pincode }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Invoice History Table --}}
                <div class="card mt-4 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold">Recent Transactions</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Invoice ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="ps-4 fw-bold">#INV-{{ $invoice->id }}</td>
                                    <td>{{ $invoice->created_at->format('d M Y') }}</td>
                                    <td>{{ $customer->currency }}{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td><span class="badge bg-soft-success text-success">{{ ucfirst($invoice->status) }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-4">No recent invoices found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Bank Details --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-white-50"><i class="fe fe-credit-card me-2"></i>Bank Account Details</h6>
                        <div class="mb-3">
                            <label class="small text-white-50 d-block">Bank Name</label>
                            <span class="fw-bold">{{ $customer->bank_name ?? 'Not Set' }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="small text-white-50 d-block">Account Holder</label>
                            <span class="fw-bold">{{ $customer->account_holder ?? 'Not Set' }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="small text-white-50 d-block">Account Number</label>
                            <span class="fw-bold h5">{{ $customer->account_number ?? '**** **** ****' }}</span>
                        </div>
                        <div>
                            <label class="small text-white-50 d-block">IFSC / Branch</label>
                            <span class="fw-bold">{{ $customer->ifsc }} ({{ $customer->branch }})</span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">Internal Notes</h6>
                        <p class="text-muted small mb-0">{{ $customer->notes ?? 'No internal notes available for this customer.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-success { background-color: #d1fae5; }
    .bg-light-danger { background-color: #fee2e2; }
    .text-orange { color: #f97316; }
    .border-end-md { border-right: 1px solid #e5e7eb; }
    @media (max-width: 767px) { .border-end-md { border-right: none; } }
</style>
@endsection
