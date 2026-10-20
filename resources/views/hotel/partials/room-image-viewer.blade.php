@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/hotel-room-viewer.css') }}?v=20261020">
    @endpush
    @push('scripts')
        <script type="module" src="{{ asset('assets/js/hotel-room-viewer.js') }}?v=20261020"></script>
    @endpush
@endonce
<div class="room-image-viewer" data-room-viewer>
    <div class="room-image-viewport" data-room-viewport tabindex="0" role="region" aria-label="Room image. Pinch or use plus and minus to zoom, drag to move, Home to fit the full image.">
        <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" draggable="false" decoding="async">
    </div>
    <div class="room-image-toolbar">
        <span class="room-image-hint">Full image first · Pinch or scroll to zoom · Drag to look around</span>
        <div class="room-image-actions" role="group" aria-label="Room image controls">
            <button type="button" data-room-action="out" aria-label="Zoom out">−</button>
            <output data-room-zoom aria-live="polite">1×</output>
            <button type="button" data-room-action="in" aria-label="Zoom in">+</button>
            <button type="button" data-room-action="fit">Fit room</button>
        </div>
    </div>
</div>
