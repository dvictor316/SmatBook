@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $ordersCollection = $orders->getCollection();
    $expressOrders = $ordersCollection->filter(fn($item) => str_contains(strtolower((string) $item->description), 'express'));
    $totalPosted = $ordersCollection->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
    $lanes = [
        'Received' => $ordersCollection->count(),
        'Washing' => max(0, $ordersCollection->count() - $expressOrders->count()),
        'Ready' => $expressOrders->count(),
        'Delivered' => $ordersCollection->filter(fn($item) => optional($item->created_at)->lt(now()->subDay()))->count(),
    ];
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-service-workflow">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-droplet"></i> Laundry Production</span>
                    <h2>Laundry order control</h2>
                    <p>Track posted laundry work as a production flow, then print receipts or open guest folios from the register.</p>
                </div>
                <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-primary">Guest Folios</a>
            </div>

            <div class="hotel-op-kpis">
                <div class="hotel-op-kpi"><span>Orders</span><strong>{{ $orders->total() }}</strong></div>
                <div class="hotel-op-kpi"><span>Express</span><strong>{{ $expressOrders->count() }}</strong></div>
                <div class="hotel-op-kpi"><span>Revenue</span><strong>{{ number_format($totalPosted, 2) }}</strong></div>
                <div class="hotel-op-kpi"><span>Open Folios</span><strong>{{ $activeFolios->count() }}</strong></div>
            </div>

            <div class="hotel-op-split">
                <main>
                    <div class="hotel-op-lanes mb-3">
                        @foreach($lanes as $lane => $count)
                            <article class="hotel-op-lane">
                                <span class="lane-count">{{ $count }}</span>
                                <strong class="mt-3">{{ $lane }}</strong>
                                <p class="text-muted mb-0">Laundry orders move through this production checkpoint.</p>
                            </article>
                        @endforeach
                    </div>
                    <section class="hotel-type-panel">
                        <div class="hotel-type-panel-header"><h5 class="mb-0">Laundry Charge Register</h5></div>
                        <div class="hotel-type-panel-body table-responsive">
                            <table class="table hotel-type-table align-middle mb-0">
                                <thead><tr><th>Room</th><th>Guest</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead>
                                <tbody>
                                    @forelse($orders as $item)
                                        <tr><td><span class="hotel-status-chip">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td><td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 2) }}</td><td>{{ optional($item->created_at)->format('d M Y H:i') }}</td><td>@include('hotel.partials.service-sale-actions', ['item' => $item])</td></tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted">No laundry charges found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <div class="mt-3">{{ $orders->links() }}</div>
                </main>
                @include('hotel.partials.service-charge-form', ['center' => 'laundry', 'title' => 'Laundry Sale', 'placeholder' => 'Dry cleaning, ironing, express laundry'])
            </div>
        </div>
    </div>
</div>
@endsection
