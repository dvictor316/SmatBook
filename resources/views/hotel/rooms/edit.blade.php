@extends('layout.mainlayout')

@section('style')
<style>
    .room-form-page { background:#f5f8fc; }
    .room-form-hero { background:linear-gradient(135deg,#061b33,#0b5fb8); color:#fff; border-radius:20px; padding:22px; margin-bottom:18px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .room-form-hero h3, .room-form-hero p { color:#fff !important; }
    .room-form-card { background:#fff; border:1px solid #d8e2ee; border-radius:18px; box-shadow:0 14px 32px rgba(15,23,42,.07); overflow:hidden; }
    .room-form-card .card-header { background:#eef5ff; border-bottom:1px solid #d8e2ee; }
    .room-thumb { width:100%; max-height:180px; object-fit:cover; border-radius:14px; border:1px solid #d8e2ee; }
</style>
@endsection

@section('content')
<div class="page-wrapper room-form-page"><div class="content container-fluid">
    <section class="room-form-hero"><div><small class="text-warning fw-bold">ROOM SETUP</small><h3 class="mb-1">Edit Room {{ $room->room_number }}</h3><p class="mb-0">Update room status, pricing override, photo and panorama preview.</p></div><a href="{{ route('hotel.rooms.index') }}" class="btn btn-light align-self-start">Back to Rooms</a></section>

    <form method="POST" action="{{ route('hotel.rooms.update', $room) }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="room-form-card"><div class="card-header"><h5 class="mb-0">Room Details</h5></div><div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Room Number</label><input class="form-control" value="{{ $room->room_number }}" disabled><small class="text-muted">Room number is fixed to protect reservations.</small></div>
                <div class="col-md-4"><label class="form-label">Room Type</label><select name="room_type_id" class="form-select"><option value="">Select room type</option>@foreach($roomTypes as $rt)<option value="{{ $rt->id }}" @if($room->room_type_id == $rt->id) selected @endif>{{ $rt->name }} - {{ number_format((float)$rt->base_rate, 2) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">Floor</label><input name="floor" class="form-control" value="{{ old('floor', $room->floor) }}"></div>
                <div class="col-md-2"><label class="form-label">Wing</label><input name="wing" class="form-control" value="{{ old('wing', $room->wing) }}"></div>
                <div class="col-md-3"><label class="form-label">Base Rate Override</label><input name="base_rate_override" class="form-control" type="number" step="0.01" value="{{ old('base_rate_override', $room->base_rate_override) }}"></div>
                <div class="col-md-3"><label class="form-label">Operational Status</label><select name="operational_status" class="form-select">@foreach(['available','occupied','reserved','maintenance','out_of_order'] as $state)<option value="{{ $state }}" @selected(old('operational_status', $room->operational_status) === $state)>{{ ucfirst(str_replace('_',' ', $state)) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Housekeeping</label><select name="housekeeping_status" class="form-select">@foreach(['clean','dirty','inspection','cleaning'] as $state)<option value="{{ $state }}" @selected(old('housekeeping_status', $room->housekeeping_status) === $state)>{{ ucfirst($state) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1" @selected($room->is_active)>Active</option><option value="0" @selected(!$room->is_active)>Inactive</option></select></div>
                <div class="col-md-6"><label class="form-label">Room Photo</label>@if($room->room_image)<img src="{{ asset('storage/'.$room->room_image) }}" class="room-thumb mb-2" alt="Room photo">@endif<input type="file" name="room_image" class="form-control" accept="image/*"></div>
                <div class="col-md-6"><label class="form-label">Panorama / Wide Preview Image</label>@if($room->panorama_image)<img src="{{ asset('storage/'.$room->panorama_image) }}" class="room-thumb mb-2" alt="Room panorama">@endif<input type="file" name="panorama_image" class="form-control" accept="image/*"></div>
                <div class="col-12"><label class="form-label">Room Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $room->notes) }}</textarea></div>
            </div>
            <div class="text-end mt-4"><a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Room</button></div>
        </div></div>
    </form>
</div></div>
@endsection
