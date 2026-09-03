@extends('layout.mainlayout')

@section('style')
<style>
    .room-show-page { background:#eef4f8; color:#10233f; }
    .room-show-hero { display:grid; grid-template-columns:minmax(0,1.2fr) 340px; gap:18px; align-items:stretch; margin-bottom:18px; }
    .room-show-gallery { position:relative; min-height:430px; border-radius:8px; overflow:hidden; background:#061b33; box-shadow:0 18px 42px rgba(15,23,42,.16); }
    .room-slide { position:absolute; inset:0; opacity:0; transition:opacity .45s ease; }
    .room-slide.is-active { opacity:1; }
    .room-slide img { width:100%; height:100%; object-fit:cover; display:block; }
    .room-show-overlay { position:absolute; left:0; right:0; bottom:0; padding:22px; color:#fff; background:linear-gradient(180deg,transparent,rgba(6,27,51,.86)); }
    .room-show-overlay h3 { color:#fff; font-size:34px; margin:0; font-weight:900; }
    .room-show-panel { background:#fff; border:1px solid #d8e2ee; border-radius:8px; padding:18px; box-shadow:0 14px 32px rgba(15,23,42,.07); }
    .room-show-status { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin:14px 0; }
    .room-show-chip { border:1px solid #d8e2ee; border-radius:8px; padding:12px; background:#f8fbff; }
    .room-show-chip span { display:block; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:800; }
    .room-show-chip strong { font-size:20px; color:#061b33; }
    .room-thumbs { display:flex; gap:8px; overflow:auto; margin-top:12px; padding-bottom:4px; }
    .room-thumbs button { width:92px; height:64px; border:2px solid transparent; border-radius:8px; padding:0; overflow:hidden; flex:0 0 auto; background:#fff; }
    .room-thumbs button.is-active { border-color:#0b5fb8; }
    .room-thumbs img { width:100%; height:100%; object-fit:cover; display:block; }
    .room-show-grid { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:18px; }
    .room-show-table th { background:#061b33; color:#fff; font-size:12px; text-transform:uppercase; }
    @media(max-width:991px){.room-show-hero,.room-show-grid{grid-template-columns:1fr}.room-show-gallery{min-height:320px}}
</style>
@endsection

@section('content')
<div class="page-wrapper room-show-page">
    <div class="content container-fluid">
        @php
            $images = $room->images;
            $fallback = $room->panorama_image ?: $room->room_image;
        @endphp
        <section class="room-show-hero">
            <div>
                <div class="room-show-gallery" data-room-show-gallery>
                    @forelse($images as $image)
                        <div class="room-slide {{ $loop->first ? 'is-active' : '' }}" data-room-slide>
                            <img src="{{ asset('storage/'.$image->path) }}" alt="Room {{ $room->room_number }} image {{ $loop->iteration }}">
                        </div>
                    @empty
                        @if($fallback)
                            <div class="room-slide is-active" data-room-slide><img src="{{ asset('storage/'.$fallback) }}" alt="Room {{ $room->room_number }}"></div>
                        @else
                            <div class="d-flex h-100 align-items-center justify-content-center text-white"><i class="fas fa-bed fa-4x"></i></div>
                        @endif
                    @endforelse
                    <div class="room-show-overlay">
                        <small class="text-warning fw-semibold">ROOM {{ $room->room_number }}</small>
                        <h3>{{ $room->type?->name ?? 'Hotel Room' }}</h3>
                        <div>{{ $room->wing ?: 'Main Wing' }} - Floor {{ $room->floor ?: 'N/A' }}</div>
                    </div>
                </div>
                @if($images->count())
                    <div class="room-thumbs" data-room-thumbs>
                        @foreach($images as $image)
                            <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-room-thumb="{{ $loop->index }}"><img src="{{ asset('storage/'.$image->path) }}" alt=""></button>
                        @endforeach
                    </div>
                @endif
            </div>
            <aside class="room-show-panel">
                <div class="d-flex justify-content-between gap-2 align-items-start">
                    <div><small class="text-muted fw-bold">ROOM PROFILE</small><h4 class="mb-0">Room {{ $room->room_number }}</h4></div>
                    <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-primary">Edit</a>
                </div>
                <div class="room-show-status">
                    <div class="room-show-chip"><span>Status</span><strong>{{ ucfirst(str_replace('_', ' ', (string) $room->operational_status)) }}</strong></div>
                    <div class="room-show-chip"><span>Cleaning</span><strong>{{ ucfirst((string) $room->housekeeping_status) }}</strong></div>
                    <div class="room-show-chip"><span>Rate</span><strong>{{ number_format((float)($room->base_rate_override ?: ($room->type?->base_rate ?? 0)), 2) }}</strong></div>
                    <div class="room-show-chip"><span>Gallery</span><strong>{{ $images->count() }}</strong></div>
                </div>
                <p class="text-muted">{{ $room->notes ?: 'No room notes recorded yet.' }}</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('hotel.reservations.create', ['room_id' => $room->id, 'room_type_id' => $room->room_type_id]) }}" class="btn btn-warning">Reserve This Room</a>
                    <a href="{{ route('hotel.housekeeping.index') }}" class="btn btn-outline-secondary">Housekeeping</a>
                </div>
            </aside>
        </section>

        <div class="room-show-grid">
            <main class="room-show-panel">
                <h5>Upcoming Reservations</h5>
                <div class="table-responsive">
                    <table class="table room-show-table align-middle">
                        <thead><tr><th>Guest</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($upcomingReservations as $reservation)
                            <tr><td>{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest' }}</td><td>{{ optional($reservation->arrival_date)->format('d M Y') }}</td><td>{{ optional($reservation->departure_date)->format('d M Y') }}</td><td>{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No upcoming reservations for this room.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </main>
            <aside class="room-show-panel">
                <h5>Current Guest</h5>
                @if($activeStay)
                    <strong>{{ $activeStay->customer?->customer_name ?? $activeStay->customer?->name ?? 'Guest' }}</strong>
                    <div class="text-muted">Expected checkout: {{ optional($activeStay->expected_checkout_at)->format('d M Y') }}</div>
                    <a href="{{ route('hotel.checkout.index', ['stay_id' => $activeStay->id]) }}" class="btn btn-sm btn-outline-primary mt-3">Open Checkout</a>
                @else
                    <div class="text-muted">Room is not currently occupied.</div>
                @endif
            </aside>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const slides = Array.from(document.querySelectorAll('[data-room-slide]'));
    const thumbs = Array.from(document.querySelectorAll('[data-room-thumb]'));
    if (!slides.length) return;
    let index = 0;
    function show(next) {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
        thumbs.forEach((thumb, i) => thumb.classList.toggle('is-active', i === index));
    }
    thumbs.forEach((thumb) => thumb.addEventListener('click', () => show(Number(thumb.dataset.roomThumb || 0))));
    if (slides.length > 1) window.setInterval(() => show(index + 1), 4500);
})();
</script>
@endsection
