@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Room Availability</h3>
        <form method="POST" action="{{ route('hotel.availability.search') }}">
            @csrf
            <div class="row">
                <div class="col-md-4"><input type="date" name="arrival_date" class="form-control" required></div>
                <div class="col-md-4"><input type="date" name="departure_date" class="form-control" required></div>
                <div class="col-md-4"><button class="btn btn-primary">Search</button></div>
            </div>
        </form>
    </div>
</div>
@endsection
