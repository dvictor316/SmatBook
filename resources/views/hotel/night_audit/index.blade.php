@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="mb-0">Night Audit</h3>
                <p class="text-muted mb-0">Close and reconcile the hotel business day</p>
            </div>
            <span class="badge bg-dark">Business Date {{ \Carbon\Carbon::parse($businessDate)->format('d M Y') }}</span>
        </div>

        @if($blockingIssues->isNotEmpty())
            <div class="alert alert-warning">
                <strong>Attention:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($blockingIssues as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Arrivals Expected</small><h4>{{ $arrivalsExpected }}</h4><div class="small">Checked In: {{ $arrivalsCheckedIn }} | Pending: {{ $arrivalsPending }}</div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Departures Expected</small><h4>{{ $departuresExpected }}</h4><div class="small">Checked Out: {{ $departuresCheckedOut }} | Pending: {{ $departuresPending }}</div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Open Folios</small><h4>{{ $financial['open_folios'] }}</h4><div class="small">Outstanding: {{ number_format((float) $financial['outstanding_balances'], 2) }}</div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Payments Today</small><h4>{{ number_format((float) $financial['payments_today'], 2) }}</h4><div class="small">Room Charges Pending: {{ $financial['room_charges_pending'] }}</div></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Arrivals</h5></div><div class="card-body"><div class="d-flex justify-content-between"><span>Expected</span><strong>{{ $arrivalsExpected }}</strong></div><div class="d-flex justify-content-between"><span>Checked In</span><strong>{{ $arrivalsCheckedIn }}</strong></div><div class="d-flex justify-content-between"><span>Pending</span><strong>{{ $arrivalsPending }}</strong></div></div></div></div>
            <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Departures</h5></div><div class="card-body"><div class="d-flex justify-content-between"><span>Expected</span><strong>{{ $departuresExpected }}</strong></div><div class="d-flex justify-content-between"><span>Checked Out</span><strong>{{ $departuresCheckedOut }}</strong></div><div class="d-flex justify-content-between"><span>Pending</span><strong>{{ $departuresPending }}</strong></div></div></div></div>
            <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Room Status</h5></div><div class="card-body"><div class="d-flex justify-content-between"><span>Occupied</span><strong>{{ $roomStatus['occupied'] }}</strong></div><div class="d-flex justify-content-between"><span>Dirty</span><strong>{{ $roomStatus['dirty'] }}</strong></div><div class="d-flex justify-content-between"><span>Maintenance</span><strong>{{ $roomStatus['maintenance'] }}</strong></div><div class="d-flex justify-content-between"><span>Out of Order</span><strong>{{ $roomStatus['out_of_order'] }}</strong></div></div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Run Night Audit</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('hotel.night_audit.run') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4"><label class="form-label">Audit Date</label><input type="date" name="audit_date" class="form-control" value="{{ $businessDate }}"></div>
                    <div class="col-md-3 form-check mt-4"><input class="form-check-input" type="checkbox" name="force" value="1" id="force"><label class="form-check-label" for="force">Allow force run</label></div>
                    <div class="col-md-3"><button class="btn btn-primary">Run Night Audit</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Audit History</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Status</th><th>Stays</th><th>Charges Posted</th><th>Skipped</th><th>Total Amount</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($audits as $audit)
                        <tr>
                            <td>{{ optional($audit->audit_date)->format('d M Y') }}</td>
                            <td>{{ ucfirst((string) $audit->status) }}</td>
                            <td>{{ $audit->stays_scanned }}</td>
                            <td>{{ $audit->charges_posted }}</td>
                            <td>{{ $audit->charges_skipped }}</td>
                            <td>{{ number_format((float) $audit->total_amount, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('hotel.night_audit.reopen', $audit) }}">@csrf<button class="btn btn-sm btn-outline-warning">Reopen</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No night audits have been run yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
