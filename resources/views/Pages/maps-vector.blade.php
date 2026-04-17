<?php $page = 'realtime-map-inbox'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>Global Activity Dashboard</h5>
                <div class="list-btn">
                    <ul class="filter-list">
                        <li>
                            <span class="badge bg-success-light d-flex align-items-center">
                                <span class="pulse-wrapper me-2">
                                    <span class="pulse-dot"></span>
                                </span> 
                                Real-time Tracking Active
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-xl-7 col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0">Live Geographic Distribution</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="dynamic_world_map" style="height: 600px; width: 100%; z-index: 1;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Incoming Streams</h5>
                        <div class="dropdown">
                            <button class="btn btn-white btn-sm border" data-bs-toggle="dropdown">
                                Actions <i class="fas fa-chevron-down ms-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#"><i class="far fa-check-circle me-2"></i>Mark all read</a>
                                <a class="dropdown-item" href="javascript:void(0);" onclick="location.reload();">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Feed
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody id="inbox-list">
                                    @forelse ($messages as $index => $msg)
                                        <tr class="message-row cursor-pointer" 
                                            onclick="focusMarker({{ $index }})"
                                            data-lat="{{ $msg['lat'] }}" 
                                            data-lng="{{ $msg['lng'] }}">
                                            <td class="ps-4" style="width: 60px;">
                                                <div class="avatar-container">
                                                    <div class="avatar avatar-sm {{ ($msg['Class'] ?? 'unread') == 'unread' ? 'bg-primary' : 'bg-light text-dark' }} rounded-circle text-white">
                                                        {{ substr($msg['Name'] ?? 'U', 0, 1) }}
                                                    </div>

                                                    <span class="status-indicator {{ ($msg['Status'] ?? 'offline') == 'online' ? 'bg-success' : 'bg-secondary' }}"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="mb-0 {{ ($msg['Class'] ?? 'unread') == 'unread' ? 'fw-bold' : 'text-muted fw-normal' }}">
                                                    {{ $msg['Name'] }}
                                                    @if(($msg['Status'] ?? 'offline') == 'online')
                                                        <small class="text-success ms-1"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> Online</small>
                                                    @endif
                                                </h6>
                                                <p class="text-truncate mb-0 small text-muted" style="max-width: 200px;">
                                                    {{ $msg['Content'] }}
                                                </p>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="badge bg-light text-dark fw-normal">{{ $msg['Time'] }}</span>
                                                @if($msg['HasAttachment'] ?? false)
                                                    <i class="fas fa-paperclip ms-1 text-muted small"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-5">
                                                <i class="fas fa-map-marker-alt fa-3x text-light mb-3"></i>
                                                <p class="text-muted">No signals found in the current region.</p>
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
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Status Indicator on Avatars */
    .avatar-container { position: relative; width: 32px; }
    .status-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    /* Pulse Animation for Header Badge */
    .pulse-wrapper { position: relative; display: flex; align-items: center; justify-content: center; width: 12px; height: 12px; }
    .pulse-dot { width: 8px; height: 8px; background-color: #28a745; border-radius: 50%; position: relative; }
    .pulse-dot::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #28a745; border-radius: 50%; animation: ripple 1.5s infinite ease-out; }
    @keyframes ripple { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(3.5); opacity: 0; } }

    /* Map Marker Pulsing Effect */
    @keyframes marker-ripple {
        0% { stroke-width: 2; stroke-opacity: 1; }
        100% { stroke-width: 20; stroke-opacity: 0; }
    }
    .online-marker { animation: marker-ripple 2s infinite; }

    /* Table UI */
    .cursor-pointer { cursor: pointer; }
    .message-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .message-row:hover { background-color: #f0f4ff !important; border-left: 3px solid #3d5ee1; transform: translateX(5px); }

    #dynamic_world_map { border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; }
    .avatar-sm { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }
</style>

<script>
    let map, markers = [];
    const messageData = @json($messages);

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        map = L.map('dynamic_world_map', {
            zoomControl: false,
            scrollWheelZoom: true
        }).setView([15, 0], 2);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Render Markers
        messageData.forEach((msg, index) => {
            const isOnline = msg.Status === 'online';
            const markerColor = isOnline ? '#28a745' : (msg.Class === 'unread' ? '#3d5ee1' : '#9e9e9e');

            const marker = L.circleMarker([msg.lat, msg.lng], {
                radius: isOnline ? 10 : 8,
                fillColor: markerColor,
                color: markerColor,
                weight: 2,
                opacity: 0.5,
                fillOpacity: 0.8,
                className: isOnline ? 'online-marker' : ''
            }).addTo(map);

            marker.bindPopup(`
                <div style="text-align: center; padding: 5px;">
                    <span class="badge ${isOnline ? 'bg-success' : 'bg-secondary'} mb-1" style="color:#fff">${msg.Status.toUpperCase()}</span><br>
                    <strong style="color: #3d5ee1;">${msg.Name}</strong><br>
                    <small class="text-muted">${msg.Time}</small>
                </div>
            `);

            markers[index] = marker;
        });
    });

    function focusMarker(index) {
        const target = messageData[index];
        if(!target) return;

        map.flyTo([target.lat, target.lng], 10, {
            animate: true,
            duration: 1.5
        });

        setTimeout(() => {
            markers[index].openPopup();
        }, 1500);
    }
</script>
@endsection