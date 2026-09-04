@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $todayItems = $items->getCollection()->filter(fn($item) => optional($item->service_date ?? $item->created_at)->isToday());
    $totalPosted = $items->getCollection()->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
    $guestRooms = $activeFolios->filter(fn($folio) => $folio->stay?->room)->take(6);
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-service-workflow">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-coffee"></i> In-Room Dining</span>
                    <h2>Room service dispatch board</h2>
                    <p>Post trays to guest folios, monitor today’s room orders, and send staff back to the front desk when a guest needs folio support.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-primary">Front Desk</a>
                    <a href="{{ route('hotel.folios.index') }}" class="btn btn-primary">Guest Folios</a>
                </div>
            </div>

            <div class="hotel-op-kpis">
                <div class="hotel-op-kpi"><span>Open Folios</span><strong>{{ $activeFolios->count() }}</strong></div>
                <div class="hotel-op-kpi"><span>Today Orders</span><strong>{{ $todayItems->count() }}</strong></div>
                <div class="hotel-op-kpi"><span>Visible Revenue</span><strong>{{ number_format($totalPosted, 2) }}</strong></div>
                <div class="hotel-op-kpi"><span>Receipt Lines</span><strong>{{ $items->total() }}</strong></div>
            </div>

            <div class="hotel-op-board">
                <section class="hotel-type-panel">
                    <div class="hotel-type-panel-header"><h5 class="mb-0">Live Room Service Orders</h5></div>
                    <div class="hotel-type-panel-body table-responsive">
                        <table class="table hotel-type-table align-middle mb-0">
                            <thead><tr><th>Time</th><th>Room</th><th>Guest</th><th>Order</th><th>Qty</th><th>Amount</th><th>Receipt</th></tr></thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ optional($item->created_at)->format('d M H:i') }}</td>
                                        <td><span class="hotel-status-chip">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td>
                                        <td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 2) }}</td>
                                        <td><a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="{{ route('hotel.folios.items.receipt', $item) }}">Receipt</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">No room service entries found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside>
                    <div class="hotel-op-cards mb-3">
                        @forelse($guestRooms as $folio)
                            <article class="hotel-op-room-card">
                                <div class="hotel-op-room-no">{{ $folio->stay?->room?->room_number }}</div>
                                <div>
                                    <h5 class="mb-1">{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }}</h5>
                                    <p class="text-muted mb-2">{{ $folio->folio_number }}</p>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('hotel.folios.show', $folio) }}">Open Folio</a>
                                </div>
                            </article>
                        @empty
                            <div class="hotel-op-alert">No checked-in rooms with open folios are available for room service posting.</div>
                        @endforelse
                    </div>
                    @include('hotel.partials.service-charge-form', ['center' => 'room_service', 'title' => 'Room Service Sale', 'placeholder' => 'Club sandwich, breakfast tray, dinner order'])
                </aside>
            </div>

            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
