@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="page-header"><h3>Edit Room</h3></div>

    <form method="POST" action="{{ route('hotel.rooms.update', $room) }}">@csrf @method('PUT')
        <div class="card"><div class="card-body">
            <div class="mb-3"><label>Room Number</label><input name="room_number" class="form-control" value="{{ old('room_number', $room->room_number) }}" required></div>
            <div class="mb-3"><label>Room Type</label>
                <select name="room_type_id" class="form-select">
                    <option value="">--</option>
                    @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}" @if($room->room_type_id == $rt->id) selected @endif>{{ $rt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label>Floor</label><input name="floor" class="form-control" value="{{ old('floor', $room->floor) }}"></div>
            <div class="mb-3"><label>Wing</label><input name="wing" class="form-control" value="{{ old('wing', $room->wing) }}"></div>
            <div class="mb-3"><label>Base Rate Override</label><input name="base_rate_override" class="form-control" type="number" step="0.01" value="{{ old('base_rate_override', $room->base_rate_override) }}"></div>
            <div class="text-end"><button class="btn btn-primary">Save</button></div>
        </div></div>
    </form>

</div></div>
@endsection
