@extends('layout.mainlayout')

@section('style')
<style>
    .service-center-page { background:#f4f7fb; color:#10233f; }
    .service-center-hero { background:linear-gradient(135deg,#082f55,#0b5fb8 58%,#0f766e); color:#fff; border-radius:18px; padding:22px; margin-bottom:16px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .service-center-hero h3 { color:#fff; margin:0; font-size:28px; font-weight:900; }
    .service-center-card { background:#fff; border:1px solid #d6e1ee; border-radius:16px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .service-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .service-metric { padding:16px; }
    .service-metric span { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:12px; font-weight:900; }
    .service-metric strong { display:block; font-size:28px; line-height:1; margin-top:8px; }
    .service-flow { display:grid; grid-template-columns:240px minmax(0,1fr); gap:14px; }
    .service-rail { background:#0b2f54; color:#dbeafe; border-radius:16px; padding:14px; }
    .service-rail h5 { color:#fff; text-transform:uppercase; letter-spacing:.1em; font-size:14px; }
    .service-rail div { padding:12px 0; border-bottom:1px solid rgba(255,255,255,.14); }
    .service-table th { background:#0c3f70; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    @media(max-width:991px){.service-metrics,.service-flow{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper service-center-page">
    <div class="content container-fluid">
        <section class="service-center-hero">
            <div><small class="text-warning fw-bold">HOTEL SERVICE CENTER</small><h3>{{ $meta['title'] }}</h3><p class="mb-0">{{ $meta['description'] }}</p></div>
            <div class="d-flex flex-wrap gap-2 align-self-start"><a href="{{ route('hotel.dashboard') }}" class="btn btn-light">Dashboard</a><a href="{{ route('hotel.folios.index') }}" class="btn btn-warning">Folios</a></div>
        </section>

        <div class="service-metrics">
            <div class="service-center-card service-metric"><span>Total Posted</span><strong>{{ number_format((float) $total, 2) }}</strong></div>
            <div class="service-center-card service-metric"><span>Transactions</span><strong>{{ $items->total() }}</strong></div>
            <div class="service-center-card service-metric"><span>Department</span><strong>{{ strtoupper($center) }}</strong></div>
            <div class="service-center-card service-metric"><span>Posting Codes</span><strong>{{ implode(', ', $meta['codes']) }}</strong></div>
        </div>

        <div class="service-flow">
            <aside class="service-rail"><h5>Service Workflow</h5><div><strong>1 Order</strong><br>Guest requests service from {{ strtolower($meta['title']) }}.</div><div><strong>2 Post</strong><br>Charge is posted to folio or POS.</div><div><strong>3 Review</strong><br>Cashier verifies service center revenue.</div><div><strong>4 Settle</strong><br>Balance clears at checkout/accounting.</div></aside>
            <main class="service-center-card p-3"><h5 class="mb-3">{{ $meta['title'] }} Activity</h5><div class="table-responsive"><table class="table table-sm service-table align-middle mb-0"><thead><tr><th>Guest</th><th>Room</th><th>Description</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td><td>Room {{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 2) }}</td><td>{{ optional($item->service_date)->format('d M Y') }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No {{ strtolower($meta['title']) }} postings found yet.</td></tr>@endforelse</tbody></table></div><div class="mt-3">{{ $items->links() }}</div></main>
        </div>
    </div>
</div>
@endsection
