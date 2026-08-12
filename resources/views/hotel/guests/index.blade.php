@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $guests instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $guestRows = $isPaginator ? collect($guests->items()) : collect($guests);
    $totalStays = $guestRows->sum(fn($guest) => (int) ($guest->total_stays ?? 0));
    $totalSpend = $guestRows->sum(fn($guest) => (float) ($guest->total_spend ?? 0));
    $outstanding = $guestRows->sum(fn($guest) => (float) ($guest->outstanding_balance ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-user"></i> Guest profile index</span>
                <h2>Hotel CRM with stay, spend, and balance visibility.</h2>
                <p>See returning guests, contact details, last stay, total stay count, lifetime hotel spend, and outstanding balances.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.search') }}" class="btn btn-light">Search Guests</a>
                    <a href="{{ route('hotel.reservations.create') }}" class="btn btn-outline-light">New Reservation</a>
                </div>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Guests</small><strong>{{ $isPaginator ? $guests->total() : $guestRows->count() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Stays</small><strong>{{ $totalStays }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Spend</small><strong>{{ number_format($totalSpend, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Outstanding</small><strong>{{ number_format($outstanding, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Guest Profiles</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Guest</th><th>Contact</th><th>Last Stay</th><th>Total Stays</th><th>Total Spend</th><th>Balance</th></tr></thead>
                    <tbody>
                    @forelse($guests as $guest)
                        <tr>
                            <td><strong>{{ $guest->customer_name ?? $guest->name }}</strong><div class="small hotel-pms-muted">Guest ID #{{ $guest->id }}</div></td>
                            <td><div>{{ $guest->phone ?: 'No phone' }}</div><small class="hotel-pms-muted">{{ $guest->email ?: 'No email' }}</small></td>
                            <td>{{ $guest->last_stay ? \Carbon\Carbon::parse($guest->last_stay)->format('d M Y') : 'No stay yet' }}</td>
                            <td>{{ $guest->total_stays ?? 0 }}</td>
                            <td>{{ number_format((float) ($guest->total_spend ?? 0), 2) }}</td>
                            <td><span class="hotel-pms-pill {{ (float)($guest->outstanding_balance ?? 0) > 0 ? 'red' : 'green' }}">{{ number_format((float) ($guest->outstanding_balance ?? 0), 2) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="hotel-pms-muted">No hotel guests found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($isPaginator)<div class="mt-3">{{ $guests->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
