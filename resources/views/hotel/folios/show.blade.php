@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Folio {{ $folio->folio_number }}</h3>
                <p class="text-muted mb-0">Guest account activity and running balance</p>
            </div>
            <a href="{{ route('hotel.checkout.index', ['stay_id' => $folio->stay_id]) }}" class="btn btn-outline-warning">Checkout / Settlement</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><strong>Guest:</strong> {{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</div>
                            <div class="col-md-4"><strong>Room:</strong> {{ $folio->stay?->room?->room_number ?? 'N/A' }}</div>
                            <div class="col-md-4"><strong>Status:</strong> {{ strtoupper((string) $folio->status) }}</div>
                            <div class="col-md-4"><strong>Check-In:</strong> {{ optional($folio->stay?->checkin_at)->format('d M Y') }}</div>
                            <div class="col-md-4"><strong>Checkout:</strong> {{ optional($folio->stay?->expected_checkout_at)->format('d M Y') }}</div>
                            <div class="col-md-4"><strong>Reservation:</strong> {{ $folio->reservation?->reservation_number ?? 'Walk-In' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between"><span>Total Charges</span><strong>{{ number_format((float) $folio->total_charges,2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Deposits</span><strong>{{ number_format((float) $folio->opening_deposit,2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Payments</span><strong>{{ number_format((float) $folio->total_payments,2) }}</strong></div>
                        <hr>
                        <div class="d-flex justify-content-between"><span>BALANCE</span><strong class="{{ (float)$folio->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $folio->balance,2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Add Charge</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hotel.folios.items.store', $folio) }}" class="row g-2">
                            @csrf
                            <div class="col-12"><input type="text" name="description" class="form-control" placeholder="Description" required></div>
                            <div class="col-6"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                            <div class="col-6"><input type="text" name="service_code" class="form-control" placeholder="Dept code"></div>
                            <div class="col-12"><button class="btn btn-primary">Post Charge</button></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Post Service</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hotel.folios.services.store', $folio) }}" class="row g-2">
                            @csrf
                            <div class="col-12"><select name="service_type" class="form-control"><option value="restaurant">Restaurant</option><option value="room_service">Room Service</option><option value="laundry">Laundry</option><option value="minibar">Minibar</option><option value="other">Other</option></select></div>
                            <div class="col-12"><input type="text" name="description" class="form-control" placeholder="Description"></div>
                            <div class="col-6"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                            <div class="col-6"><input type="number" step="0.001" name="quantity" class="form-control" placeholder="Qty"></div>
                            <div class="col-12"><button class="btn btn-outline-primary">Post Service</button></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Actions</h5></div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('hotel.checkout.index', ['stay_id' => $folio->stay_id]) }}" class="btn btn-outline-warning">Receive Payment / Checkout</a>
                        <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-secondary">Back to Folios</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Transaction Ledger</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Description</th><th>Department</th><th>Reference</th><th>Charge</th><th>Payment</th><th>Running Balance</th></tr></thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ optional($item->service_date)->format('d M Y') }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ strtoupper((string) ($item->service_code ?? $item->type)) }}</td>
                            <td>{{ $item->posting_key ?: '-' }}</td>
                            <td>{{ $item->ledger_charge > 0 ? number_format((float)$item->ledger_charge,2) : '-' }}</td>
                            <td>{{ $item->ledger_payment > 0 ? number_format((float)$item->ledger_payment,2) : '-' }}</td>
                            <td>{{ number_format((float)$item->ledger_running_balance,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No folio transactions posted yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
