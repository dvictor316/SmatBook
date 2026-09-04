@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $maxBookings = max(1, (int) $sources->max('reservations_count'));
    $totalBookings = (int) $sources->sum('reservations_count');
    $totalValue = (float) $sources->sum('gross_value');
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-directory-page">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-link"></i> Channel Sales</span>
                    <h2>Booking source performance</h2>
                    <p>Review direct, OTA, corporate, and walk-in channels with fast access to reservations and hotel reports.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('hotel.reservations.index') }}" class="btn btn-primary"><i class="fas fa-book me-1"></i> Reservations</a>
                    <a href="{{ route('hotel.reports.index') }}" class="btn btn-outline-primary"><i class="fas fa-chart-bar me-1"></i> Reports</a>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
                </div>
            </div>

            <div class="hotel-ledger-strip">
                <span>Sources: {{ $sources->count() }}</span>
                <span>Bookings: {{ $totalBookings }}</span>
                <span>Gross value: {{ number_format($totalValue, 2) }}</span>
            </div>

            <div class="hotel-type-panel">
                <div class="hotel-type-panel-body">
                    @forelse($sources as $source)
                        @php
                            $width = round(((int) $source->reservations_count / $maxBookings) * 100);
                            $share = $totalBookings > 0 ? round(((int) $source->reservations_count / $totalBookings) * 100) : 0;
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong>{{ ucfirst((string) $source->booking_source) }}</strong>
                                <span>{{ $source->reservations_count }} bookings | {{ $share }}% share | {{ number_format((float) $source->gross_value, 2) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 12px;">
                                <div class="progress-bar" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No booking source data found.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
