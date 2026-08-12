@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3>Hotel Setup — Room Types</h3>
        </div>

        <div class="card">
            <div class="card-body">
                <p>Create room types which define occupancy and base rates.</p>
                <a href="{{ route('hotel.room_types.index') }}" class="btn btn-primary">Manage Room Types</a>
                <a href="{{ route('hotel.rooms.index') }}" class="btn btn-secondary">Manage Rooms</a>
            </div>
        </div>
    </div>
</div>
@endsection
