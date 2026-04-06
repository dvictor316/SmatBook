<?php $page = 'finance-collections'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Collections & Payables Hub
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Receivables</div>
                        <div class="fs-4 fw-bold">₦{{ number_format((float) ($summary['receivable_total'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Customers Owing</div>
                        <div class="fs-3 fw-bold">{{ number_format((int) ($summary['receivable_accounts'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Payables</div>
                        <div class="fs-4 fw-bold">₦{{ number_format((float) ($summary['payable_total'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Suppliers Outstanding</div>
                        <div class="fs-3 fw-bold">{{ number_format((int) ($summary['payable_accounts'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Customer Receivables</h5>
                                <p class="text-muted mb-0">Outstanding customer balances ready for collection.</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Outstanding</th>
                                        <th>Ageing</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customerAccounts as $account)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $account['name'] }}</div>
                                                <small class="text-muted">{{ $account['documents'] }} docs • Oldest {{ $account['oldest_date'] ?: 'N/A' }}</small>
                                            </td>
                                            <td class="text-end fw-semibold">₦{{ number_format((float) $account['total_due'], 2) }}</td>
                                            <td>
                                                <div class="small">0-30: ₦{{ number_format((float) $account['bucket_current'], 2) }}</div>
                                                <div class="small">31-60: ₦{{ number_format((float) $account['bucket_31_60'], 2) }}</div>
                                                <div class="small">61-90: ₦{{ number_format((float) $account['bucket_61_90'], 2) }}</div>
                                                <div class="small">90+: ₦{{ number_format((float) $account['bucket_90_plus'], 2) }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if(!empty($account['id']))
                                                    <a href="{{ route('customers.receive-payment', $account['id']) }}" class="btn btn-sm btn-primary">Receive</a>
                                                @else
                                                    <span class="text-muted small">Walk-in</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No outstanding customer balances found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $customerAccounts->links() }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Supplier Payables</h5>
                                <p class="text-muted mb-0">Outstanding supplier balances ready for payment planning.</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Supplier</th>
                                        <th class="text-end">Outstanding</th>
                                        <th>Ageing</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplierAccounts as $account)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $account['name'] }}</div>
                                                <small class="text-muted">{{ $account['documents'] }} docs • Oldest {{ $account['oldest_date'] ?: 'N/A' }}</small>
                                            </td>
                                            <td class="text-end fw-semibold">₦{{ number_format((float) $account['total_due'], 2) }}</td>
                                            <td>
                                                <div class="small">0-30: ₦{{ number_format((float) $account['bucket_current'], 2) }}</div>
                                                <div class="small">31-60: ₦{{ number_format((float) $account['bucket_31_60'], 2) }}</div>
                                                <div class="small">61-90: ₦{{ number_format((float) $account['bucket_61_90'], 2) }}</div>
                                                <div class="small">90+: ₦{{ number_format((float) $account['bucket_90_plus'], 2) }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if(!empty($account['id']))
                                                    <a href="{{ route('suppliers.pay', $account['id']) }}" class="btn btn-sm btn-outline-primary">Pay</a>
                                                @else
                                                    <span class="text-muted small">Unlinked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No outstanding supplier balances found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $supplierAccounts->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
