@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Laundry</h3>
                <p class="text-muted mb-0">Laundry order tracking and folio charge review</p>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Orders Logged</small><h4>{{ $orders->total() }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Recent Charges</small><h4>{{ $orders->count() }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Workflow</small><h4>Received to Delivered</h4></div></div></div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($orders as $item)
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
                        <tr><td colspan="7" class="text-muted">No laundry charges found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
