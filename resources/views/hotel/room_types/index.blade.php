@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Room Types</h3>
                <p class="text-muted mb-0">Configuration of inventory, capacity, and rack pricing</p>
            </div>
            <a href="{{ route('hotel.room_types.create') }}" class="btn btn-primary">New Room Type</a>
        </div>

        <div class="card">
            <div class="card-body">
                @if($types->count() === 0)
                    <div class="alert alert-info">No room types have been configured yet.</div>
                @else
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Room Type</th><th>Bed Type</th><th>Beds</th><th>Capacity</th><th>Base Rate</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($types as $type)
                                <tr>
                                    <td>{{ $type->name }}</td>
                                    <td>{{ $type->bed_type ?: 'N/A' }}</td>
                                    <td>{{ $type->beds }}</td>
                                    <td>{{ $type->max_occupancy }}</td>
                                    <td>{{ number_format($type->base_rate,2) }}</td>
                                    <td><span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span></td>
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
