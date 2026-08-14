@extends('layout.mainlayout')

@section('style')
<style>
    .room-form-page { background:#f5f8fc; }
    .room-form-shell { max-width:1180px; margin:0 auto; padding-bottom:28px; }
    .room-form-hero { background:linear-gradient(135deg,#061b33,#0b5fb8); color:#fff; border-radius:8px; padding:28px 32px; margin-bottom:22px; display:flex; justify-content:space-between; gap:18px; flex-wrap:wrap; box-shadow:0 18px 40px rgba(6,27,51,.18); }
    .room-form-hero h3, .room-form-hero p { color:#fff !important; }
    .room-form-card { background:#fff; border:1px solid #d8e2ee; border-radius:8px; box-shadow:0 18px 42px rgba(15,23,42,.08); overflow:hidden; }
    .room-form-card .card-header { background:linear-gradient(180deg,#f8fbff,#eef5ff); border-bottom:1px solid #d8e2ee; padding:20px 28px; }
    .room-form-card .card-header h5 { color:#061b33; font-size:20px; font-weight:800; }
    .room-form-card .card-body { padding:28px !important; }
    .room-form-card .form-label { margin-bottom:8px; color:#64748b; font-size:13px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .room-form-card .form-control, .room-form-card .form-select { min-height:50px; border-color:#cfd9e3; color:#061b33; font-size:15px; }
    .room-form-card textarea.form-control { min-height:116px; }
    .room-form-actions { display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:22px; padding-top:20px; border-top:1px solid #edf2f7; }
    .room-thumb { width:100%; max-height:180px; object-fit:cover; border-radius:8px; border:1px solid #d8e2ee; }
    @media(max-width:767px){.room-form-shell{padding:0 2px 24px}.room-form-hero{padding:22px}.room-form-card .card-header,.room-form-card .card-body{padding:20px !important}}
</style>
@endsection

@section('content')
<div class="page-wrapper room-form-page"><div class="content container-fluid"><div class="room-form-shell">
    <section class="room-form-hero"><div><small class="text-warning fw-semibold">ROOM SETUP</small><h3 class="mb-1">Edit Room {{ $room->room_number }}</h3><p class="mb-0">Update room status, pricing override, photo and panorama preview.</p></div><a href="{{ route('hotel.rooms.index') }}" class="btn btn-light align-self-start">Back to Rooms</a></section>

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
            <div class="room-form-actions"><a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Room</button></div>
        </div></div>
    </form>
</div></div></div>
@endsection
