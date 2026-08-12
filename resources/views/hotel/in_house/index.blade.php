@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">In-House Guests</h3>
                <p class="text-muted mb-0">Active stay control and balance monitoring</p>
            </div>
            <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-warning">Open Checkout Desk</a>
        </div>
        @php $isPaginator = $stays instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
        @if(($isPaginator && $stays->count()===0) || (!$isPaginator && $stays->isEmpty()))
            <div class="alert alert-info">No in-house guests found.</div>
        @else
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Guest</th><th>Room</th><th>Check-In</th><th>Expected Checkout</th><th>Charges</th><th>Paid</th><th>Balance</th><th>Actions</th></tr></thead>
                        <tbody>
                        @foreach($stays as $stay)
                            <tr>
                                <td>{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Walk-In Guest' }}</td>
                                <td>{{ $stay->room?->room_number ?? 'N/A' }}</td>
                                <td>{{ optional($stay->checkin_at)->format('d M Y H:i') }}</td>
                                <td>{{ optional($stay->expected_checkout_at)->format('d M Y H:i') }}</td>
                                <td>{{ number_format((float) $stay->folio_charges, 2) }}</td>
                                <td>{{ number_format((float) $stay->folio_payments, 2) }}</td>
                                <td><span class="{{ (float)$stay->folio_balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $stay->folio_balance, 2) }}</span></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-light">Folio</a>
                                        <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-sm btn-outline-warning">Checkout</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($isPaginator)<div class="mt-3">{{ $stays->links() }}</div>@endif
        @endif
    </div>
</div>
@endsection
