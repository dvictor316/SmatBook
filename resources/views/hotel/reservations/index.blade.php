@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Reservations</h3>
        <div class="d-flex justify-content-between mb-3">
            <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary">New Reservation</a>
        </div>

        <form method="GET" class="row g-2 mb-3">
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
                <button class="btn btn-outline-primary">Filter</button>
            </div>
        </form>

        @if($reservations->count() === 0)
            <div class="alert alert-info">No reservations found.</div>
        @else
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Reservation</th><th>Guest</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($reservations as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td><a href="{{ route('hotel.reservations.show', $r) }}">{{ $r->reservation_number }}</a></td>
                        <td>{{ $r->customer?->name ?? 'N/A' }}</td>
                        <td>{{ optional($r->arrival_date)->toDateString() }}</td>
                        <td>{{ optional($r->departure_date)->toDateString() }}</td>
                        <td>{{ ucfirst(str_replace('_',' ', $r->status)) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $reservations->links() }}
        @endif
    </div>
</div>
@endsection
