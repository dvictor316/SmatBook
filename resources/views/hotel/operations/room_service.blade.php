@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3 class="mb-3">Room Service Tickets</h3>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Order</th><th>Qty</th><th>Amount</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($items as $item)
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
                        <tr><td colspan="7" class="text-muted">No room service entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
</div>
@endsection
