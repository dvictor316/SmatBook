@extends('layout.mainlayout')

@section('title', 'Find Nearby Businesses')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
@php
    $mapCenter = $center ?: ['lat' => 9.0820, 'lng' => 8.6753, 'label' => 'Nigeria'];
@endphp

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQ8fQh34J6LrB2r6E1px8K1i3q9z6iQ=" crossorigin="">

<style>
    .geo-command { background:linear-gradient(135deg,#073b7a 0%,#0f65c9 62%,#eaf4ff 62%,#ffffff 100%); border:0; color:#fff; overflow:hidden; }
    .geo-command .agent-field label { color:#dcecff; }
    .geo-command .agent-field select,
    .geo-command .agent-field input { background:#fff; border-color:#bfdbfe; color:#062f68; }
    .geo-command .agent-button.soft { background:#ffffff; color:#064995; border-color:#bfdbfe; }
    .geo-command-title { display:flex; align-items:center; gap:14px; margin-bottom:18px; }
    .geo-command-icon { width:54px; height:54px; border-radius:18px; background:#fff; color:#0f65c9; display:flex; align-items:center; justify-content:center; font-size:24px; box-shadow:0 14px 34px rgba(3,38,82,.18); }
    .geo-command-title h3 { color:#fff; margin:0; font-weight:900; }
    .geo-command-title p { color:#dcecff; margin:2px 0 0; }
    .geo-card-grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:16px; }
    .geo-mini-card { min-height: 245px; display:flex; flex-direction:column; justify-content:space-between; border:1px solid #dbeafe; border-radius:20px; background:linear-gradient(180deg,#fff 0%,#f8fbff 100%); box-shadow:0 14px 34px rgba(15,101,201,.08); }
    .geo-map-panel { height: 520px; border-radius: 24px; overflow:hidden; border:1px solid #bfdbfe; box-shadow:0 24px 55px rgba(6,47,104,.14); }
    .geo-search-help { background:rgba(255,255,255,.92); border:1px solid #bfdbfe; color:#164178; border-radius:999px; padding:10px 12px; font-size:13px; }
    .geo-place-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
    .geo-place-action { border:1px solid #bfdbfe; background:#fff; color:#064995; border-radius:999px; padding:7px 10px; font-size:12px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .geo-place-action:hover { background:#0f65c9; color:#fff; border-color:#0f65c9; }
    .geo-place-action.primary { background:#0f65c9; color:#fff; border-color:#0f65c9; }
    .geo-place-action.primary:hover { background:#073b7a; }
    @media (max-width: 1199px) { .geo-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .geo-card-grid { grid-template-columns: 1fr; } .geo-map-panel { height: 330px; } }
</style>

<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Find Nearby Businesses</h1>
                <p>Select a store type, choose country/state/local council, or turn on location for precise nearby prospects.</p>
            </div>
            <a href="{{ route('deployment.crm.leads') }}" class="agent-button soft"><i class="fa-solid fa-users"></i> Team Leads</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error') || $lookupError)
            <div class="alert alert-warning alert-dismissible fade show">{{ session('error') ?? $lookupError }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <section class="agent-card geo-command mb-4">
            <div class="geo-command-title">
                <span class="geo-command-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                <div>
                    <h3>Business Location Finder</h3>
                    <p>Search mapped businesses around the selected state, local council, or your live location.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('deployment.geo.index') }}" id="geoSearchForm">
                <input type="hidden" name="search" value="1">
                <input type="hidden" name="lat" id="geoLat" value="{{ request('lat') }}">
                <input type="hidden" name="lng" id="geoLng" value="{{ request('lng') }}">
                <div class="row g-3">
                    <div class="col-xl-3 col-lg-6">
                        <div class="agent-field">
                            <label>Business / Store Type</label>
                            <select name="business_type" id="geoBusinessType">
                                @foreach($categoryOptions as $key => $label)
                                    <option value="{{ $label }}" data-category="{{ $key }}" @selected($businessType === $label || $category === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="category" id="geoCategory" value="{{ $category }}">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-6">
                        <div class="agent-field">
                            <label>Country</label>
                            <select name="country" id="geoCountry">
                                @foreach($countryOptions as $key => $label)
                                    <option value="{{ $key }}" @selected($country === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4">
                        <div class="agent-field">
                            <label>State / Region</label>
                            <select name="state" id="geoState"></select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4">
                        <div class="agent-field">
                            <label>Local Council / County</label>
                            <select name="local_council" id="geoCouncil"></select>
                        </div>
                    </div>
                    <div class="col-xl-1 col-lg-4">
                        <div class="agent-field">
                            <label>Radius</label>
                            <select name="radius">
                                @foreach([1000, 2000, 5000, 10000] as $option)
                                    <option value="{{ $option }}" @selected((int) $radius === $option)>{{ $option / 1000 }}km</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="agent-button soft" id="geoUseLocation"><i class="fa-solid fa-location-arrow"></i> Use My Location</button>
                    <button type="submit" class="agent-button"><i class="fa-solid fa-magnifying-glass-location"></i> Search Nearby</button>
                    <span class="geo-search-help" id="geoLocationHint"><i class="fa-solid fa-circle-info"></i> For best accuracy, tap Use My Location and allow location access.</span>
                </div>
            </form>
        </section>

        <div class="agent-grid mb-4">
            <section class="agent-card span-4 agent-metric">
                <span class="icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                <div class="label">Search Center</div>
                <div class="value" style="font-size:28px;">{{ \Illuminate\Support\Str::limit($mapCenter['label'] ?? 'Nigeria', 34) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-store"></i></span>
                <div class="label">Businesses Found</div>
                <div class="value">{{ number_format(count($nearbyResults)) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric">
                <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-map"></i></span>
                <div class="label">Coverage</div>
                <div class="value">{{ number_format($radius / 1000, 1) }}km</div>
            </section>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-12">
                <h4>Map View</h4>
                <p class="agent-muted">Pins show the current search center and nearby matches.</p>
                <div id="geoMap" class="geo-map-panel"></div>
            </section>
            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4>Search Results</h4>
                        <p class="agent-muted mb-0">{{ $businessType }} around {{ $localCouncil ?: $state }}, {{ $country }}</p>
                    </div>
                    <span class="agent-pill">{{ count($nearbyResults) }} matches</span>
                </div>
                <div class="geo-card-grid">
                    @forelse($nearbyResults as $place)
                        <article class="agent-card geo-mini-card">
                            <div>
                                <span class="agent-initial mb-2">{{ strtoupper(mb_substr($place['name'], 0, 1)) }}</span>
                                <h4 style="font-size:17px;">{{ $place['name'] }}</h4>
                                <small><i class="fa-solid fa-location-dot text-primary"></i> {{ \Illuminate\Support\Str::limit($place['address'], 105) }}</small>
                            </div>
                            <div class="mt-3">
                                <span class="agent-pill">{{ ucwords(str_replace('_', ' ', $place['type'])) }}</span>
                                <div class="agent-muted mt-2">
                                    <div><i class="fa-solid fa-route"></i> {{ number_format($place['distance'], 2) }} km away</div>
                                    <div><i class="fa-solid fa-phone"></i> {{ $place['phone'] ?: 'Public number not listed' }}</div>
                                </div>
                                <div class="geo-place-actions">
                                    <button type="button" class="geo-place-action primary geo-view-place" data-lat="{{ $place['lat'] }}" data-lng="{{ $place['lng'] }}" data-name="{{ $place['name'] }}">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <a class="geo-place-action" href="https://www.google.com/maps/dir/?api=1&destination={{ $place['lat'] }},{{ $place['lng'] }}" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-diamond-turn-right"></i> Direction
                                    </a>
                                    @if(!empty($place['website']))
                                        <a class="geo-place-action" href="{{ $place['website'] }}" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i> Website</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="text-center agent-muted py-5" style="grid-column:1 / -1;">
                            <h4>No matches yet</h4>
                            <p>Type a business type and search. Try examples like pharmacy, supermarket, hotel, restaurant, electronics, salon, school.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statesUrl = @json(route('locations.states'));
    const councilsUrl = @json(route('locations.councils'));
    const selectedCountry = @json($country);
    const selectedState = @json($state);
    const selectedCouncil = @json($localCouncil);
    const country = document.getElementById('geoCountry');
    const state = document.getElementById('geoState');
    const council = document.getElementById('geoCouncil');
    const businessType = document.getElementById('geoBusinessType');
    const category = document.getElementById('geoCategory');

    if (businessType && category) {
        businessType.addEventListener('change', () => {
            category.value = businessType.selectedOptions[0]?.dataset.category || 'business';
        });
        category.value = businessType.selectedOptions[0]?.dataset.category || category.value || 'business';
    }

    function fetchJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then((response) => {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        });
    }

    function fillStates() {
        state.innerHTML = '<option value="">Loading states...</option>';
        council.innerHTML = '<option value="">All local councils</option>';

        return fetchJson(`${statesUrl}?country=${encodeURIComponent(country.value)}`)
            .then((data) => {
                const stateNames = data.states || [];
                state.innerHTML = stateNames.length
                    ? stateNames.map((item) => `<option value="${escapeAttr(item)}">${escapeHtml(item)}</option>`).join('')
                    : '<option value="">All states / use location</option>';
                if ([...state.options].some((option) => option.value === selectedState)) state.value = selectedState;
                return fillCouncils();
            })
            .catch(() => {
                state.innerHTML = '<option value="">Unable to load states</option>';
                council.innerHTML = '<option value="">Unable to load local councils</option>';
            });
    }

    function fillCouncils() {
        if (!country.value || !state.value) {
            council.innerHTML = '<option value="">All local councils</option>';
            return Promise.resolve();
        }

        council.innerHTML = '<option value="">Loading local councils...</option>';
        return fetchJson(`${councilsUrl}?country=${encodeURIComponent(country.value)}&state=${encodeURIComponent(state.value)}`)
            .then((data) => {
                const councils = data.councils || [];
                council.innerHTML = [''].concat(councils).map((item) => `<option value="${escapeAttr(item)}">${item ? escapeHtml(item) : 'All local councils'}</option>`).join('');
                if ([...council.options].some((option) => option.value === selectedCouncil)) council.value = selectedCouncil;
            })
            .catch(() => {
                council.innerHTML = '<option value="">Unable to load local councils</option>';
            });
    }

    country.addEventListener('change', () => {
        fillStates();
    });
    state.addEventListener('change', fillCouncils);
    fillStates();
    if ([...country.options].some((option) => option.value === selectedCountry)) {
        country.value = selectedCountry;
        fillStates();
    }

    document.getElementById('geoUseLocation').addEventListener('click', function () {
        const hint = document.getElementById('geoLocationHint');
        if (!navigator.geolocation) {
            if (hint) hint.innerHTML = '<i class="fa-solid fa-circle-info"></i> Please turn on location services in your browser/device settings.';
            return;
        }
        if (hint) hint.innerHTML = '<i class="fa-solid fa-location-dot"></i> Please allow location access when your browser asks.';
        navigator.geolocation.getCurrentPosition((position) => {
            document.getElementById('geoLat').value = position.coords.latitude;
            document.getElementById('geoLng').value = position.coords.longitude;
            document.getElementById('geoSearchForm').submit();
        }, () => {
            if (hint) hint.innerHTML = '<i class="fa-solid fa-circle-info"></i> Please turn on/allow location, then tap Use My Location again.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });

    const center = @json($mapCenter);
    const places = @json($nearbyResults);
    const map = L.map('geoMap').setView([center.lat, center.lng], places.length ? 13 : 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    const bounds = L.latLngBounds([[center.lat, center.lng]]);
    L.marker([center.lat, center.lng]).addTo(map).bindPopup(`<strong>${escapeHtml(center.label || 'Search center')}</strong>`).openPopup();
    places.forEach((place) => {
        const marker = L.circleMarker([place.lat, place.lng], { radius: 7, color: '#062f68', weight: 2, fillColor: '#18bf86', fillOpacity: .85 }).addTo(map);
        marker.bindPopup(`<strong>${escapeHtml(place.name)}</strong><br>${escapeHtml(place.address || '')}<br>${place.distance} km`);
        bounds.extend([place.lat, place.lng]);
    });
    if (places.length) map.fitBounds(bounds, { padding: [26, 26], maxZoom: 15 });

    document.querySelectorAll('.geo-view-place').forEach((button) => {
        button.addEventListener('click', () => {
            const lat = Number(button.dataset.lat);
            const lng = Number(button.dataset.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            map.setView([lat, lng], 17, { animate: true });
            L.popup()
                .setLatLng([lat, lng])
                .setContent(`<strong>${escapeHtml(button.dataset.name || 'Business')}</strong>`)
                .openOn(map);
            document.getElementById('geoMap')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }
    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
});
</script>
@endsection
