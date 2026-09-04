@extends('layout.mainlayout')

@section('style')
<style>
    .service-center-page { background:#f4f7fb; color:#10233f; }
    .service-center-hero { background:linear-gradient(135deg,#082f55,#0b5fb8 58%,#0f766e); color:#fff; border-radius:8px; padding:18px 20px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap; }
    .service-center-hero h3 { color:#fff; margin:0; font-size:28px; font-weight:800; }
    .service-center-hero .btn { min-height:34px; padding:6px 12px; border-radius:8px; font-size:13px; font-weight:800; line-height:1.2; }
    .service-center-hero .btn-light:hover { background:#fff; color:#10233f; border-color:#fff; }
    .service-center-card { background:#fff; border:1px solid #d6e1ee; border-radius:8px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .service-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .service-metric { padding:14px; }
    .service-metric span { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:12px; font-weight:700; }
    .service-metric strong { display:block; font-size:24px; line-height:1.08; margin-top:7px; color:#061b33; }
    .service-flow { display:grid; grid-template-columns:240px minmax(0,1fr); gap:14px; }
    .service-workspace { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:14px; }
    .service-rail { background:#0b2f54; color:#dbeafe; border-radius:8px; padding:14px; }
    .service-rail h5 { color:#fff; text-transform:uppercase; letter-spacing:.1em; font-size:14px; }
    .service-rail div { padding:12px 0; border-bottom:1px solid rgba(255,255,255,.14); }
    .service-table th { background:#0c3f70; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    .service-table td { vertical-align:middle; }
    @media(max-width:1199px){.service-workspace{grid-template-columns:1fr}}
    @media(max-width:991px){.service-metrics,.service-flow{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper service-center-page">
    <div class="content container-fluid">
        <section class="service-center-hero">
            <div><small class="text-warning fw-semibold">HOTEL SERVICE CENTER</small><h3>{{ $meta['title'] }}</h3><p class="mb-0">{{ $meta['description'] }}</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.restaurant.pos') }}" class="btn btn-light">Open POS</a>
                <a href="{{ route('hotel.folios.index') }}" class="btn btn-warning">Folios</a>
                <a href="{{ route('hotel.reports.index') }}" class="btn btn-outline-light">Reports</a>
            </div>
        </section>

        <div class="hotel-op-kpis">
            <div class="hotel-op-kpi"><span>Total Posted</span><strong>{{ number_format((float) $total, 2) }}</strong></div>
            <div class="hotel-op-kpi"><span>Transactions</span><strong>{{ $items->total() }}</strong></div>
            <div class="hotel-op-kpi"><span>Open Folios</span><strong>{{ $activeFolios->count() }}</strong></div>
            <div class="hotel-op-kpi"><span>Posting Codes</span><strong>{{ implode(', ', $meta['codes']) }}</strong></div>
        </div>

        @include('hotel.partials.operations-action-deck', [
            'context' => $center,
            'title' => $meta['title'] . ' Operating Actions',
            'subtitle' => 'Post guest charges, review folios, settle checkout and keep the department tied to hotel revenue.'
        ])

        <div class="service-flow">
            <aside class="service-rail hotel-service-theme-{{ $meta['theme'] ?? $center }}"><h5>{{ $meta['mode'] ?? 'Service Workflow' }}</h5>@foreach(($meta['actions'] ?? ['Take order', 'Post sale', 'Print receipt']) as $i => $action)<div><strong>{{ $i + 1 }} {{ $action }}</strong><br>{{ $meta['description'] }}</div>@endforeach</aside>
            <main class="service-workspace">
                <section class="service-center-card p-3">
                    <h5 class="mb-3">{{ $meta['title'] }} Activity</h5>
                    <div class="table-responsive"><table class="table table-sm service-table align-middle mb-0"><thead><tr><th>Guest</th><th>Room</th><th>Description</th><th>Qty</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td><td>Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 2) }}</td><td>{{ optional($item->service_date)->format('d M Y') }}</td><td><a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="{{ route('hotel.folios.items.receipt', $item) }}"><i class="fas fa-print me-1"></i> Receipt</a></td></tr>@empty<tr><td colspan="7" class="text-muted">No {{ strtolower($meta['title']) }} postings found yet.</td></tr>@endforelse</tbody></table></div><div class="mt-3">{{ $items->links() }}</div>
                </section>
                <aside class="service-center-card p-3">
                    <h5 class="mb-3">Post {{ $meta['title'] }} Charge</h5>
                    @if($activeFolios->isEmpty())
                        <div class="alert alert-light mb-0">No open guest folios are available for room charging.</div>
                    @else
                        <form method="POST" action="{{ route('hotel.service_centers.charges.store', $center) }}" data-service-charge-form>
                            @csrf
                            <div class="mb-2"><label class="form-label">Guest / Room</label><select name="folio_id" class="form-select" required>@foreach($activeFolios as $folio)<option value="{{ $folio->id }}">{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }} - Room {{ $folio->stay?->room?->room_number ?? 'N/A' }} - {{ $folio->folio_number }}</option>@endforeach</select></div>
                            <div class="mb-2"><label class="form-label">Item / Service</label><input name="description" class="form-control" placeholder="{{ $center === 'ticketing' ? 'VIP dinner ticket' : 'Service item' }}" required></div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label">Qty</label><input name="quantity" class="form-control" type="number" step="0.001" min="0.001" value="1" data-service-quantity></div>
                                <div class="col-6"><label class="form-label">Unit Price</label><input name="unit_price" class="form-control" type="number" step="0.01" min="0.01" value="0" data-service-unit></div>
                                <div class="col-6"><label class="form-label">Discount</label><input name="discount" class="form-control" type="number" step="0.01" min="0" value="0" data-service-discount></div>
                                <div class="col-6"><label class="form-label">Tax</label><input name="tax" class="form-control" type="number" step="0.01" min="0" value="0" data-service-tax></div>
                            </div>
                            <div class="my-2"><label class="form-label">Payment</label><select name="payment_mode" class="form-select"><option value="charge_to_room">Charge to Room</option><option value="cash">Cash Paid</option><option value="card">Card Paid</option><option value="transfer">Transfer Paid</option><option value="other">Other Paid</option></select></div>
                            <div class="mb-2"><label class="form-label">Date</label><input name="service_date" class="form-control" type="date" value="{{ now()->toDateString() }}"></div>
                            <div class="mb-3"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                            <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted">Total</span><strong data-service-total>0.00</strong></div>
                            <button class="btn btn-primary w-100">Post Charge</button>
                        </form>
                    @endif
                </aside>
            </main>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const form = document.querySelector('[data-service-charge-form]');
    if (!form) return;
    const qty = form.querySelector('[data-service-quantity]');
    const unit = form.querySelector('[data-service-unit]');
    const discount = form.querySelector('[data-service-discount]');
    const tax = form.querySelector('[data-service-tax]');
    const total = form.querySelector('[data-service-total]');
    function money(value) {
        return Number.isFinite(value) ? value.toFixed(2) : '0.00';
    }
    function recalc() {
        const amount = Math.max(0, (parseFloat(qty.value) || 0) * (parseFloat(unit.value) || 0) + (parseFloat(tax.value) || 0) - (parseFloat(discount.value) || 0));
        total.textContent = money(amount);
    }
    [qty, unit, discount, tax].forEach((input) => input.addEventListener('input', recalc));
    recalc();
})();
</script>
@endsection
