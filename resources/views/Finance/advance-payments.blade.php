<?php $page = 'advance-payments'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                {{ $title }}
            @endslot
        @endcomponent

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">{{ $title }}</h4>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('advance-payments.customers') }}" class="btn {{ $mode === 'customers' ? 'btn-primary' : 'btn-white border' }}">Customer Advances</a>
                    <a href="{{ route('advance-payments.suppliers') }}" class="btn {{ $mode === 'suppliers' ? 'btn-primary' : 'btn-white border' }}">Supplier Advances</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ $mode === 'customers' ? 'Customer' : 'Supplier' }}</th>
                                <th>Phone</th>
                                <th class="text-end">{{ $mode === 'customers' ? 'Wallet / Advance' : 'Opening Balance' }}</th>
                                <th class="text-end">{{ $mode === 'customers' ? 'Outstanding' : 'Action' }}</th>
                                @if($mode === 'customers')
                                    <th class="text-end">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                @php
                                    $displayName = $mode === 'customers'
                                        ? ($record->customer_name ?? $record->name ?? 'Unnamed Customer')
                                        : ($record->supplier_name ?? $record->company_name ?? $record->name ?? 'Unnamed Supplier');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $displayName }}</div>
                                        <small class="text-muted">{{ $record->email ?? 'No email saved' }}</small>
                                    </td>
                                    <td>{{ $record->phone ?? '-' }}</td>
                                    @if($mode === 'customers')
                                        <td class="text-end fw-semibold">₦{{ number_format((float) ($record->wallet_balance ?? 0), 2) }}</td>
                                        <td class="text-end">₦{{ number_format((float) ($record->sales_balance_sum ?? 0), 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('customers.receive-payment', $record->id) }}" class="btn btn-sm btn-primary">Receive Advance</a>
                                        </td>
                                    @else
                                        <td class="text-end fw-semibold">₦{{ number_format((float) ($record->opening_balance ?? 0), 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('suppliers.pay', $record->id) }}" class="btn btn-sm btn-primary">Pay Advance</a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $mode === 'customers' ? 5 : 4 }}" class="text-center text-muted py-4">
                                        No {{ $mode === 'customers' ? 'customers' : 'suppliers' }} found yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $records->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
