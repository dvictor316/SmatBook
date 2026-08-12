@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $totalAmount = collect($items->items())->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
    $roomCount = collect($items->items())->pluck('folio.stay.room.room_number')->filter()->unique()->count();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-coffee"></i> Room service desk</span>
                <h2>Charge-to-room meals and service requests.</h2>
                <p>Review posted restaurant and room-service charges by stay, guest, room, quantity, and posting time.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.frontdesk') }}" class="btn btn-light">Front Desk</a>
                    <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-light">Guest Folios</a>
                </div>
            </div>

            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Posted Orders</small><strong>{{ $items->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Rooms Served</small><strong>{{ $roomCount }}</strong></div>
                <div class="hotel-pms-kpi"><small>This Page Value</small><strong>{{ number_format($totalAmount, 2) }}</strong></div>
            </div>

            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Room Service Activity</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Order</th><th>Qty</th><th>Amount</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($items as $item)
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
                        <tr><td colspan="7" class="hotel-pms-muted">No room service entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
