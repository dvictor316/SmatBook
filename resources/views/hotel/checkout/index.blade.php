@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Checkout Desk</h3>
            <a href="{{ route('hotel.in_house') }}" class="btn btn-outline-secondary">In-House Guests</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Open Stays</small><h5 class="mb-0">{{ $stays->count() }}</h5></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Selected Stay</small><h5 class="mb-0">{{ $selectedStay?->id ? '#'.$selectedStay->id : 'None' }}</h5></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Folio Charges</small><h5 class="mb-0">{{ number_format((float) ($selectedFolio?->total_charges ?? 0), 2) }}</h5></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Folio Balance</small><h5 class="mb-0">{{ number_format((float) ($selectedFolio?->balance ?? 0), 2) }}</h5></div></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Stays Ready For Checkout</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Check In</th><th>Action</th></tr></thead>
                            <tbody>
                            @forelse($stays as $stay)
                                <tr>
                                    <td>#{{ $stay->id }}</td>
                                    <td>{{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'N/A' }}</td>
                                    <td>{{ $stay->room?->room_number ?? 'N/A' }}</td>
                                    <td>{{ optional($stay->check_in_at)->format('d M Y H:i') }}</td>
                                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}">Settle</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No active stays found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Settlement</h5></div>
                    <div class="card-body">
                        @if(!$selectedStay)
                            <div class="alert alert-info mb-0">Select a stay from the list to process checkout.</div>
                        @else
                            <p class="mb-2"><strong>Guest:</strong> {{ $selectedStay->customer?->customer_name ?? $selectedStay->customer?->name ?? 'N/A' }}</p>
                            <p class="mb-2"><strong>Room:</strong> {{ $selectedStay->room?->room_number ?? 'N/A' }}</p>
                            <p class="mb-3"><strong>Net Due:</strong> {{ number_format((float) ($selectedFolio?->balance ?? 0), 2) }}</p>

                            <form method="POST" action="{{ route('hotel.checkout', $selectedStay) }}" class="row g-2">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label">Payment Method</label>
                                    <select name="settlement_method" class="form-control" required>
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="pos">POS</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Amount Paid</label>
                                    <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-warning">Complete Checkout</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $stays->links() }}</div>
    </div>
</div>
@endsection
