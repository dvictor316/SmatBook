@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $visibleCharges = collect($cityLedgers->items())->sum(fn($folio) => (float) ($folio->total_charges ?? 0));
    $visibleBalance = collect($cityLedgers->items())->sum(fn($folio) => (float) ($folio->balance ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-briefcase"></i> Corporate ledger</span>
                <h2>Company accounts and city-ledger balances.</h2>
                <p>Track B2B folios, outstanding balances, charges, and payments from one finance-ready screen.</p>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Corporate Folios</small><strong>{{ $cityLedgers->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Charges</small><strong>{{ number_format($visibleCharges, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Balance</small><strong>{{ number_format($visibleBalance, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Corporate Account Activity</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Company</th><th>Folio No</th><th>Total Charges</th><th>Total Payments</th><th>Outstanding</th></tr></thead>
                    <tbody>
                    @forelse($cityLedgers as $folio)
                        <tr>
                            <td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td>
                            <td><strong>{{ $folio->folio_number }}</strong></td>
                            <td>{{ number_format((float)$folio->total_charges,2) }}</td>
                            <td>{{ number_format((float)$folio->total_payments,2) }}</td>
                            <td><span class="hotel-pms-pill {{ (float)$folio->balance > 0 ? 'red' : 'green' }}">{{ number_format((float)$folio->balance,2) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="hotel-pms-muted">No corporate account activity found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $cityLedgers->links() }}</div>
        </div>
    </div>
</div>
@endsection
