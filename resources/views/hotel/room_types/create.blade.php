@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3>New Room Type</h3>
        </div>

        <form method="POST" action="{{ route('hotel.room_types.store') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="mb-3"><label>Name</label><input name="name" class="form-control" required></div>
                    <div class="mb-3"><label>Code</label><input name="code" class="form-control"></div>
                    <div class="mb-3"><label>Beds</label><input name="beds" class="form-control" type="number"></div>
                    <div class="mb-3"><label>Max Occupancy</label><input name="max_occupancy" class="form-control" type="number"></div>
                    <div class="mb-3"><label>Base Rate</label><input name="base_rate" class="form-control" type="number" step="0.01"></div>
                    <div class="text-end"><button class="btn btn-primary">Create</button></div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
