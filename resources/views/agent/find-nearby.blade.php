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
                <div class="col-lg-3">
                    <button type="button" class="agent-button soft w-100" id="agentUseLocation"><i class="fa-solid fa-location-arrow"></i> Use My Location</button>
                </div>
                <div class="col-lg-2">
                    <input class="form-control" id="agentArea" value="FCT" style="border-radius:14px;" aria-label="Search area">
                </div>
                <div class="col-lg-5">
                    <input class="form-control" id="agentNearbyKeyword" placeholder="Search by keyword e.g. Supermarket" style="border-radius:14px;" aria-label="Business keyword">
                </div>
                <div class="col-lg-2">
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
    const area = document.getElementById('agentArea');
    let coords = null;

    document.querySelectorAll('.agent-category-chip').forEach((button) => {
        button.addEventListener('click', () => {
            keyword.value = button.dataset.category;
            searchNearby();
        });
    });

    document.getElementById('agentUseLocation').addEventListener('click', () => {
        if (!navigator.geolocation) {
            state.textContent = 'Location is not supported on this browser.';
            return;
        }
        state.textContent = 'Requesting your location...';
        navigator.geolocation.getCurrentPosition((position) => {
            coords = { lat: position.coords.latitude, lon: position.coords.longitude };
            state.textContent = 'Location ready. Choose a category or search keyword.';
        }, () => {
            state.textContent = 'Location permission was not granted. You can still search by area.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });

    document.getElementById('agentFindBusinesses').addEventListener('click', searchNearby);

    async function searchNearby() {
        const query = keyword.value.trim() || 'business';
        state.textContent = 'Searching nearby businesses...';
        results.innerHTML = '';
        count.textContent = '0 results';

        try {
            let url;
            if (coords) {
                url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=12&q=${encodeURIComponent(query)}&viewbox=${coords.lon - 0.08},${coords.lat + 0.08},${coords.lon + 0.08},${coords.lat - 0.08}&bounded=1`;
            } else {
                url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=12&q=${encodeURIComponent(query + ' ' + area.value)}`;
            }

            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            renderResults(data, query);
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
            return `<section class="agent-card span-4">
                <div class="agent-lead-card">
                    <span class="agent-initial">${escapeHtml(name.charAt(0).toUpperCase())}</span>
                    <div>
                        <h4>${escapeHtml(name)}</h4>
                        <small>${escapeHtml(address)}</small>
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
});
</script>
@endsection
