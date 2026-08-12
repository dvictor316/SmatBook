@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>New Reservation</h3>
        <form method="POST" action="{{ route('hotel.reservations.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4"><input type="date" name="arrival_date" class="form-control" required></div>
                <div class="col-md-4"><input type="date" name="departure_date" class="form-control" required></div>
                <div class="col-md-4"><input type="number" name="nights" class="form-control" value="1" required></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>
@endsection
