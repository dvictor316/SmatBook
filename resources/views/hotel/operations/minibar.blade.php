@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $entryValue = collect($entries->items())->sum(fn($item) => (float) ($item->line_total ?? $item->amount ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-box"></i> Minibar control</span>
                <h2>Post room consumption and inspect recent minibar charges.</h2>
                <p>Use the active-stay panel to open a folio quickly, then review posted minibar entries by guest and room.</p>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Active Stays</small><strong>{{ $activeStays->count() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Minibar Entries</small><strong>{{ $entries->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Value</small><strong>{{ number_format($entryValue, 2) }}</strong></div>
            </div>
            <div class="row g-3 align-items-stretch">
                <div class="col-xl-4">
                    <div class="hotel-pms-card h-100">
                        <h4 class="hotel-pms-card-title">Current In-House Rooms</h4>
                        @forelse($activeStays as $stay)
                            <div class="hotel-pms-ticket">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><strong>Room {{ $stay->room?->room_number ?? 'N/A' }}</strong><div class="small hotel-pms-muted">{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Guest' }}</div></div>
                                    <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-outline-primary">Folio</a>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-light mb-0">No active stays for minibar posting.</div>
                        @endforelse
                    </div>
                </div>
                <div class="col-xl-8">
                    <div class="hotel-pms-card table-responsive h-100">
                        <h4 class="hotel-pms-card-title">Recent Minibar Postings</h4>
                        <table class="table hotel-pms-table align-middle mb-0">
                            <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead>
                            <tbody>
                            @forelse($entries as $item)
                                <tr>
                                    <td>#{{ $item->folio?->stay_id ?: 'N/A' }}</td>
                                    <td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td>
                                    <td><span class="hotel-pms-pill gold">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0),2) }}</td>
                                    <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="hotel-pms-muted">No minibar charges found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
</div>
@endsection
