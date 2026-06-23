@extends('layout.mainlayout')

@section('title', 'Geo Finder')

@section('content')
@php
    $mapCenter = $center ?: ['lat' => 9.0820, 'lng' => 8.6753, 'label' => 'Nigeria'];
    $selectedCompanyId = $selectedCompany?->id;
@endphp

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQ8fQh34J6LrB2r6E1px8K1i3q9z6iQ=" crossorigin="">

<style>
    .geo-shell {
        margin-left: var(--sb-sidebar-w, 270px);
        min-height: 100vh;
        background: #f6f8fb;
        padding: 24px;
    }
    body.sidebar-collapsed .geo-shell,
    body.mini-sidebar .geo-shell {
        margin-left: var(--sb-sidebar-collapsed, 80px);
    }
    .geo-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .geo-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-end;
        margin-bottom: 18px;
    }
    .geo-title {
        font-size: 22px;
        font-weight: 800;
        color: #111827;
        margin: 0;
    }
    .geo-subtitle {
        color: #64748b;
        font-size: 13px;
        margin-top: 4px;
    }
    .geo-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto auto;
        gap: 12px;
        align-items: end;
    }
    .geo-map {
        width: 100%;
        height: 520px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    .geo-stat {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #edf2f7;
    }
    .geo-stat i {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #e0f2fe;
        color: #0369a1;
    }
    .geo-stat strong {
        display: block;
        color: #0f172a;
        line-height: 1.1;
    }
    .geo-stat span {
        color: #64748b;
        font-size: 12px;
    }
    .geo-result-name {
        font-weight: 700;
        color: #0f172a;
    }
    .geo-result-meta {
        color: #64748b;
        font-size: 12px;
    }
    @media (max-width: 991.98px) {
        .geo-shell { margin-left: 0; padding: 16px; }
        .geo-header { align-items: flex-start; flex-direction: column; }
        .geo-filter-grid { grid-template-columns: 1fr; }
        .geo-map { height: 420px; }
    }
</style>

<div class="geo-shell">
    <div class="geo-header">
        <div>
            <h1 class="geo-title">Geo Finder</h1>
            <div class="geo-subtitle">Locate selected client companies, then find nearby businesses, stores, and service points.</div>
        </div>
        <a href="{{ route('deployment.dashboard') }}" class="btn btn-light border">
            <i class="fas fa-arrow-left me-2"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error') || $lookupError)
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') ?? $lookupError }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="geo-panel p-3 mb-3">
        <form method="GET" action="{{ route('deployment.geo.index') }}" class="geo-filter-grid">
            <div>
                <label class="form-label fw-semibold">Company</label>
                <select name="company_id" class="form-select" required>
                    @forelse($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>
                            {{ $company->name ?? $company->company_name ?? 'Company #' . $company->id }}
                        </option>
                    @empty
                        <option value="">No managed companies found</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="form-label fw-semibold">Search Type</label>
                <select name="category" class="form-select">
                    @foreach($categoryOptions as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label fw-semibold">Radius</label>
                <select name="radius" class="form-select">
                    @foreach([500, 1000, 2000, 5000, 10000] as $option)
                        <option value="{{ $option }}" @selected((int) $radius === $option)>{{ number_format($option / 1000, 1) }} km</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" name="search" value="1" class="btn btn-primary">
                <i class="fas fa-search-location me-2"></i> Find Nearby
            </button>
            <button type="submit" class="btn btn-light border">
                <i class="fas fa-map-marker-alt me-2"></i> Locate
            </button>
        </form>

        @if($selectedCompany)
            <form method="POST" action="{{ route('deployment.geo.geocode-company') }}" class="mt-2">
                @csrf
                <input type="hidden" name="company_id" value="{{ $selectedCompany->id }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-crosshairs me-1"></i> Geocode selected company address
                </button>
                <span class="text-muted small ms-2">
                    {{ $selectedCompany->location_label ?: ($selectedCompany->address ?: 'No saved location yet') }}
                </span>
            </form>
        @endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="geo-stat">
                <i class="fas fa-building"></i>
                <div><strong>{{ number_format($companies->count()) }}</strong><span>Selectable companies</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="geo-stat">
                <i class="fas fa-location-arrow"></i>
                <div><strong>{{ $center ? 'Ready' : 'Needs geocode' }}</strong><span>Selected company location</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="geo-stat">
                <i class="fas fa-store-alt"></i>
                <div><strong>{{ number_format(count($nearbyResults)) }}</strong><span>Nearby matches</span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="geo-panel p-3">
                <div id="geoMap" class="geo-map"></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="geo-panel">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <strong>Nearby Results</strong>
                        <div class="text-muted small">{{ $categoryOptions[$category] ?? 'Businesses' }} within {{ number_format($radius / 1000, 1) }} km</div>
                    </div>
                    @if($selectedCompany)
                        <span class="badge bg-light text-dark border">{{ $selectedCompany->name }}</span>
                    @endif
                </div>
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Place</th>
                                <th>Type</th>
                                <th class="text-end">Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($nearbyResults as $place)
                                <tr>
                                    <td>
                                        <div class="geo-result-name">{{ $place['name'] }}</div>
                                        <div class="geo-result-meta">{{ $place['address'] }}</div>
                                        @if(!empty($place['phone']) || !empty($place['website']))
                                            <div class="geo-result-meta">
                                                {{ $place['phone'] ?? '' }}
                                                @if(!empty($place['website']))
                                                    <a href="{{ $place['website'] }}" target="_blank" rel="noopener" class="ms-1">Website</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-primary border">{{ ucwords(str_replace('_', ' ', $place['type'])) }}</span></td>
                                    <td class="text-end fw-semibold">{{ number_format($place['distance'], 2) }} km</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        Select a company and run a nearby search.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const center = @json($mapCenter);
        const places = @json($nearbyResults);
        const selectedCompany = @json($selectedCompany ? [
            'name' => $selectedCompany->name,
            'address' => $selectedCompany->address,
        ] : null);

        const map = L.map('geoMap').setView([center.lat, center.lng], places.length ? 14 : 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const companyMarker = L.marker([center.lat, center.lng]).addTo(map);
        companyMarker.bindPopup(`<strong>${selectedCompany?.name || center.label}</strong><br>${center.label || selectedCompany?.address || ''}`).openPopup();

        const bounds = L.latLngBounds([[center.lat, center.lng]]);
        places.forEach(function (place) {
            const marker = L.circleMarker([place.lat, place.lng], {
                radius: 7,
                color: '#0369a1',
                weight: 2,
                fillColor: '#38bdf8',
                fillOpacity: .85
            }).addTo(map);
            marker.bindPopup(`<strong>${place.name}</strong><br>${place.type}<br>${place.distance} km`);
            bounds.extend([place.lat, place.lng]);
        });

        if (places.length) {
            map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
        }
    });
</script>
@endsection
