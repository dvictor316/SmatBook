@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="page-header"><h3>New Room</h3></div>

    <form method="POST" action="{{ route('hotel.rooms.store') }}">
        @csrf
        <div class="card"><div class="card-body">
            <div class="mb-3"><label>Property</label>
                <select name="property_id" class="form-select">
                    @php $props = \App\Models\HotelProperty::where('company_id', auth()->user()->company_id)->get(); @endphp
                    @foreach($props as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label>Room Number</label><input name="room_number" class="form-control" required></div>
            <div class="mb-3"><label>Room Type</label>
                <select name="room_type_id" class="form-select">
                    <option value="">--</option>
                    @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label>Floor</label><input name="floor" class="form-control"></div>
            <div class="mb-3"><label>Wing</label><input name="wing" class="form-control"></div>
            <div class="mb-3"><label>Base Rate Override</label><input name="base_rate_override" class="form-control" type="number" step="0.01"></div>
            <div class="text-end"><button class="btn btn-primary">Create Room</button></div>
        </div></div>
    </form>
</div></div>
@endsection
