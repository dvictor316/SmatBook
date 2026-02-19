<?php $page = 'ledger'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                Vendor Ledger
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="card p-4 ledger-list">
                
                @isset($vendor)
                <div class="d-flex align-items-center justify-content-between">
                    <div class="ledger-info mb-4">
                        <div class="d-flex align-items-center">
                            <a href="{{ url('profile') }}" class="avatar me-2">
                                <img class="avatar-img rounded-circle"
                                    src="{{ asset('assets/img/profiles/default-avatar.jpg') }}" 
                                    alt="Vendor Image">
                            </a>
                            <h2>
                                    <a href="{{ route('vendors.show', ['id' => $vendor->id]) }}">
                                        {{ $vendor->name }}
                                        <a href="mailto:{{ $vendor->email }}" class="d-block mail-to">
                                            {{ $vendor->email }}
                                    </a>
                                </a>
                            </h2>
                        </div>
                    </div>
                    <div class="list-btn">
                        <ul class="filter-list">
                            <li>
                                <div class="closing-balance">
                                    {{-- Use the dynamically calculated closing balance --}}
                                    <span>Closing Balance : ${{ number_format($closingBalance ?? 0, 2) }}</span>
                                </div>
                            </li>
                            <li>
                                    <a href="{{ route('vendors.transactions.create', ['id' => $vendor->id]) }}" class="btn btn-primary btn-sm">
                                        Add Transaction
                                    </a>
                            </li>
                        </ul>
                    </div>
                </div>
                @else
                    <div class="alert alert-info">
                        Select a specific vendor from the main list to see their detailed ledger information.
                    </div>
                @endisset

                <!-- Table -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card-table">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-stripped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Reference #</th>
                                                <th>Created</th>
                                                <th>Mode</th>
                                                <th>Amount</th>
                                                <th>Closing Balance</th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- FULLY DYNAMIC DATA LOOP --}}
                                            @if(isset($transactions) && $transactions->count() > 0)
                                                @php $runningBalance = 0; @endphp
                                                @foreach ($transactions as $transaction)
                                                    @php $runningBalance += $transaction->amount; @endphp
                                                    <tr>
                                                        <td>
                                                            <h2 class="ledger">{{ $transaction->name }}
                                                                <span>{{ $transaction->mode }}</span>
                                                            </h2>
                                                        </td>
                                                        <td>{{ $transaction->reference }}</td>
                                                        <td>{{ $transaction->created_at->format('M d, Y') }}</td>
                                                        <td>
                                                            {{-- Apply CSS classes based on mode (you need to define these classes in your CSS) --}}
                                                            <span class="badge {{ $transaction->amount >= 0 ? 'bg-success-light' : 'bg-danger-light' }}">
                                                                {{ $transaction->mode }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="{{ $transaction->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                                                ${{ number_format(abs($transaction->amount), 2) }}
                                                            </span>
                                                        </td>
                                                        <td><span>${{ number_format($runningBalance, 2) }}</span></td>
                                                        <td class="text-start">
                                                            <div class="dropdown dropdown-action">
                                                                <span class="text-muted">-</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="7">No transactions found for this vendor.</td>
                                                </tr>
                                            @endif
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
@endsection
