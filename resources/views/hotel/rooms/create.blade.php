@extends('layout.mainlayout')

@section('style')
<style>
    .room-form-page { background:#f5f8fc; }
    .room-form-hero { background:linear-gradient(135deg,#061b33,#0b5fb8); color:#fff; border-radius:20px; padding:22px; margin-bottom:18px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .room-form-hero h3, .room-form-hero p { color:#fff !important; }
    .room-form-card { background:#fff; border:1px solid #d8e2ee; border-radius:18px; box-shadow:0 14px 32px rgba(15,23,42,.07); overflow:hidden; }
    .room-form-card .card-header { background:#eef5ff; border-bottom:1px solid #d8e2ee; }
    .room-media-hint { border:1px dashed #d4a23a; background:#fff8e1; border-radius:14px; padding:14px; color:#5a3d00; }
</style>
@endsection

@section('content')
<div class="page-wrapper room-form-page"><div class="content container-fluid">
    <section class="room-form-hero"><div><small class="text-warning fw-bold">ROOM SETUP</small><h3 class="mb-1">Add Available Room</h3><p class="mb-0">Create the room, attach it to a room type, set override pricing if needed, and upload client-facing visuals.</p></div><div class="d-flex gap-2 flex-wrap align-self-start"><a href="{{ route('hotel.room_types.index') }}" class="btn btn-light">Room Types & Prices</a><a href="{{ route('hotel.rate_plans.index') }}" class="btn btn-warning">Rate Plans</a></div></section>

    <form method="POST" action="{{ route('hotel.rooms.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="room-form-card"><div class="card-header"><h5 class="mb-0">Room Identity, Pricing & Visuals</h5></div><div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Property</label><select name="property_id" class="form-select" required>@foreach($properties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Room Number</label><input name="room_number" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Room Type</label><select name="room_type_id" class="form-select"><option value="">Select room type</option>@foreach($roomTypes as $rt)<option value="{{ $rt->id }}">{{ $rt->name }} - {{ number_format((float)$rt->base_rate, 2) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Floor</label><input name="floor" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Wing</label><input name="wing" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Base Rate Override</label><input name="base_rate_override" class="form-control" type="number" step="0.01" placeholder="Optional"></div>
                <div class="col-md-3"><label class="form-label">Operational Status</label><select name="operational_status" class="form-select"><option value="available">Available</option><option value="reserved">Reserved</option><option value="maintenance">Maintenance</option><option value="out_of_order">Out of Order</option></select></div>
                <div class="col-md-6"><label class="form-label">Room Photo</label><input type="file" name="room_image" class="form-control" accept="image/*"><small class="text-muted">Used on room cards and booking previews.</small></div>
                <div class="col-md-6"><label class="form-label">Panorama / Wide Preview Image</label><input type="file" name="panorama_image" class="form-control" accept="image/*"><small class="text-muted">Use a wide room image so clients can preview the room without entering.</small></div>
                <div class="col-12"><label class="form-label">Room Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Amenities, view, policy notes, special features"></textarea></div>
                <div class="col-12"><div class="room-media-hint"><strong>Where prices are set:</strong> base room prices are configured under Room Types. Date/season/service pricing is configured under Rate Plans. This room override is optional for special rooms.</div></div>
            </div>
            <div class="text-end mt-4"><a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Create Room</button></div>
        </div></div>
    </form>
</div></div>
@endsection
