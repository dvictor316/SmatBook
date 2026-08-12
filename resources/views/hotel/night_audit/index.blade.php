@extends('layout.mainlayout')

@section('style')
<style>
    .audit-page { background:#071d35; color:#dbeafe; }
    .audit-hero { background:linear-gradient(135deg,#082f55,#0f172a); border:1px solid rgba(255,255,255,.14); border-radius:18px; padding:20px; margin-bottom:16px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .audit-hero h3 { color:#fff; font-weight:900; margin:0; }
    .audit-hero p { color:#cbd5e1; margin:5px 0 0; }
    .audit-date { background:#f5c451; color:#111827; border-radius:999px; padding:9px 14px; font-weight:900; }
    .audit-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .audit-kpi { background:#fff; color:#0f172a; border-radius:16px; padding:16px; border:1px solid #d9e4ef; }
    .audit-kpi span { display:block; color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:11px; font-weight:900; }
    .audit-kpi strong { display:block; font-size:30px; line-height:1; margin:8px 0; }
    .audit-grid { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(340px,.85fr); gap:16px; }
    .audit-panel { background:#fff; color:#172033; border:1px solid #d9e4ef; border-radius:16px; overflow:hidden; box-shadow:0 16px 34px rgba(0,0,0,.16); }
    .audit-panel-head { padding:15px 17px; background:#f8fafc; border-bottom:1px solid #e5edf6; display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; }
    .audit-checks { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; padding:16px; }
    .audit-check { border:1px solid #e5edf6; border-left:5px solid #0b5fb8; border-radius:14px; padding:14px; background:#fff; }
    .audit-check.warning { border-left-color:#d4a23a; background:#fffaf0; }
    .audit-check.danger { border-left-color:#dc2626; background:#fff5f5; }
    .audit-run { padding:16px; background:#f8fafc; border-top:1px solid #e5edf6; }
    .audit-table th { background:#0b2f54; color:#fff; font-size:12px; text-transform:uppercase; }
    @media(max-width:1199px){.audit-grid{grid-template-columns:1fr}.audit-kpis,.audit-checks{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.audit-kpis,.audit-checks{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper audit-page">
    <div class="content container-fluid">
        <section class="audit-hero">
            <div><h3>Night Audit Command Center</h3><p>Close the hotel business day after checking arrivals, departures, folios, room status, and payments.</p></div>
            <span class="audit-date">Business Date {{ \Carbon\Carbon::parse($businessDate)->format('d M Y') }}</span>
        </section>

        @if($blockingIssues->isNotEmpty())
            <div class="alert alert-warning"><strong>Close-day attention required:</strong><ul class="mb-0 mt-2">@foreach($blockingIssues as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
        @endif

        <div class="audit-kpis">
            <div class="audit-kpi"><span>Arrivals Expected</span><strong>{{ $arrivalsExpected }}</strong><small>{{ $arrivalsCheckedIn }} checked in · {{ $arrivalsPending }} pending</small></div>
            <div class="audit-kpi"><span>Departures Expected</span><strong>{{ $departuresExpected }}</strong><small>{{ $departuresCheckedOut }} checked out · {{ $departuresPending }} pending</small></div>
            <div class="audit-kpi"><span>Open Folios</span><strong>{{ $financial['open_folios'] }}</strong><small>Outstanding {{ number_format((float) $financial['outstanding_balances'], 2) }}</small></div>
            <div class="audit-kpi"><span>Payments Today</span><strong>{{ number_format((float) $financial['payments_today'], 2) }}</strong><small>Room charges pending: {{ $financial['room_charges_pending'] }}</small></div>
        </div>

        <div class="audit-grid">
            <main class="audit-panel">
                <div class="audit-panel-head"><div><strong>Pre-Audit Checklist</strong><div class="small text-muted">Operational counters that determine whether the day is safe to close.</div></div><span class="badge bg-dark">{{ $blockingIssues->count() }} blocker(s)</span></div>
                <div class="audit-checks">
                    <div class="audit-check {{ $arrivalsPending > 0 ? 'warning' : '' }}"><span class="text-muted small">Arrivals</span><h5>{{ $arrivalsCheckedIn }} / {{ $arrivalsExpected }} checked in</h5><p class="mb-0 text-muted">Pending arrivals should be resolved or marked no-show.</p></div>
                    <div class="audit-check {{ $departuresPending > 0 ? 'warning' : '' }}"><span class="text-muted small">Departures</span><h5>{{ $departuresCheckedOut }} / {{ $departuresExpected }} checked out</h5><p class="mb-0 text-muted">Pending departures may keep rooms occupied.</p></div>
                    <div class="audit-check {{ (float)$financial['outstanding_balances'] > 0 ? 'danger' : '' }}"><span class="text-muted small">Financial</span><h5>{{ number_format((float) $financial['outstanding_balances'], 2) }} outstanding</h5><p class="mb-0 text-muted">Review open folios before close-day posting.</p></div>
                    <div class="audit-check {{ ($roomStatus['dirty'] + $roomStatus['maintenance'] + $roomStatus['out_of_order']) > 0 ? 'warning' : '' }}"><span class="text-muted small">Rooms</span><h5>{{ $roomStatus['occupied'] }} occupied · {{ $roomStatus['dirty'] }} dirty</h5><p class="mb-0 text-muted">Maintenance and dirty rooms carry into tomorrow.</p></div>
                </div>
                <div class="audit-run">
                    <form method="POST" action="{{ route('hotel.night_audit.run') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5"><label class="form-label">Audit Date</label><input type="date" name="audit_date" class="form-control" value="{{ $businessDate }}"></div>
                        <div class="col-md-3 form-check mt-4"><input class="form-check-input" type="checkbox" name="force" value="1" id="force"><label class="form-check-label" for="force">Allow force run</label></div>
                        <div class="col-md-4"><button class="btn btn-warning w-100">Run Night Audit</button></div>
                    </form>
                </div>
            </main>

            <aside class="audit-panel">
                <div class="audit-panel-head"><strong>Audit History</strong><span class="badge bg-light text-dark">Recent closes</span></div>
                <div class="table-responsive">
                    <table class="table table-sm audit-table align-middle mb-0"><thead><tr><th>Date</th><th>Status</th><th>Posted</th><th>Total</th><th>Action</th></tr></thead><tbody>
                    @forelse($audits as $audit)
                        <tr><td>{{ optional($audit->audit_date)->format('d M Y') }}</td><td>{{ ucfirst((string) $audit->status) }}</td><td>{{ $audit->charges_posted }} / {{ $audit->stays_scanned }}</td><td>{{ number_format((float) $audit->total_amount, 2) }}</td><td><form method="POST" action="{{ route('hotel.night_audit.reopen', $audit) }}">@csrf<button class="btn btn-sm btn-outline-warning">Reopen</button></form></td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted p-4">No night audits have been run yet.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
