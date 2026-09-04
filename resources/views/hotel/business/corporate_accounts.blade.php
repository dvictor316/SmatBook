@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $cityLedgers instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $ledgerRows = $isPaginator ? collect($cityLedgers->items()) : collect($cityLedgers);
    $visibleCharges = $ledgerRows->sum(fn ($folio) => (float) ($folio->total_charges ?? 0));
    $visiblePayments = $ledgerRows->sum(fn ($folio) => (float) ($folio->total_payments ?? 0));
    $visibleBalance = $ledgerRows->sum(fn ($folio) => (float) ($folio->balance ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-ledger-page">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-briefcase"></i> Corporate Ledger</span>
                    <h2>Corporate account ledger</h2>
                    <p>Track city-ledger folios, outstanding balances, payments, and report actions for company accounts.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Ledger</button>
                    <a href="{{ route('hotel.folios.index') }}" class="btn btn-primary"><i class="fas fa-file-invoice me-1"></i> Guest Folios</a>
                    <a href="{{ route('general-ledger') }}" class="btn btn-outline-primary"><i class="fas fa-chart-line me-1"></i> Financial Report</a>
                </div>
            </div>

            <div class="hotel-ledger-strip">
                <span>Accounts: {{ $isPaginator ? $cityLedgers->total() : $ledgerRows->count() }}</span>
                <span>Visible charges: {{ number_format($visibleCharges, 2) }}</span>
                <span>Visible payments: {{ number_format($visiblePayments, 2) }}</span>
                <span>Visible balance: {{ number_format($visibleBalance, 2) }}</span>
            </div>

            <div class="hotel-type-panel">
                <div class="hotel-type-panel-body table-responsive">
                    <table class="table hotel-type-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Folio No</th>
                                <th>Total Charges</th>
                                <th>Total Payments</th>
                                <th>Outstanding</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cityLedgers as $folio)
                                <tr>
                                    <td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td>
                                    <td><strong>{{ $folio->folio_number }}</strong></td>
                                    <td>{{ number_format((float) $folio->total_charges, 2) }}</td>
                                    <td>{{ number_format((float) $folio->total_payments, 2) }}</td>
                                    <td><span class="hotel-status-chip {{ (float) $folio->balance > 0 ? 'red' : 'green' }}">{{ number_format((float) $folio->balance, 2) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('hotel.folios.show', $folio) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-folder-open me-1"></i> Open Folio</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No corporate account activity found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($isPaginator)
                <div class="mt-3">{{ $cityLedgers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
