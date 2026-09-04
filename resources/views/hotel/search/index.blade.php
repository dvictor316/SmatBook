@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page">
        <div class="hotel-type-header">
            <div>
                <span class="hotel-type-label"><i class="fe fe-search"></i> Hotel Command Search</span>
                <h2>Global Hotel Search</h2>
                <p>Find guests, reservations, rooms, folios, receipts, then open the operational record directly.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-primary"><i class="fas fa-briefcase me-1"></i> Front Desk</a>
                <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Results</button>
            </div>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-lg-8">
                <input type="text" name="q" class="form-control" value="{{ $term }}" placeholder="Guest, phone, email, reservation no, room no, folio no, invoice, receipt">
            </div>
            <div class="col-auto"><button class="btn btn-primary"><i class="fas fa-search me-1"></i> Search</button></div>
        </form>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Guests</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th></th></tr></thead>
                            <tbody>
                            @forelse($results['guests'] as $guest)
                                <tr>
                                    <td>{{ $guest->customer_name }}</td>
                                    <td>{{ $guest->phone }}</td>
                                    <td>{{ $guest->email }}</td>
                                    <td><a href="{{ route('hotel.guests', ['q' => $guest->customer_name]) }}" class="btn btn-sm btn-light">Profiles</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No guest results.</td></tr>
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
                            <thead><tr><th>Room</th><th>Type</th><th></th></tr></thead>
                            <tbody>
                            @forelse($results['rooms'] as $room)
                                <tr>
                                    <td>{{ $room->room_number }}</td>
                                    <td>{{ $room->type?->name ?? 'N/A' }}</td>
                                    <td><a href="{{ route('hotel.rooms.show', $room) }}" class="btn btn-sm btn-light">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No room results.</td></tr>
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
                            <thead><tr><th>Folio</th><th>Guest</th><th></th></tr></thead>
                            <tbody>
                            @forelse($results['folios'] as $folio)
                                <tr>
                                    <td><a href="{{ route('hotel.folios.show', $folio) }}">{{ $folio->folio_number }}</a></td>
                                    <td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td>
                                    <td><a href="{{ route('hotel.folios.show', $folio) }}" class="btn btn-sm btn-light">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No folio results.</td></tr>
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
                            <thead><tr><th>Description</th><th>Amount</th><th></th></tr></thead>
                            <tbody>
                            @forelse($results['receipts'] as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ number_format((float)$item->amount, 2) }}</td>
                                    <td><a href="{{ route('hotel.folios.items.receipt', $item) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">Receipt</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No receipt results.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
@endsection
