@extends('layout.mainlayout')

@section('style')
<style>
    .folio-register { background:#f3f5f7; color:#1f2937; }
    .folio-hero { background:#24333a; color:#fff; padding:16px 18px; border-radius:6px 6px 0 0; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center; }
    .folio-hero h3 { color:#fff; margin:0; font-weight:700; }
    .folio-strip { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin:14px 0; }
    .folio-stat { background:#fff; border:1px solid #d7dde5; border-radius:6px; padding:13px; box-shadow:0 6px 18px rgba(15,23,42,.04); }
    .folio-stat span { color:#64748b; text-transform:uppercase; font-size:11px; letter-spacing:.08em; font-weight:700; }
    .folio-stat strong { display:block; font-size:24px; margin-top:5px; }
    .folio-shell { display:grid; grid-template-columns:260px minmax(0,1fr); gap:14px; }
    .folio-side, .folio-table-card { background:#fff; border:1px solid #d7dde5; box-shadow:0 6px 18px rgba(15,23,42,.04); }
    .folio-side { padding:14px; border-radius:6px; align-self:start; }
    .folio-side a { display:flex; justify-content:space-between; color:#27313f; text-decoration:none; border-bottom:1px solid #edf1f5; padding:11px 0; font-weight:600; }
    .folio-table-card { border-radius:6px; overflow:hidden; }
    .folio-table th { background:#f4f5f7; color:#1f2937; font-size:12px; text-transform:uppercase; border-bottom:1px solid #cfd6df; }
    .folio-table td { vertical-align:middle; }
    .folio-number { color:#8a174f; font-weight:700; text-decoration:none; }
    @media(max-width:991px){.folio-shell{grid-template-columns:1fr}.folio-strip{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.folio-strip{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $loadedFolios = collect($folios->items());
    $charges = $loadedFolios->sum(fn($folio) => (float) $folio->total_charges);
    $payments = $loadedFolios->sum(fn($folio) => (float) $folio->total_payments);
    $balances = $loadedFolios->sum(fn($folio) => (float) $folio->balance);
    $openCount = $loadedFolios->where('status', 'open')->count();
@endphp
<div class="page-wrapper folio-register">
    <div class="content container-fluid">
        <section class="folio-hero">
            <div><h3>Guest Folio Register</h3><p class="mb-0">Cashier queue for charges, payments, balances and checkout readiness.</p></div>
            <div class="d-flex gap-2 flex-wrap"><a href="{{ route('hotel.checkout.index') }}" class="btn btn-warning">Checkout Desk</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-light">Front Desk</a></div>
        </section>

        <div class="folio-strip">
            <div class="folio-stat"><span>Open Folios</span><strong>{{ $openCount }}</strong></div>
            <div class="folio-stat"><span>Charges Loaded</span><strong>{{ number_format($charges, 2) }}</strong></div>
            <div class="folio-stat"><span>Payments Loaded</span><strong>{{ number_format($payments, 2) }}</strong></div>
            <div class="folio-stat"><span>Balance Loaded</span><strong class="{{ $balances > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balances, 2) }}</strong></div>
        </div>

        <div class="folio-shell">
            <aside class="folio-side">
                <h5>Cashier Actions</h5>
                <a href="{{ route('hotel.checkout.index') }}"><span>Payment</span><i class="fe fe-credit-card"></i></a>
                <a href="{{ route('hotel.room_service.index') }}"><span>Room Service</span><i class="fe fe-shopping-cart"></i></a>
                <a href="{{ route('hotel.laundry.index') }}"><span>Laundry</span><i class="fe fe-package"></i></a>
                <a href="{{ route('hotel.minibar.index') }}"><span>Minibar</span><i class="fe fe-box"></i></a>
                <a href="{{ route('hotel.deposits') }}"><span>Deposits</span><i class="fe fe-dollar-sign"></i></a>
            </aside>

            <main class="folio-table-card">
                @if($folios->count() === 0)
                    <div class="p-4 text-muted">No guest folios found.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm folio-table align-middle mb-0">
                            <thead><tr><th>Folio</th><th>Guest</th><th>Room</th><th>Charges</th><th>Payments</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            @foreach($folios as $folio)
                                <tr>
                                    <td><a class="folio-number" href="{{ route('hotel.folios.show', $folio) }}">{{ $folio->folio_number }}</a></td>
                                    <td><strong>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</strong></td>
                                    <td><span class="badge bg-light text-dark">Room {{ $folio->stay?->room?->room_number ?? 'N/A' }}</span></td>
                                    <td>{{ number_format((float)$folio->total_charges,2) }}</td>
                                    <td>{{ number_format((float)$folio->total_payments,2) }}</td>
                                    <td><strong class="{{ (float)$folio->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$folio->balance,2) }}</strong></td>
                                    <td><span class="badge {{ $folio->status === 'open' ? 'bg-warning text-dark' : 'bg-success' }}">{{ ucfirst((string) $folio->status) }}</span></td>
                                    <td><a href="{{ route('hotel.folios.show', $folio) }}" class="btn btn-sm btn-outline-primary">Open Folio</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $folios->links() }}</div>
                @endif
            </main>
        </div>
    </div>
</div>
@endsection
