@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Global Hotel Search</h3>
            <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Back to Front Desk</a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-lg-8">
                <input type="text" name="q" class="form-control" value="{{ $term }}" placeholder="Guest, phone, email, reservation no, room no, folio no, invoice, receipt">
            </div>
            <div class="col-auto"><button class="btn btn-primary">Search</button></div>
        </form>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Guests</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Name</th><th>Phone</th><th>Email</th></tr></thead>
                            <tbody>
                            @forelse($results['guests'] as $guest)
                                <tr><td>{{ $guest->customer_name }}</td><td>{{ $guest->phone }}</td><td>{{ $guest->email }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No guest results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Reservations</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>No.</th><th>Guest</th><th>Room</th><th></th></tr></thead>
                            <tbody>
                            @forelse($results['reservations'] as $reservation)
                                <tr>
                                    <td>{{ $reservation->reservation_number }}</td>
                                    <td>{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'N/A' }}</td>
                                    <td>{{ $reservation->room?->room_number ?? 'Unassigned' }}</td>
                                    <td><a href="{{ route('hotel.reservations.show', $reservation) }}" class="btn btn-sm btn-light">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No reservation results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Rooms</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Room</th><th>Type</th></tr></thead>
                            <tbody>
                            @forelse($results['rooms'] as $room)
                                <tr><td>{{ $room->room_number }}</td><td>{{ $room->type?->name ?? 'N/A' }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No room results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Folios</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Folio</th><th>Guest</th></tr></thead>
                            <tbody>
                            @forelse($results['folios'] as $folio)
                                <tr><td><a href="{{ route('hotel.folios.show', $folio) }}">{{ $folio->folio_number }}</a></td><td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No folio results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Receipts / Payments</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Description</th><th>Amount</th></tr></thead>
                            <tbody>
                            @forelse($results['receipts'] as $item)
                                <tr><td>{{ $item->description }}</td><td>{{ number_format((float)$item->amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No receipt results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
