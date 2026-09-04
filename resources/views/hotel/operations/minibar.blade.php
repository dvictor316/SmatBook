@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $entryRows = $entries->getCollection();
    $totalPosted = $entryRows->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
    $todayEntries = $entryRows->filter(fn($item) => optional($item->created_at)->isToday());
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-service-workflow">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-box"></i> Minibar Audit</span>
                    <h2>Room consumption desk</h2>
                    <p>Start from occupied rooms, post minibar usage, and review consumption receipts before checkout.</p>
                </div>
                <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-primary">Checkout Desk</a>
            </div>

            <div class="hotel-op-kpis">
                <div class="hotel-op-kpi"><span>Occupied Rooms</span><strong>{{ $activeStays->count() }}</strong></div>
                <div class="hotel-op-kpi"><span>Today Posts</span><strong>{{ $todayEntries->count() }}</strong></div>
                <div class="hotel-op-kpi"><span>Revenue</span><strong>{{ number_format($totalPosted, 2) }}</strong></div>
                <div class="hotel-op-kpi"><span>Open Folios</span><strong>{{ $activeFolios->count() }}</strong></div>
            </div>

            <div class="hotel-op-split">
                <main>
                    <div class="hotel-op-cards mb-3">
                        @forelse($activeStays as $stay)
                            <article class="hotel-op-room-card">
                                <div class="hotel-op-room-no">{{ $stay->room?->room_number ?? '?' }}</div>
                                <div>
                                    <h5 class="mb-1">{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Guest' }}</h5>
                                    <p class="text-muted mb-2">{{ $stay->room?->type?->name ?? 'In-house stay' }}</p>
                                    <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-outline-primary">Review Folio</a>
                                </div>
                            </article>
                        @empty
                            <div class="hotel-op-alert">No active stays are available for minibar posting.</div>
                        @endforelse
                    </div>
                    <section class="hotel-type-panel">
                        <div class="hotel-type-panel-header"><h5 class="mb-0">Recent Minibar Consumption</h5></div>
                        <div class="hotel-type-panel-body table-responsive">
                            <table class="table hotel-type-table align-middle mb-0">
                                <thead><tr><th>Room</th><th>Guest</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead>
                                <tbody>
                                    @forelse($entries as $item)
                                        <tr><td><span class="hotel-status-chip gold">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td><td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 2) }}</td><td>{{ optional($item->created_at)->format('d M Y H:i') }}</td><td>@include('hotel.partials.service-sale-actions', ['item' => $item])</td></tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted">No minibar charges found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <div class="mt-3">{{ $entries->links() }}</div>
                </main>
                @include('hotel.partials.service-charge-form', ['center' => 'minibar', 'title' => 'Minibar Sale', 'placeholder' => 'Water, snacks, wine, minibar restock'])
            </div>
        </div>
    </div>
</div>
@endsection
