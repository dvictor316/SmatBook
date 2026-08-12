@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $depositRows = $isPaginator ? collect($deposits->items()) : collect($deposits);
    $visibleReceived = $depositRows->sum(fn($deposit) => (float) ($deposit->deposit_received ?? 0));
    $visibleGap = $depositRows->sum(fn($deposit) => max(0, (float) ($deposit->deposit_required ?? 0) - (float) ($deposit->deposit_received ?? 0)));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-credit-card"></i> Deposit control</span>
                <h2>Reservation deposits and funding gaps.</h2>
                <p>Monitor received deposits, required balances, and reservations that still need pre-arrival funding.</p>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Deposits Found</small><strong>{{ $isPaginator ? $deposits->total() : $depositRows->count() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Received</small><strong>{{ number_format($visibleReceived, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Gap</small><strong>{{ number_format($visibleGap, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Deposit Register</h4>
                <table class="table hotel-pms-table align-middle mb-0"><thead><tr><th>Reservation</th><th>Guest</th><th>Deposit Received</th><th>Deposit Gap</th><th>Status</th><th>Date</th></tr></thead><tbody>
                @forelse($deposits as $deposit)
                    @php $gap = max(0, (float)$deposit->deposit_required - (float)$deposit->deposit_received); @endphp
                    <tr>
                        <td><strong>{{ $deposit->reservation_number }}</strong></td>
                        <td>{{ $deposit->customer?->customer_name ?? $deposit->customer?->name ?? 'N/A' }}</td>
                        <td>{{ number_format((float)$deposit->deposit_received,2) }}</td>
                        <td>{{ number_format($gap,2) }}</td>
                        <td><span class="hotel-pms-pill {{ $gap > 0 ? 'gold' : 'green' }}">{{ $gap > 0 ? 'More Required' : 'Covered' }}</span></td>
                        <td>{{ optional($deposit->created_at)->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hotel-pms-muted">No deposits found.</td></tr>
                @endforelse
                </tbody></table>
            </div>
            @if($isPaginator)<div class="mt-3">{{ $deposits->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
