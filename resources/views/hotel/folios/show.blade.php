@extends('layout.mainlayout')

@section('style')
<style>
    .folio-cashier { background:#fbfbfa; color:#20242a; }
    .cashier-top { background:#24333a; color:#fff; padding:10px 14px; display:flex; justify-content:space-between; gap:10px; align-items:center; border-radius:4px 4px 0 0; }
    .cashier-shell { display:grid; grid-template-columns:280px minmax(0,1fr) 330px; gap:12px; }
    .cashier-card { background:#fff; border:1px solid #d7dde5; box-shadow:0 5px 16px rgba(15,23,42,.04); }
    .cashier-side { padding:12px; }
    .cashier-room { font-size:42px; font-weight:900; color:#123456; line-height:1; }
    .cashier-chip { display:inline-flex; align-items:center; padding:5px 9px; border-radius:4px; background:#eef2f7; color:#334155; font-weight:800; font-size:12px; }
    .cashier-tabs { display:flex; border-bottom:3px solid #8a174f; background:#f7f7f7; }
    .cashier-tab { padding:12px 18px; border-right:1px solid #d7dde5; font-weight:800; }
    .cashier-tab.active { background:#8a174f; color:#fff; }
    .folio-ledger th { background:#f5f5f5; color:#1f2937; border-bottom:1px solid #cfd6df; font-size:12px; text-transform:uppercase; }
    .folio-ledger td { font-size:13px; vertical-align:middle; }
    .payment-pad { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; padding:12px; }
    .payment-pad button, .payment-pad a, .payment-pad .pad-label { min-height:68px; border:1px solid #d7dde5; background:#fff; border-radius:5px; display:flex; align-items:center; justify-content:center; text-align:center; color:#27313f; font-weight:800; padding:8px; }
    .payment-pad .active { background:#8a174f; color:#fff; border-color:#8a174f; }
    .post-form { padding:12px; border-top:1px solid #e6ebf0; }
    .balance-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:12px; }
    .balance-box { background:#fff; border:1px solid #d7dde5; border-radius:5px; padding:10px; }
    @media(max-width:1199px){.cashier-shell{grid-template-columns:1fr}.payment-pad{grid-template-columns:repeat(2,1fr)}}
</style>
@endsection

@section('content')
<div class="page-wrapper folio-cashier">
    <div class="content container-fluid">
        <div class="cashier-top mb-0">
            <div><strong>PMS Cashier</strong> · Folio Settlement Desk</div>
            <div class="d-flex flex-wrap gap-2"><a href="{{ route('hotel.folios.index') }}" class="btn btn-sm btn-light">Folios</a><a href="{{ route('hotel.checkout.index', ['stay_id' => $folio->stay_id]) }}" class="btn btn-sm btn-warning">Checkout</a></div>
        </div>

        <div class="cashier-shell">
            <aside class="cashier-card cashier-side">
                <div class="cashier-room">{{ $folio->stay?->room?->room_number ?? '---' }}</div>
                <div class="text-muted mb-3">{{ $folio->stay?->room?->type?->name ?? 'Guest Room' }}</div>
                <div class="mb-2"><span class="cashier-chip">{{ strtoupper((string) $folio->status) }}</span></div>
                <hr>
                <p class="mb-2"><strong>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }}</strong></p>
                <p class="mb-2 text-muted">Reservation: {{ $folio->reservation?->reservation_number ?? 'Walk-In' }}</p>
                <p class="mb-2 text-muted">Check-in: {{ optional($folio->stay?->checkin_at)->format('d M Y H:i') ?: 'N/A' }}</p>
                <p class="mb-2 text-muted">Due-out: {{ optional($folio->stay?->expected_checkout_at)->format('d M Y H:i') ?: 'N/A' }}</p>
                <hr>
                <div class="d-flex justify-content-between"><span>Master Folio</span><strong>{{ $folio->folio_number }}</strong></div>
                <div class="d-flex justify-content-between"><span>Balance</span><strong class="{{ (float)$folio->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $folio->balance,2) }}</strong></div>
            </aside>

            <main class="cashier-card">
                <div class="cashier-tabs"><div class="cashier-tab active">Master Folio</div><div class="cashier-tab">Extra Folio</div><div class="cashier-tab">Package</div></div>
                <div class="p-3 table-responsive">
                    <table class="table table-sm folio-ledger align-middle mb-0">
                        <thead><tr><th>No.</th><th>Posted</th><th>Ref No.</th><th>Transaction</th><th>Charge</th><th>Payment</th><th>Balance</th></tr></thead>
                        <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ optional($item->service_date)->format('d/m/Y') }}</td>
                                <td>{{ $item->posting_key ?: ($item->service_code ?: '-') }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->ledger_charge > 0 ? number_format((float)$item->ledger_charge,2) : '-' }}</td>
                                <td>{{ $item->ledger_payment > 0 ? number_format((float)$item->ledger_payment,2) : '-' }}</td>
                                <td>{{ number_format((float)$item->ledger_running_balance,2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted py-4 text-center">No folio transactions posted yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="balance-strip px-3 pb-3">
                    <div class="balance-box"><small>Guest Balance</small><h5 class="mb-0">{{ number_format((float) $folio->balance,2) }}</h5></div>
                    <div class="balance-box"><small>Total Charges</small><h5 class="mb-0">{{ number_format((float) $folio->total_charges,2) }}</h5></div>
                    <div class="balance-box"><small>Total Payments</small><h5 class="mb-0">{{ number_format((float) $folio->total_payments,2) }}</h5></div>
                </div>
            </main>

            <aside class="cashier-card">
                <div class="payment-pad">
                    <a class="active" href="{{ route('hotel.checkout.index', ['stay_id' => $folio->stay_id]) }}">Payment</a>
                    <div class="pad-label">Charge</div>
                    <div class="pad-label">Deposit</div>
                    <div class="pad-label">Cash</div>
                    <div class="pad-label">Card / POS</div>
                    <div class="pad-label">Transfer</div>
                    <div class="pad-label">Room Service</div>
                    <div class="pad-label">Laundry</div>
                    <div class="pad-label">Minibar</div>
                </div>
                <form method="POST" action="{{ route('hotel.folios.items.store', $folio) }}" class="post-form row g-2">
                    @csrf
                    <div class="col-12"><strong>Post Charge</strong></div>
                    <div class="col-12"><input type="text" name="description" class="form-control" placeholder="Description" required></div>
                    <div class="col-6"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                    <div class="col-6"><input type="text" name="service_code" class="form-control" placeholder="Code"></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Post Charge</button></div>
                </form>
                <form method="POST" action="{{ route('hotel.folios.services.store', $folio) }}" class="post-form row g-2">
                    @csrf
                    <div class="col-12"><strong>Post Service</strong></div>
                    <div class="col-12"><select name="service_type" class="form-control"><option value="restaurant">Restaurant</option><option value="room_service">Room Service</option><option value="laundry">Laundry</option><option value="minibar">Minibar</option><option value="other">Other</option></select></div>
                    <div class="col-7"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                    <div class="col-5"><input type="number" step="0.001" name="quantity" class="form-control" placeholder="Qty"></div>
                    <div class="col-12"><input type="text" name="description" class="form-control" placeholder="Optional note"></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Post Service</button></div>
                </form>
            </aside>
        </div>
    </div>
</div>
@endsection
