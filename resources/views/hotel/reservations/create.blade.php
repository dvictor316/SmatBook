@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>New Reservation</h3>
        @if(!$property)
            <div class="alert alert-warning">No hotel property is mapped to your current branch yet. Complete Hotel setup first.</div>
        @endif
        <form method="POST" action="{{ route('hotel.reservations.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3"><label>Arrival</label><input type="date" name="arrival_date" class="form-control" required></div>
                <div class="col-md-3"><label>Departure</label><input type="date" name="departure_date" class="form-control" required></div>
                <div class="col-md-2"><label>Adults</label><input type="number" name="adults" class="form-control" value="1" min="1"></div>
                <div class="col-md-2"><label>Children</label><input type="number" name="children" class="form-control" value="0" min="0"></div>
                <div class="col-md-2"><label>Nights</label><input type="number" name="nights" class="form-control" value="1" min="1"></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <label>Room Type</label>
                    <select name="room_type_id" class="form-control">
                        <option value="">Select room type</option>
                        @foreach($roomTypes as $roomType)
                            <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label>Nightly Rate</label><input type="number" step="0.01" name="nightly_rate" class="form-control" value="0"></div>
                <div class="col-md-4"><label>Deposit Required</label><input type="number" step="0.01" name="deposit_required" class="form-control" value="0"></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>
@endsection
