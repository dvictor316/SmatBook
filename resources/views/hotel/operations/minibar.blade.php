@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Minibar</h3>
                <p class="text-muted mb-0">Fast room consumption posting and recent minibar activity</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Current In-House Rooms</h5></div>
                    <div class="card-body">
                        @forelse($activeStays as $stay)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">Room {{ $stay->room?->room_number ?? 'N/A' }}</div>
                                <div class="small">{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Guest' }}</div>
                                <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-outline-primary mt-2">Open Folio</a>
                            </div>
                        @empty
                            <div class="alert alert-light mb-0">No active stays for minibar posting.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recent Minibar Postings</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead>
                            <tbody>
                            @forelse($entries as $item)
                                <tr>
                                    <td>#{{ $item->folio?->stay_id }}</td>
                                    <td>{{ $item->folio?->customer?->customer_name ?? $item->folio?->customer?->name ?? 'N/A' }}</td>
                                    <td>{{ $item->folio?->stay?->room?->room_number ?? 'N/A' }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format((float) ($item->line_total ?? $item->amount ?? 0),2) }}</td>
                                    <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No minibar charges found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
@endsection
