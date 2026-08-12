@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Reservations Workspace</h3>
            <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary">New Reservation</a>
        </div>

        <form method="GET" class="row g-2 mb-3 align-items-end">
            <div class="col-md-3">
                <select name="property_id" class="form-control">
                    <option value="">All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ (int) request('property_id', $propertyId) === (int) $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    @foreach(['inquiry','reserved','confirmed','checked_in','completed','cancelled','no_show'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search reservation/guest/source">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary">Apply Filters</button>
            </div>
        </form>

        @if($reservations->count() === 0)
            <div class="alert alert-info">No reservations found for the selected period.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Reservation No.</th>
                        <th>Guest</th>
                        <th>Room Type</th>
                        <th>Room</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Nights</th>
                        <th>Total</th>
                        <th>Deposit</th>
                        <th>Balance</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($reservations as $r)
                        @php
                            $status = (string) $r->status;
                            $statusClass = match($status) {
                                'confirmed' => 'bg-primary',
                                'checked_in' => 'bg-success',
                                'cancelled' => 'bg-danger',
                                'no_show' => 'bg-dark',
                                'completed' => 'bg-secondary',
                                default => 'bg-info',
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('hotel.reservations.show', $r) }}">{{ $r->reservation_number }}</a></td>
                            <td>{{ $r->customer?->customer_name ?? $r->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $r->roomType?->name ?? 'N/A' }}</td>
                            <td>{{ $r->room?->room_number ?? 'Unassigned' }}</td>
                            <td>{{ optional($r->arrival_date)->format('d M Y') }}</td>
                            <td>{{ optional($r->departure_date)->format('d M Y') }}</td>
                            <td>{{ $r->nights }}</td>
                            <td>{{ number_format((float) $r->total, 2) }}</td>
                            <td>{{ number_format((float) $r->deposit_received, 2) }}</td>
                            <td>{{ number_format((float) $r->balance, 2) }}</td>
                            <td>{{ ucfirst((string) ($r->source ?: 'direct')) }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_',' ', $status)) }}</span></td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-light">View</a>
                                    <a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @if(in_array($status, ['reserved', 'confirmed']))
                                        <form method="POST" action="{{ route('hotel.checkin', $r) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">Check In</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $reservations->links() }}
        @endif
    </div>
</div>
@endsection
