@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Walk-In Check-In</h3>
        <form method="POST" action="{{ route('hotel.walkin.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <select name="room_id" class="form-control">
                        @foreach(App\Models\HotelRoom::where('property_id', auth()->user()->branch_id)->get() as $room)
                            <option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->type?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><input type="datetime-local" name="expected_checkout_at" class="form-control" required></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary">Check In</button></div>
        </form>
    </div>
</div>
@endsection
