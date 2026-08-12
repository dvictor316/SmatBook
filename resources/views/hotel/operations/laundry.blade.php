@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $pageAmount = collect($orders->items())->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-droplet"></i> Laundry workflow</span>
                <h2>Track guest laundry from receipt to folio posting.</h2>
                <p>Monitor laundry charges, guest ownership, room details, volume, and charge value without mixing it with minibar or room service.</p>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Total Orders</small><strong>{{ $orders->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Orders</small><strong>{{ $orders->count() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Value</small><strong>{{ number_format($pageAmount, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-board mb-3">
                @foreach(['Received', 'Washing', 'Ready', 'Delivered'] as $step)
                    <div class="hotel-pms-lane">
                        <h5>{{ $step }}</h5>
                        <div class="hotel-pms-ticket mb-0">
                            <div class="fw-semibold">Live folio-backed queue</div>
                            <div class="small hotel-pms-muted">Orders appear below once posted to guest folios.</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Laundry Charges</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($orders as $item)
                        <tr>
                            <td>#{{ $item->folio?->stay_id ?: 'N/A' }}</td>
                            <td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td>
                            <td><span class="hotel-pms-pill">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0),2) }}</td>
                            <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="hotel-pms-muted">No laundry charges found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
