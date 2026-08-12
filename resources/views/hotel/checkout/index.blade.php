@extends('layout.mainlayout')

@section('style')
<style>
    .settlement-page { background:#f7f8fa; color:#1f2937; }
    .settlement-shell { display:grid; grid-template-columns:310px minmax(0,1fr); gap:14px; }
    .settle-card { background:#fff; border:1px solid #d9dee7; border-radius:6px; box-shadow:0 6px 18px rgba(15,23,42,.04); }
    .stay-card { padding:14px; border-bottom:1px solid #edf1f5; display:block; color:#1f2937; text-decoration:none; }
    .stay-card.active { border-left:5px solid #315bdc; background:#f3f6ff; }
    .stay-card:hover { background:#f8fafc; color:#1f2937; }
    .timeline { display:flex; align-items:center; gap:8px; margin:12px 0; }
    .timeline span { flex:1; height:3px; background:#cdd7e7; position:relative; }
    .timeline span:before { content:''; position:absolute; left:0; top:-4px; width:11px; height:11px; border-radius:50%; background:#315bdc; }
    .settle-tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .settle-tabs label { border:1px solid #cfd6df; border-radius:7px; padding:10px 14px; min-width:105px; text-align:center; font-weight:800; cursor:pointer; background:#fff; }
    .settle-tabs input { display:none; }
    .settle-tabs input:checked + span { color:#315bdc; }
    .settle-summary { background:#f6f7f9; border-top:1px solid #d9dee7; padding:18px; }
    .settle-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e5e9ef; }
    .settle-row:last-child { border-bottom:0; }
    .folio-strip { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; }
    .folio-stat { background:#fff; border:1px solid #d9dee7; border-radius:6px; padding:12px; }
    .settle-table th { background:#f3f4f6; font-size:12px; text-transform:uppercase; }
    @media(max-width:991px){.settlement-shell{grid-template-columns:1fr}.folio-strip{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.folio-strip{grid-template-columns:1fr}.settle-tabs label{width:100%}}
</style>
@endsection

@section('content')
<div class="page-wrapper settlement-page">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h3 class="mb-1">Checkout Settlement</h3><p class="text-muted mb-0">Cashier-style balance review and guest departure desk.</p></div>
            <a href="{{ route('hotel.in_house') }}" class="btn btn-outline-secondary">In-House Guests</a>
        </div>

        <div class="settlement-shell">
            <aside class="settle-card">
                <div class="p-3 border-bottom"><strong>Open Stays</strong><div class="small text-muted">{{ $stays->count() }} guests currently checked in</div></div>
                @forelse($stays as $stay)
                    <a class="stay-card {{ $selectedStay && (int)$selectedStay->id === (int)$stay->id ? 'active' : '' }}" href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}">
                        <div class="d-flex justify-content-between gap-2"><strong>{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Guest' }}</strong><span class="badge bg-light text-dark">Room {{ $stay->room?->room_number ?? 'N/A' }}</span></div>
                        <div class="timeline"><span></span><span></span><span></span></div>
                        <div class="small text-muted">Booked · Check-in {{ optional($stay->checkin_at)->format('d M Y') ?: 'N/A' }} · Check-out {{ optional($stay->expected_checkout_at)->format('d M Y') ?: 'N/A' }}</div>
                    </a>
                @empty
                    <div class="p-3 text-muted">No active stays found.</div>
                @endforelse
                <div class="p-3">{{ $stays->links() }}</div>
            </aside>

            <main class="settle-card">
                @if(!$selectedStay)
                    <div class="p-5 text-center text-muted">Select a stay from the left list to process checkout.</div>
                @else
                    <div class="p-3 border-bottom d-flex justify-content-between flex-wrap gap-2">
                        <div><strong>{{ $selectedStay->customer?->customer_name ?? $selectedStay->customer?->name ?? 'Guest' }}</strong><div class="small text-muted">Room {{ $selectedStay->room?->room_number ?? 'N/A' }} · Folio {{ $selectedFolio?->folio_number ?? 'N/A' }}</div></div>
                        <span class="badge bg-warning text-dark align-self-start">Balance due: {{ number_format((float) ($selectedFolio?->balance ?? 0), 2) }}</span>
                    </div>

                    <div class="p-3">
                        <div class="folio-strip mb-3">
                            <div class="folio-stat"><small>Charges</small><h5 class="mb-0">{{ number_format((float) ($selectedFolio?->total_charges ?? 0), 2) }}</h5></div>
                            <div class="folio-stat"><small>Deposits</small><h5 class="mb-0">{{ number_format((float) ($selectedFolio?->opening_deposit ?? 0), 2) }}</h5></div>
                            <div class="folio-stat"><small>Payments</small><h5 class="mb-0">{{ number_format((float) ($selectedFolio?->total_payments ?? 0), 2) }}</h5></div>
                            <div class="folio-stat"><small>Net Due</small><h5 class="mb-0 {{ (float)($selectedFolio?->balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) ($selectedFolio?->balance ?? 0), 2) }}</h5></div>
                        </div>

                        <form method="POST" action="{{ route('hotel.checkout', $selectedStay) }}">
                            @csrf
                            <label class="form-label fw-bold">Payment Method</label>
                            <div class="settle-tabs mb-3">
                                @foreach(['cash' => 'Cash', 'transfer' => 'Transfer', 'pos' => 'POS', 'split' => 'Split', 'corporate_credit' => 'City Ledger'] as $method => $label)
                                    <label><input type="radio" name="settlement_method" value="{{ $method }}" {{ $loop->first ? 'checked' : '' }}><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Amount Paid</label><input type="number" step="0.01" min="0" name="paid_amount" value="{{ number_format((float) max($selectedFolio?->balance ?? 0, 0), 2, '.', '') }}" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label">Reference</label><input type="text" class="form-control" placeholder="Optional payment reference"></div>
                                <div class="col-12"><button class="btn btn-warning btn-lg">Settle and Complete Checkout</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="settle-summary">
                        <div class="settle-row"><span>Suggested deposit applied</span><strong>{{ number_format((float) ($selectedFolio?->opening_deposit ?? 0), 2) }}</strong></div>
                        <div class="settle-row"><span>Subtotal</span><strong>{{ number_format((float) ($selectedFolio?->total_charges ?? 0), 2) }}</strong></div>
                        <div class="settle-row"><span>Amount paid</span><strong>{{ number_format((float) ($selectedFolio?->total_payments ?? 0), 2) }}</strong></div>
                        <div class="settle-row"><span>Balance due</span><strong>{{ number_format((float) ($selectedFolio?->balance ?? 0), 2) }}</strong></div>
                    </div>

                    <div class="p-3 table-responsive">
                        <table class="table table-sm settle-table align-middle mb-0">
                            <thead><tr><th>Date</th><th>Description</th><th>Department</th><th>Type</th><th>Amount</th></tr></thead>
                            <tbody>
                            @forelse($folioItems as $item)
                                <tr><td>{{ optional($item->service_date)->format('d M Y') }}</td><td>{{ $item->description }}</td><td>{{ strtoupper((string) ($item->service_code ?? '-')) }}</td><td>{{ ucfirst(str_replace('_', ' ', (string) $item->type)) }}</td><td>{{ number_format((float) $item->amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-4">No folio line items available.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </main>
        </div>
    </div>
</div>
@endsection
