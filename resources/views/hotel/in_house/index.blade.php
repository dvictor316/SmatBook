@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $stays instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $stayRows = $isPaginator ? collect($stays->items()) : collect($stays);
    $charges = $stayRows->sum(fn($stay) => (float) ($stay->folio_charges ?? 0));
    $payments = $stayRows->sum(fn($stay) => (float) ($stay->folio_payments ?? 0));
    $balance = $stayRows->sum(fn($stay) => (float) ($stay->folio_balance ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-users"></i> In-house control</span>
                <h2>Active stay monitoring for occupied rooms.</h2>
                <p>Watch checked-in guests, room assignment, expected checkout, folio charges, payments, and outstanding balances.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.checkout.index') }}" class="btn btn-light">Open Checkout Desk</a>
                    <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-light">Room Calendar</a>
                </div>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>In-House Guests</small><strong>{{ $isPaginator ? $stays->total() : $stayRows->count() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Charges</small><strong>{{ number_format($charges, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Payments</small><strong>{{ number_format($payments, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Outstanding</small><strong>{{ number_format($balance, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Active Stays</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Guest</th><th>Room</th><th>Check-In</th><th>Expected Checkout</th><th>Charges</th><th>Paid</th><th>Balance</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($stays as $stay)
                        <tr>
                            <td><strong>{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Walk-In Guest' }}</strong></td>
                            <td><span class="hotel-pms-pill">Room {{ $stay->room?->room_number ?? 'N/A' }}</span></td>
                            <td>{{ optional($stay->checkin_at)->format('d M Y H:i') }}</td>
                            <td>{{ optional($stay->expected_checkout_at)->format('d M Y H:i') }}</td>
                            <td>{{ number_format((float) $stay->folio_charges, 2) }}</td>
                            <td>{{ number_format((float) $stay->folio_payments, 2) }}</td>
                            <td><span class="hotel-pms-pill {{ (float)$stay->folio_balance > 0 ? 'red' : 'green' }}">{{ number_format((float) $stay->folio_balance, 2) }}</span></td>
                            <td><div class="d-flex gap-1 flex-wrap"><a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-light">Folio</a><a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-outline-warning">Checkout</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="hotel-pms-muted">No in-house guests found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($isPaginator)<div class="mt-3">{{ $stays->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
