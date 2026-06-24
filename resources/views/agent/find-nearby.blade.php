@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
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

        <section class="agent-card mb-4">
            <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                <strong class="text-uppercase">Daily Search Limit</strong>
                <strong>{{ $usedToday }} / {{ $dailyLimit }} used</strong>
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

        <div class="agent-grid">
            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4>Nearby Results</h4>
                    <span class="agent-pill" id="agentResultCount">0 results</span>
                </div>
                <div id="agentNearbyState" class="text-center py-5 agent-muted">
                    Use your location or search by area to discover businesses.
                </div>
                <div class="agent-grid" id="agentNearbyResults"></div>
            </section>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('agent.leads.store') }}" id="agentSaveNearbyLead" class="d-none">
    @csrf
    <input name="business_name" id="nearbyBusinessName">
    <input name="business_category" id="nearbyBusinessCategory">
    <input name="address" id="nearbyBusinessAddress">
    <input name="source" value="find_nearby">
    <input name="lead_type" value="company">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const results = document.getElementById('agentNearbyResults');
    const state = document.getElementById('agentNearbyState');
    const count = document.getElementById('agentResultCount');
    const keyword = document.getElementById('agentNearbyKeyword');
    const countryOptions = @json($countryOptions ?? []);
    const statesUrl = @json(route('locations.states'));
    const councilsUrl = @json(route('locations.councils'));
    const country = document.getElementById('agentCountry');
    const region = document.getElementById('agentRegion');
    const council = document.getElementById('agentCouncil');
    let coords = null;

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
            renderResults(data.filter((item) => {
                const key = item.place_id || `${item.lat},${item.lon}`;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            }), query);
        } catch (error) {
            state.textContent = 'Search failed. Please try again.';
        }
    }

    function renderResults(items, category) {
        state.textContent = '';
        count.textContent = `${items.length} result${items.length === 1 ? '' : 's'}`;
        if (!items.length) {
            state.textContent = 'No businesses found. Try another category or area.';
            return;
        }
        results.innerHTML = items.map((item) => {
            const name = (item.name || item.display_name || 'Nearby Business').split(',')[0];
            const address = item.display_name || '';
            const extra = item.extratags || {};
            const phone = extra.phone || extra['contact:phone'] || 'Public number not listed';
            const website = extra.website || extra['contact:website'] || '';
            return `<section class="agent-card span-3">
                <div class="agent-lead-card">
                    <span class="agent-initial">${escapeHtml(name.charAt(0).toUpperCase())}</span>
                    <div>
                        <h4>${escapeHtml(name)}</h4>
                        <small>${escapeHtml(address)}</small>
                        <div class="agent-muted mt-2"><i class="fa-solid fa-phone"></i> ${escapeHtml(phone)}</div>
                        ${website ? `<a href="${escapeAttr(website)}" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i> Website</a>` : ''}
                        <div class="agent-actions">
                            <button type="button" class="save-nearby" data-name="${escapeAttr(name)}" data-category="${escapeAttr(category)}" data-address="${escapeAttr(address)}"><i class="fa-solid fa-plus"></i> Save Lead</button>
                        </div>
                    </div>
                </div>
            </section>`;
        }).join('');

        document.querySelectorAll('.save-nearby').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('nearbyBusinessName').value = button.dataset.name;
                document.getElementById('nearbyBusinessCategory').value = button.dataset.category;
                document.getElementById('nearbyBusinessAddress').value = button.dataset.address;
                document.getElementById('agentSaveNearbyLead').submit();
            });
        });
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }
    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
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
