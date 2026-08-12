@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $totalBookings = $sources->sum(fn($source) => (int) ($source->reservations_count ?? 0));
    $grossValue = $sources->sum(fn($source) => (float) ($source->gross_value ?? 0));
    $topSource = $sources->first();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-link"></i> Booking sources</span>
                <h2>Know which channels are feeding the hotel.</h2>
                <p>Compare direct bookings, travel agents, OTAs, corporate sources, and event channels by booking count and value.</p>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Total Bookings</small><strong>{{ $totalBookings }}</strong></div>
                <div class="hotel-pms-kpi"><small>Gross Value</small><strong>{{ number_format($grossValue, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Top Source</small><strong>{{ $topSource ? ucfirst((string) $topSource->booking_source) : 'N/A' }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Channel Performance</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Source</th><th>Bookings</th><th>Revenue</th><th>Average Value</th><th>Share</th></tr></thead>
                    <tbody>
                    @forelse($sources as $source)
                        @php
                            $count = (int) ($source->reservations_count ?? 0);
                            $value = (float) ($source->gross_value ?? 0);
                            $share = $totalBookings > 0 ? round(($count / $totalBookings) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ ucfirst((string)$source->booking_source) }}</strong></td>
                            <td>{{ $count }}</td>
                            <td>{{ number_format($value,2) }}</td>
                            <td>{{ number_format($count > 0 ? ($value / $count) : 0,2) }}</td>
                            <td><span class="hotel-pms-pill gold">{{ $share }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="hotel-pms-muted">No booking source data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
