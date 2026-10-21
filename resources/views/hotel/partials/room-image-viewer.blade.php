@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/hotel-room-viewer.css') }}?v={{ filemtime(public_path('assets/css/hotel-room-viewer.css')) }}">
    @endpush
    @push('scripts')
        <script type="module" src="{{ asset('assets/js/hotel-room-viewer.js') }}?v={{ filemtime(public_path('assets/js/hotel-room-viewer.js')) }}"></script>
    @endpush
@endonce
@php
    $viewerScenes = collect();
    if ($room->panorama_image) {
        $viewerScenes->push(['url' => asset('storage/'.$room->panorama_image), 'panorama' => true, 'caption' => 'Room panorama']);
    }
    foreach ($room->images as $roomPhoto) {
        $viewerScenes->push(['url' => asset('storage/'.$roomPhoto->path), 'panorama' => (bool) $roomPhoto->is_panorama, 'caption' => $roomPhoto->caption ?: 'Room photo']);
    }
    $viewerScenes->push(['url' => $imageUrl, 'panorama' => false, 'caption' => 'Room photo']);
    $viewerScenes = $viewerScenes->unique('url')->values();
@endphp
<div class="room-image-viewer" data-room-viewer data-panorama="{{ $viewerScenes->first()['panorama'] ? 'true' : 'false' }}">
    <header class="room-tour-heading">
        <div><span class="room-tour-eyebrow">A closer look</span><strong>Explore Room {{ $room->room_number }}</strong></div>
        <span class="room-tour-mode" data-room-mode>Room tour</span>
    </header>
    <div class="room-image-viewport" data-room-viewport tabindex="0" role="region" aria-label="Interactive room view. Swipe vertically to scroll the page or horizontally to look around. Enable Drag freely for vertical room movement and pinch zoom. Arrow keys change the view; Home resets it.">
        <img src="{{ $viewerScenes->first()['url'] }}" alt="{{ $imageAlt }}" draggable="false" decoding="async" loading="lazy">
        <div class="room-tour-backdrop" data-room-backdrop aria-hidden="true"></div>
        <canvas data-room-sphere aria-hidden="true" hidden></canvas>
        <span class="room-tour-loading" data-room-loading hidden>Opening this view…</span>
        <div class="room-tour-shade" aria-hidden="true"></div>
        <div class="room-tour-caption"><span class="room-tour-dot" aria-hidden="true"></span><span data-room-caption>{{ $viewerScenes->first()['caption'] }}</span></div>
        <div class="room-tour-bearing" aria-hidden="true" hidden><span>VIEW</span><strong data-room-bearing>0°</strong></div>
    </div>
    <div class="room-tour-angles" hidden role="group" aria-label="Room viewing angles">
        <span>Choose your angle</span>
        <button type="button" data-room-angle="0">Front</button>
        <button type="button" data-room-angle="90">Right</button>
        <button type="button" data-room-angle="180">Behind</button>
        <button type="button" data-room-angle="270">Left</button>
        <button type="button" data-room-angle="up">Look up</button>
        <button type="button" data-room-angle="down">Look down</button>
    </div>
    <div class="room-image-toolbar">
        <button type="button" class="room-tour-play" data-room-action="play" aria-pressed="false">Play tour</button>
        <div class="room-image-actions" role="group" aria-label="Room image controls">
            <button type="button" data-room-action="out" aria-label="Zoom out">−</button>
            <output data-room-zoom>1×</output>
            <button type="button" data-room-action="in" aria-label="Zoom in">+</button>
            <button type="button" data-room-action="fit">Fit room</button>
            <button type="button" data-room-action="panorama" hidden>360° view</button>
            <button type="button" data-room-action="interact" aria-pressed="false">Drag freely</button>
            <button type="button" data-room-action="fullscreen">Fullscreen</button>
        </div>
        <span class="room-image-hint" data-room-hint>Swipe up to scroll · Drag sideways to explore · Ctrl + scroll to zoom</span>
        <span class="visually-hidden" role="status" data-room-status></span>
    </div>
    @if($viewerScenes->count() > 1)
        <div class="room-tour-scenes" role="group" aria-label="Room photos and panorama">
            @foreach($viewerScenes as $scene)
                <button type="button" data-room-scene data-src="{{ $scene['url'] }}" data-panorama="{{ $scene['panorama'] ? 'true' : 'false' }}" data-caption="{{ $scene['caption'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="View {{ $scene['caption'] }} {{ $loop->iteration }}">
                    <img src="{{ $scene['url'] }}" alt="" loading="lazy"><span>{{ $scene['panorama'] ? 'Panorama' : 'Angle '.$loop->iteration }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
