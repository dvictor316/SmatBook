@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php $maxBookings = max(1, (int) $sources->max('reservations_count')); @endphp
<div class="page-wrapper"><div class="content container-fluid"><div class="hotel-type-page hotel-directory-page"><div class="hotel-type-header"><div><span class="hotel-type-label"><i class="fe fe-link"></i> Data Management</span><h2>Booking source performance</h2><p>Channels are shown as horizontal performance bars, not dashboard metric cards.</p></div></div><div class="hotel-type-panel"><div class="hotel-type-panel-body">@forelse($sources as $source)@php $width = round(((int)$source->reservations_count / $maxBookings) * 100); @endphp<div class="mb-4"><div class="d-flex justify-content-between"><strong>{{ ucfirst((string)$source->booking_source) }}</strong><span>{{ $source->reservations_count }} bookings · {{ number_format((float)$source->gross_value,2) }}</span></div><div class="progress mt-2" style="height: 12px;"><div class="progress-bar" style="width: {{ $width }}%"></div></div></div>@empty<div class="text-muted">No booking source data found.</div>@endforelse</div></div></div></div></div>
@endsection
