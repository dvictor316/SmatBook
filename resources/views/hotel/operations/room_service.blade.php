@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-service-workflow">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-coffee"></i> Service Workflow</span>
                    <h2>Room service order queue</h2>
                    <p>Food and room-service postings are reviewed as a workflow queue, not a dashboard.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start"><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-primary">Front Desk</a><a href="{{ route('hotel.folios.index') }}" class="btn btn-primary">Guest Folios</a></div>
            </div>
            <div class="hotel-service-layout">
                <aside class="hotel-type-panel hotel-service-rail"><div class="hotel-type-panel-body"><h5 class="fw-semibold mb-3">Order Flow</h5>@foreach(['Posted to room', 'Kitchen/restaurant fulfilled', 'Folio reviewed', 'Ready for checkout'] as $i => $step)<div class="hotel-service-step"><span>{{ $i + 1 }}</span><div><strong>{{ $step }}</strong><div class="small text-muted">Tracked from live folio items.</div></div></div>@endforeach</div></aside>
                <section class="hotel-type-panel"><div class="hotel-type-panel-header"><h5 class="mb-0">Room Service Activity</h5></div><div class="hotel-type-panel-body table-responsive"><table class="table hotel-type-table align-middle mb-0"><thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Order</th><th>Qty</th><th>Amount</th><th>Time</th><th>Action</th></tr></thead><tbody>@forelse($items as $item)<tr><td>#{{ $item->folio?->stay_id ?: 'N/A' }}</td><td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td><td><span class="hotel-status-chip">Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</span></td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0),2) }}</td><td>{{ optional($item->created_at)->format('d M Y H:i') }}</td><td><a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="{{ route('hotel.folios.items.receipt', $item) }}">Receipt</a></td></tr>@empty<tr><td colspan="8" class="text-muted">No room service entries found.</td></tr>@endforelse</tbody></table></div></section>
                @include('hotel.partials.service-charge-form', ['center' => 'room_service', 'title' => 'Room Service Sale', 'placeholder' => 'Club sandwich, breakfast tray, dinner order'])
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
