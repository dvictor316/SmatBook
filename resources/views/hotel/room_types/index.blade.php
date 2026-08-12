@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3>Room Types</h3>
            <a href="{{ route('hotel.room_types.create') }}" class="btn btn-primary float-end">New Room Type</a>
        </div>

        <div class="card">
            <div class="card-body">
                @if($types->count() === 0)
                    <div class="alert alert-info">No room types have been configured yet.</div>
                @else
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Beds</th><th>Max Occupancy</th><th>Base Rate</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($types as $type)
                                <tr>
                                    <td>{{ $type->name }}</td>
                                    <td>{{ $type->beds }}</td>
                                    <td>{{ $type->max_occupancy }}</td>
                                    <td>{{ number_format($type->base_rate,2) }}</td>
                                    <td>
                                        <a href="{{ route('hotel.room_types.edit', $type) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form method="POST" action="{{ route('hotel.room_types.destroy', $type) }}" style="display:inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Deactivate</button></form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $types->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
