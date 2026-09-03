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
    .room-form-section { padding:22px; border:1px solid #edf2f7; border-radius:8px; background:#fff; }
    .room-form-section + .room-form-section { margin-top:18px; }
    .room-form-section-title { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #edf2f7; }
    .room-form-section-title h6 { margin:0; color:#061b33; font-size:16px; font-weight:800; }
    .room-form-section-title span { color:#64748b; font-size:13px; }
    .room-form-card .form-label { margin-bottom:8px; color:#64748b; font-size:13px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .room-form-card .form-control, .room-form-card .form-select { min-height:50px; border-color:#cfd9e3; color:#061b33; font-size:15px; }
    .room-form-card textarea.form-control { min-height:116px; }
    .room-upload-box { height:100%; padding:16px; border:1px solid #d8e2ee; border-radius:8px; background:#f8fbff; }
    .room-upload-box .form-control { background:#fff; }
    .room-media-hint { border:1px dashed #d4a23a; background:#fff8e1; border-radius:8px; padding:16px 18px; color:#5a3d00; }
    .room-form-actions { display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:22px; padding-top:20px; border-top:1px solid #edf2f7; }
    @media(max-width:767px){.room-form-shell{padding:0 2px 24px}.room-form-hero{padding:22px}.room-form-card .card-header,.room-form-card .card-body{padding:20px !important}.room-form-section{padding:16px}}
</style>
@endsection

@section('content')
<div class="page-wrapper room-form-page"><div class="content container-fluid"><div class="room-form-shell">
    <section class="room-form-hero"><div><small class="text-warning fw-semibold">ROOM SETUP</small><h3 class="mb-1">Add Available Room</h3><p class="mb-0">Create the room, attach it to a room type, set override pricing if needed, and upload client-facing visuals.</p></div><div class="d-flex gap-2 flex-wrap align-self-start"><a href="{{ route('hotel.rooms.index') }}" class="btn btn-light">Room Inventory</a></div></section>

    <form method="POST" action="{{ route('hotel.rooms.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="room-form-card"><div class="card-header"><h5 class="mb-0">Room Identity, Pricing & Visuals</h5></div><div class="card-body">
            <section class="room-form-section">
                <div class="room-form-section-title"><div><h6>Room Identity</h6><span>Core room details used by front desk, housekeeping and booking screens.</span></div></div>
                <div class="row g-4">
                <div class="col-md-6"><label class="form-label">Property</label><select name="property_id" class="form-select" required>@foreach($properties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Room Number</label><input name="room_number" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Room Type</label><select name="room_type_id" class="form-select"><option value="">Select room type</option>@foreach($roomTypes as $rt)<option value="{{ $rt->id }}">{{ $rt->name }} - {{ number_format((float)$rt->base_rate, 2) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Floor</label><input name="floor" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Wing</label><input name="wing" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Base Rate Override</label><input name="base_rate_override" class="form-control" type="number" step="0.01" placeholder="Optional"></div>
                <div class="col-md-3"><label class="form-label">Operational Status</label><select name="operational_status" class="form-select"><option value="available">Available</option><option value="reserved">Reserved</option><option value="maintenance">Maintenance</option><option value="out_of_order">Out of Order</option></select></div>
                </div>
            </section>

            <section class="room-form-section">
                <div class="room-form-section-title"><div><h6>Photos & Guest Preview</h6><span>Upload clean room visuals for room cards and client-facing previews.</span></div></div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="room-media-uploader">
                            <div class="room-media-uploader__preview" data-room-image-preview="room_image">
                                <span class="room-media-uploader__tag">Room card image</span>
                                <div class="room-media-uploader__empty">
                                    <i class="fas fa-image"></i>
                                    <strong>Upload room photo</strong>
                                    <span class="d-block mt-1">This appears on the room card and can be used as preview fallback.</span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Room Photo</label>
                                <input type="file" name="room_image" class="form-control" accept="image/*" data-room-image-input="room_image">
                                <small class="d-block mt-2">Best for normal room thumbnails, booking cards and room list previews.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="room-media-uploader">
                            <div class="room-media-uploader__preview is-wide" data-room-image-preview="panorama_image">
                                <span class="room-media-uploader__tag">Panorama viewer image</span>
                                <div class="room-media-uploader__empty">
                                    <i class="fas fa-vr-cardboard"></i>
                                    <strong>Upload wide panorama</strong>
                                    <span class="d-block mt-1">This is what customers see in the large preview display.</span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Panorama / Wide Preview Image</label>
                                <input type="file" name="panorama_image" class="form-control" accept="image/*" data-room-image-input="panorama_image">
                                <small class="d-block mt-2">Use a wide landscape image so clients can inspect the room without entering.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Gallery Photos</label>
                        <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple data-room-gallery-input>
                        <small class="d-block mt-2">Upload multiple photos for the room show page carousel and thumbnails.</small>
                        <div class="room-gallery-preview mt-3" data-room-gallery-preview></div>
                    </div>
                    <div class="col-12"><label class="form-label">Room Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Amenities, view, policy notes, special features"></textarea></div>
                    <div class="col-12"><div class="room-media-hint"><strong>Where prices are set:</strong> base room prices are configured under Room Types. Date/season/service pricing is configured under Rate Plans. This room override is optional for special rooms.</div></div>
                </div>
            </section>

            <div class="room-form-actions"><a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Create Room</button></div>
        </div></div>
    </form>
</div></div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('[data-room-image-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const key = input.dataset.roomImageInput;
        const preview = document.querySelector(`[data-room-image-preview="${key}"]`);
        const file = input.files && input.files[0];
        if (!preview || !file || !file.type.startsWith('image/')) return;

        const url = URL.createObjectURL(file);
        preview.innerHTML = `<span class="room-media-uploader__tag">${key === 'panorama_image' ? 'Panorama viewer image' : 'Room card image'}</span><img src="${url}" alt="Selected room image preview">`;
    });
});

document.querySelectorAll('[data-room-gallery-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = document.querySelector('[data-room-gallery-preview]');
        if (!preview) return;
        preview.innerHTML = '';
        Array.from(input.files || []).slice(0, 8).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            img.style.cssText = 'width:96px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #d8e2ee;margin-right:8px;margin-bottom:8px';
            preview.appendChild(img);
        });
    });
});
</script>
@endsection
