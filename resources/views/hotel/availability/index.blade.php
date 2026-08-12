@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Availability Search</h3>
            <a href="{{ route('hotel.reservations.create') }}" class="btn btn-outline-primary">New Reservation</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hotel.availability.search') }}" class="row g-3 align-items-end">
            @csrf
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Arrival</label>
                        <input type="date" name="arrival_date" class="form-control" required>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Departure</label>
                        <input type="date" name="departure_date" class="form-control" required>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" min="1" value="1" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" min="0" value="0" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Property</label>
                        <input type="hidden" name="property_id" value="{{ $property?->id }}">
                        <input type="text" class="form-control" value="{{ $property?->name ?? 'Current Property' }}" disabled>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary">Search Rooms</button>
                        <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Calendar / Timeline</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-light mt-3 mb-0">
            Use this booking search to find available inventory and proceed directly to reservation or walk-in workflows.
            Results show room type, capacity, rates, stay nights, and estimated totals.
        </div>
    </div>
</div>
@endsection
