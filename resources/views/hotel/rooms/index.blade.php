@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3>Rooms</h3>
            <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary float-end">New Room</a>
        </div>

        <div class="card"><div class="card-body">
            <table class="table">
                <thead><tr><th>Room #</th><th>Type</th><th>Floor</th><th>Status</th><th>Housekeeping</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->type?->name }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ ucfirst($room->operational_status) }}</td>
                            <td>{{ ucfirst($room->housekeeping_status) }}</td>
                            <td>
                                <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-info">Edit</a>
                                <form method="POST" action="{{ route('hotel.rooms.destroy', $room) }}" style="display:inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Deactivate</button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $rooms->links() }}
        </div></div>
    </div>
</div>
@endsection
