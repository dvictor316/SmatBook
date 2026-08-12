@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header"><h3>Edit Room Type</h3></div>

        <form method="POST" action="{{ route('hotel.room_types.update', $type) }}">
            @csrf
            @method('PUT')
            <div class="card"><div class="card-body">
                <div class="mb-3"><label>Name</label><input name="name" class="form-control" value="{{ old('name', $type->name) }}" required></div>
                <div class="mb-3"><label>Code</label><input name="code" class="form-control" value="{{ old('code', $type->code) }}"></div>
                <div class="mb-3"><label>Beds</label><input name="beds" class="form-control" type="number" value="{{ old('beds', $type->beds) }}"></div>
                <div class="mb-3"><label>Max Occupancy</label><input name="max_occupancy" class="form-control" type="number" value="{{ old('max_occupancy', $type->max_occupancy) }}"></div>
                <div class="mb-3"><label>Base Rate</label><input name="base_rate" class="form-control" type="number" step="0.01" value="{{ old('base_rate', $type->base_rate) }}"></div>
                <div class="text-end"><button class="btn btn-primary">Save</button></div>
            </div></div>
        </form>
    </div>
</div>
@endsection
