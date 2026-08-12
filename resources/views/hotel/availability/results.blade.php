@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Available Rooms</h3>
        <div class="row">
            @forelse($rooms as $room)
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Room {{ $room->room_number }}</h5>
                            <p>{{ $room->type?->name }}</p>
                            <a href="{{ route('hotel.walkin.create') }}" class="btn btn-sm btn-success">Walk-In</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">No rooms available</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
