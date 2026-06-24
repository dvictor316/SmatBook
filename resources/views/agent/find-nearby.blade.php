@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .nearby-command { background: linear-gradient(135deg, #073b80 0%, #0f68d8 55%, #eaf4ff 160%); border: 0; color: #fff; overflow: hidden; position: relative; }
        .nearby-command::after { content: ""; position: absolute; width: 280px; height: 280px; right: -120px; top: -120px; border-radius: 50%; background: rgba(255,255,255,.12); }
        .nearby-command > * { position: relative; z-index: 1; }
        .nearby-command .agent-muted { color: rgba(255,255,255,.72); }
        .nearby-command strong { color: #fff; }
        .nearby-command .form-control { min-height: 44px; border: 1px solid rgba(255,255,255,.36); background: rgba(255,255,255,.96); color: #0f172a; font-weight: 700; }
        .nearby-map-shell { border: 1px solid #d9e8fb; border-radius: 24px; overflow: hidden; background: #fff; box-shadow: 0 22px 48px rgba(6, 47, 104, .12); }
        #agentNearbyMap { min-height: 480px; width: 100%; background: #eaf4ff; }
        .nearby-map-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 16px; border-top: 1px solid #e4eefb; background: linear-gradient(180deg, #fff, #f8fbff); }
        .nearby-results-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .nearby-business-card { border: 1px solid #dbeafe; border-radius: 20px; background: #fff; padding: 16px; box-shadow: 0 14px 34px rgba(15, 57, 116, .08); min-height: 100%; display: flex; flex-direction: column; gap: 12px; }
        .nearby-business-top { display: flex; gap: 12px; align-items: flex-start; }
        .nearby-business-icon { width: 44px; height: 44px; border-radius: 15px; display: grid; place-items: center; flex: 0 0 auto; background: linear-gradient(135deg, #e8f2ff, #f8fbff); color: #0756b8; font-weight: 900; border: 1px solid #dbeafe; }
        .nearby-business-card h4 { color: #082f68; font-size: 15px; margin: 0 0 4px; }
        .nearby-business-address { color: #64748b; font-size: 12px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .nearby-contact-row { display: flex; align-items: center; gap: 7px; color: #475569; font-size: 12px; }
        .nearby-card-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: auto; }
        .nearby-mini-action { border: 0; border-radius: 12px; padding: 9px 10px; background: #eef5ff; color: #0756b8; font-size: 12px; font-weight: 850; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
        .nearby-mini-action.primary { background: #073b80; color: #fff; }
        .nearby-mini-action:hover { color: #0756b8; transform: translateY(-1px); }
        .nearby-mini-action.primary:hover { color: #fff; }
        .nearby-empty-state { border: 1px dashed #bad6f7; border-radius: 20px; background: #f8fbff; color: #64748b; padding: 30px; text-align: center; }
        .nearby-marker { width: 34px; height: 34px; border-radius: 50% 50% 50% 8px; transform: rotate(-45deg); background: #0756b8; border: 3px solid #fff; box-shadow: 0 10px 20px rgba(7, 86, 184, .28); display: grid; place-items: center; }
        .nearby-marker span { transform: rotate(45deg); color: #fff; font-weight: 900; font-size: 12px; }
        @media (max-width: 1199px) { .nearby-results-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767px) { #agentNearbyMap { min-height: 360px; } .nearby-results-grid { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Find Businesses</h1>
                <p>Discover nearby businesses and save strong prospects as leads.</p>
            </div>
            <a href="{{ route('agent.leads') }}" class="agent-button soft"><i class="fa-solid fa-users"></i> Manage Leads</a>
        </div>

        <div class="agent-tabs mb-4">
            <a href="{{ route('agent.leads') }}">Manage Leads</a>
            <a class="active" href="{{ route('agent.find-nearby') }}"><i class="fa-solid fa-location-dot"></i> Find Nearby</a>
            <a href="{{ route('agent.earnings') }}">Invoices</a>
        </div>

        <section class="agent-card nearby-command mb-4">
            <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <strong class="text-uppercase">Geo Business Finder</strong>
                    <div class="agent-muted">Search by live location or by country, state, and local council.</div>
                </div>
                <strong>{{ $usedToday }} / {{ $dailyLimit }} searches used today</strong>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-lg-2">
                    <button type="button" class="agent-button soft w-100" id="agentUseLocation"><i class="fa-solid fa-location-arrow"></i> Use My Location</button>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="agentCountry" style="border-radius:14px;" aria-label="Country"></select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="agentRegion" style="border-radius:14px;" aria-label="State or region"></select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="agentCouncil" style="border-radius:14px;" aria-label="Local council or county"></select>
                </div>
                <div class="col-lg-3">
                    <select class="form-control" id="agentNearbyKeyword" style="border-radius:14px;" aria-label="Business keyword">
                        @foreach($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1">
                    <button type="button" class="agent-button w-100" id="agentFindBusinesses"><i class="fa-solid fa-magnifying-glass"></i> Find</button>
                </div>
            </div>
            <div class="mt-4">
                <small class="fw-bold text-uppercase agent-muted">Target high-value industries</small>
                <div class="d-flex gap-2 overflow-auto mt-2">
                    @foreach(array_slice($categories, 0, 8) as $category)
                        <button type="button" class="agent-button soft agent-category-chip" data-category="{{ $category }}">{{ $category }}</button>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="agent-card span-12 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4>Business Map</h4>
                    <small class="agent-muted">Markers are built from geocoded nearby business results.</small>
                </div>
                <span class="agent-pill" id="agentMapStatus">Map ready</span>
            </div>
            <div class="nearby-map-shell">
                <div id="agentNearbyMap"></div>
                <div class="nearby-map-meta">
                    <span class="agent-muted" id="agentNearbyState">Use your location or search by area to discover businesses.</span>
                    <span class="agent-pill" id="agentResultCount">0 results</span>
                </div>
            </div>
        </section>

        <section class="agent-card span-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4>Businesses Found</h4>
                    <small class="agent-muted">Review, view on map, get directions, or save as a lead.</small>
                </div>
            </div>
            <div id="agentNearbyResults" class="nearby-results-grid">
                <div class="nearby-empty-state" style="grid-column:1/-1;">
                    Search nearby businesses to see four-column cards here.
                </div>
            </div>
        </section>
    </div>
</div>

<form method="POST" action="{{ route('agent.leads.store') }}" id="agentSaveNearbyLead" class="d-none">
    @csrf
    <input name="business_name" id="nearbyBusinessName">
    <input name="business_category" id="nearbyBusinessCategory">
    <input name="address" id="nearbyBusinessAddress">
    <input name="phone" id="nearbyBusinessPhone">
    <input name="latitude" id="nearbyBusinessLatitude">
    <input name="longitude" id="nearbyBusinessLongitude">
    <input name="source" value="find_nearby">
    <input name="lead_type" value="company">
</form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const results = document.getElementById('agentNearbyResults');
    const state = document.getElementById('agentNearbyState');
    const count = document.getElementById('agentResultCount');
    const mapStatus = document.getElementById('agentMapStatus');
    const keyword = document.getElementById('agentNearbyKeyword');
    const countryOptions = @json($countryOptions ?? []);
    const statesUrl = @json(route('locations.states'));
    const councilsUrl = @json(route('locations.councils'));
    const country = document.getElementById('agentCountry');
    const region = document.getElementById('agentRegion');
    const council = document.getElementById('agentCouncil');
    let coords = null;
    let nearbyMap = null;
    let markersLayer = null;

    initializeMap();

    country.innerHTML = Object.keys(countryOptions).map((item) => `<option value="${escapeAttr(item)}">${escapeHtml(item)}</option>`).join('');
    country.value = 'Nigeria';
    fillRegions().then(() => {
        region.value = 'FCT';
        fillCouncils();
    });

    country.addEventListener('change', function () {
        fillRegions();
    });
    region.addEventListener('change', fillCouncils);

    document.querySelectorAll('.agent-category-chip').forEach((button) => {
        button.addEventListener('click', () => {
            keyword.value = button.dataset.category;
            searchNearby();
        });
    });

    document.getElementById('agentUseLocation').addEventListener('click', () => {
        if (!navigator.geolocation) {
            state.textContent = 'Please turn on location services in your browser/device settings.';
            return;
        }
        state.textContent = 'Please allow location access when your browser asks.';
        navigator.geolocation.getCurrentPosition((position) => {
            coords = { lat: position.coords.latitude, lon: position.coords.longitude };
            state.textContent = 'Location ready. Choose a category or search keyword.';
            mapStatus.textContent = 'Location locked';
            if (nearbyMap) nearbyMap.setView([coords.lat, coords.lon], 14);
            renderMapMarkers([], true);
        }, () => {
            state.textContent = 'Please turn on/allow location, then tap Use My Location again.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });

    document.getElementById('agentFindBusinesses').addEventListener('click', searchNearby);

    async function searchNearby() {
        const query = normalizeSearchTerm(keyword.value.trim() || 'business');
        state.textContent = 'Searching nearby businesses...';
        results.innerHTML = '';
        count.textContent = '0 results';

        try {
            let data = [];
            if (coords) {
                const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=24&extratags=1&addressdetails=1&q=${encodeURIComponent(query)}&viewbox=${coords.lon - 0.12},${coords.lat + 0.12},${coords.lon + 0.12},${coords.lat - 0.12}&bounded=1`;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                data = await response.json();
            } else {
                const queries = [
                    [query, council.value, region.value, country.value].filter(Boolean).join(' '),
                    [query, region.value, country.value].filter(Boolean).join(' '),
                    ['shop', council.value, region.value, country.value].filter(Boolean).join(' '),
                    ['business', region.value, country.value].filter(Boolean).join(' ')
                ];

                for (const text of [...new Set(queries.filter(Boolean))]) {
                    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=24&extratags=1&addressdetails=1&q=${encodeURIComponent(text)}`;
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const rows = await response.json();
                    data = data.concat(rows || []);
                    if (data.length >= 8) break;
                }
            }

            const seen = new Set();
            const cleaned = data.filter((item) => {
                const key = item.place_id || `${item.lat},${item.lon}`;
                if (seen.has(key)) return false;
                seen.add(key);
                return item.lat && item.lon;
            }).slice(0, 24);
            renderMapMarkers(cleaned);
            renderResults(cleaned, query);
        } catch (error) {
            state.textContent = 'Search failed. Please try again.';
            mapStatus.textContent = 'Search failed';
        }
    }

    function renderResults(items, category) {
        state.textContent = '';
        count.textContent = `${items.length} result${items.length === 1 ? '' : 's'}`;
        if (!items.length) {
            state.textContent = 'No businesses found. Try another category or area.';
            results.innerHTML = `<div class="nearby-empty-state" style="grid-column:1/-1;">No businesses found. Try another category, state, or local government.</div>`;
            return;
        }
        results.innerHTML = items.map((item) => {
            const name = (item.name || item.display_name || 'Nearby Business').split(',')[0];
            const address = item.display_name || '';
            const extra = item.extratags || {};
            const phone = extra.phone || extra['contact:phone'] || 'Public number not listed';
            const website = extra.website || extra['contact:website'] || '';
            const lat = item.lat || '';
            const lon = item.lon || '';
            const mapUrl = lat && lon ? `https://www.openstreetmap.org/?mlat=${encodeURIComponent(lat)}&mlon=${encodeURIComponent(lon)}#map=18/${encodeURIComponent(lat)}/${encodeURIComponent(lon)}` : '#';
            const directionsUrl = lat && lon ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(lat + ',' + lon)}` : '#';
            return `<article class="nearby-business-card" data-lat="${escapeAttr(lat)}" data-lon="${escapeAttr(lon)}">
                <div class="nearby-business-top">
                    <span class="nearby-business-icon">${escapeHtml(name.charAt(0).toUpperCase())}</span>
                    <div>
                        <h4>${escapeHtml(name)}</h4>
                        <div class="nearby-business-address">${escapeHtml(address)}</div>
                    </div>
                </div>
                <div class="nearby-contact-row"><i class="fa-solid fa-phone"></i> ${escapeHtml(phone)}</div>
                ${website ? `<div class="nearby-contact-row"><i class="fa-solid fa-globe"></i> <a href="${escapeAttr(website)}" target="_blank" rel="noopener">Visit website</a></div>` : ''}
                <div class="nearby-card-actions">
                    <button type="button" class="nearby-mini-action primary save-nearby" data-name="${escapeAttr(name)}" data-category="${escapeAttr(category)}" data-address="${escapeAttr(address)}" data-phone="${escapeAttr(phone === 'Public number not listed' ? '' : phone)}" data-lat="${escapeAttr(lat)}" data-lon="${escapeAttr(lon)}"><i class="fa-solid fa-plus"></i> Save</button>
                    <button type="button" class="nearby-mini-action view-nearby" data-lat="${escapeAttr(lat)}" data-lon="${escapeAttr(lon)}"><i class="fa-solid fa-map-pin"></i> View</button>
                    <a class="nearby-mini-action" href="${escapeAttr(directionsUrl)}" target="_blank" rel="noopener"><i class="fa-solid fa-route"></i> Direction</a>
                    <a class="nearby-mini-action" href="${escapeAttr(mapUrl)}" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> Map</a>
                </div>
            </article>`;
        }).join('');

        document.querySelectorAll('.save-nearby').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('nearbyBusinessName').value = button.dataset.name;
                document.getElementById('nearbyBusinessCategory').value = button.dataset.category;
                document.getElementById('nearbyBusinessAddress').value = button.dataset.address;
                document.getElementById('nearbyBusinessPhone').value = button.dataset.phone || '';
                document.getElementById('nearbyBusinessLatitude').value = button.dataset.lat || '';
                document.getElementById('nearbyBusinessLongitude').value = button.dataset.lon || '';
                document.getElementById('agentSaveNearbyLead').submit();
            });
        });

        document.querySelectorAll('.view-nearby').forEach((button) => {
            button.addEventListener('click', () => {
                const lat = parseFloat(button.dataset.lat);
                const lon = parseFloat(button.dataset.lon);
                if (!Number.isFinite(lat) || !Number.isFinite(lon) || !nearbyMap) return;
                nearbyMap.setView([lat, lon], 17);
                document.getElementById('agentNearbyMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }
    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
    function initializeMap() {
        if (!window.L) return;
        nearbyMap = L.map('agentNearbyMap', { scrollWheelZoom: false }).setView([9.082, 8.6753], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(nearbyMap);
        markersLayer = L.layerGroup().addTo(nearbyMap);
    }
    function markerIcon(index) {
        return L.divIcon({
            className: '',
            html: `<div class="nearby-marker"><span>${index}</span></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -30]
        });
    }
    function renderMapMarkers(items, includeCurrentLocation = false) {
        if (!nearbyMap || !markersLayer) return;
        markersLayer.clearLayers();
        const bounds = [];
        if (includeCurrentLocation && coords) {
            L.circleMarker([coords.lat, coords.lon], {
                radius: 9,
                color: '#073b80',
                fillColor: '#38bdf8',
                fillOpacity: .85,
                weight: 3
            }).bindPopup('Your current location').addTo(markersLayer);
            bounds.push([coords.lat, coords.lon]);
        }
        items.forEach((item, index) => {
            const lat = parseFloat(item.lat);
            const lon = parseFloat(item.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
            const name = (item.name || item.display_name || 'Nearby Business').split(',')[0];
            const address = item.display_name || '';
            L.marker([lat, lon], { icon: markerIcon(index + 1) })
                .bindPopup(`<strong>${escapeHtml(name)}</strong><br><small>${escapeHtml(address)}</small>`)
                .addTo(markersLayer);
            bounds.push([lat, lon]);
        });
        if (bounds.length > 1) {
            nearbyMap.fitBounds(bounds, { padding: [34, 34], maxZoom: 15 });
        } else if (bounds.length === 1) {
            nearbyMap.setView(bounds[0], 15);
        }
        mapStatus.textContent = items.length ? `${items.length} mapped` : 'Map ready';
    }
    function fetchJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then((response) => {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        });
    }
    function fillRegions() {
        region.innerHTML = '<option value="">Loading states...</option>';
        council.innerHTML = '<option value="">All local councils</option>';
        return fetchJson(`${statesUrl}?country=${encodeURIComponent(country.value)}`)
            .then((data) => {
                const regionNames = data.states || [];
                region.innerHTML = regionNames.length
                    ? regionNames.map((item) => `<option value="${escapeAttr(item)}">${escapeHtml(item)}</option>`).join('')
                    : '<option value="">All states / use location</option>';
                return fillCouncils();
            })
            .catch(() => {
                region.innerHTML = '<option value="">Unable to load states</option>';
                council.innerHTML = '<option value="">Unable to load local councils</option>';
            });
    }
    function fillCouncils() {
        if (!country.value || !region.value) {
            council.innerHTML = '<option value="">All local councils</option>';
            return Promise.resolve();
        }

        council.innerHTML = '<option value="">Loading local councils...</option>';
        return fetchJson(`${councilsUrl}?country=${encodeURIComponent(country.value)}&state=${encodeURIComponent(region.value)}`)
            .then((data) => {
                const councils = data.councils || [];
                council.innerHTML = [''].concat(councils).map((item) => `<option value="${escapeAttr(item)}">${item ? escapeHtml(item) : 'All local councils'}</option>`).join('');
            })
            .catch(() => {
                council.innerHTML = '<option value="">Unable to load local councils</option>';
            });
    }
    function normalizeSearchTerm(value) {
        const map = {
            'Businesses': 'business',
            'Stores': 'shop',
            'Supermarkets': 'supermarket',
            'Pharmacies': 'pharmacy',
            'Hospitals': 'hospital clinic',
            'Restaurants': 'restaurant',
            'Banks': 'bank',
            'Fuel Stations': 'fuel station',
            'Schools': 'school',
            'Hotels': 'hotel',
            'Salon': 'salon',
            'Electronics': 'electronics shop',
            'Fashion': 'fashion shop',
            'Education': 'school',
            'Automotive': 'car service',
            'Real Estate': 'real estate'
        };

        return map[value] || value || 'business';
    }
});
</script>
@endsection
