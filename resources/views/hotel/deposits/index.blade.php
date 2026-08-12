@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $depositRows = $isPaginator ? collect($deposits->items()) : collect($deposits);
    $visibleReceived = $depositRows->sum(fn($deposit) => (float) ($deposit->deposit_received ?? 0));
    $visibleGap = $depositRows->sum(fn($deposit) => max(0, (float) ($deposit->deposit_required ?? 0) - (float) ($deposit->deposit_received ?? 0)));
@endphp
<div class="page-wrapper"><div class="content container-fluid"><div class="hotel-type-page hotel-ledger-page"><div class="hotel-type-header"><div><span class="hotel-type-label"><i class="fe fe-credit-card"></i> Financial</span><h2>Deposit ledger</h2><p>Reservation funding is presented as a ledger with coverage status, not as dashboard tiles.</p></div></div><div class="hotel-ledger-strip"><span>Rows: {{ $isPaginator ? $deposits->total() : $depositRows->count() }}</span><span>Visible received: {{ number_format($visibleReceived, 2) }}</span><span>Visible gap: {{ number_format($visibleGap, 2) }}</span></div><div class="hotel-type-panel"><div class="hotel-type-panel-body table-responsive"><table class="table hotel-type-table align-middle mb-0"><thead><tr><th>Reservation</th><th>Guest</th><th>Deposit Received</th><th>Deposit Gap</th><th>Status</th><th>Date</th></tr></thead><tbody>@forelse($deposits as $deposit)@php $gap = max(0, (float)$deposit->deposit_required - (float)$deposit->deposit_received); @endphp<tr><td><strong>{{ $deposit->reservation_number }}</strong></td><td>{{ $deposit->customer?->customer_name ?? $deposit->customer?->name ?? 'N/A' }}</td><td>{{ number_format((float)$deposit->deposit_received,2) }}</td><td>{{ number_format($gap,2) }}</td><td><span class="hotel-status-chip {{ $gap > 0 ? 'gold' : 'green' }}">{{ $gap > 0 ? 'More Required' : 'Covered' }}</span></td><td>{{ optional($deposit->created_at)->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No deposits found.</td></tr>@endforelse</tbody></table></div></div>@if($isPaginator)<div class="mt-3">{{ $deposits->links() }}</div>@endif</div></div></div>
@endsection
