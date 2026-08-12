@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h3 class="mb-1">Hotel Dashboard</h3>
                <p class="text-muted mb-0">
                    {{ $property?->name ?? 'All Property View' }}
                    • Operational Date: {{ now()->format('d M Y') }}
                </p>
            </div>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <select name="range" class="form-select">
                    <option value="today" {{ $rangeKey === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $rangeKey === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ $rangeKey === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last_30_days" {{ $rangeKey === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="this_month" {{ $rangeKey === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $rangeKey === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                <button class="btn btn-primary">Apply</button>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Occupancy</small><h4 class="mb-0">{{ number_format($occupancyRate, 1) }}%</h4><span class="text-muted">{{ $occupiedRooms }} of {{ $totalRooms }} rooms</span></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Available Rooms</small><h4 class="mb-0">{{ $availableRooms }}</h4><span class="text-muted">Ready for sale now</span></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">In-House Guests</small><h4 class="mb-0">{{ $inHouseGuests }}</h4><span class="text-muted">Active stays</span></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Today's Revenue</small><h4 class="mb-0">{{ number_format($todayRevenue, 2) }}</h4><span class="text-muted">Charges posted today</span></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Today's Arrivals</small><h5 class="mb-0">{{ $todayArrivals }}</h5></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Today's Departures</small><h5 class="mb-0">{{ $todayDepartures }}</h5></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Reserved Rooms</small><h5 class="mb-0">{{ $reservedRooms }}</h5></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Outstanding Balances</small><h5 class="mb-0">{{ number_format($folioBalances, 2) }}</h5></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>Total Rooms</small><h5>{{ $totalRooms }}</h5></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>Dirty Rooms</small><h5>{{ $dirtyRooms }}</h5></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>Cleaning</small><h5>{{ $cleaningRooms }}</h5></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>Maintenance</small><h5>{{ $maintenanceRooms }}</h5></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>ADR</small><h5>{{ number_format($adr, 2) }}</h5></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="card"><div class="card-body"><small>RevPAR</small><h5>{{ number_format($revpar, 2) }}</h5></div></div></div>
        </div>

        @section('style')
        <style>
            .hotel-shell {
                --hotel-line: #dbe4f0;
                --hotel-panel: #ffffff;
                --hotel-soft: #f4f7fb;
                --hotel-ink: #0f172a;
                --hotel-muted: #64748b;
                --hotel-blue: #2563eb;
                --hotel-green: #16a34a;
                --hotel-amber: #d97706;
                --hotel-red: #dc2626;
            }
            .hotel-shell .hotel-panel {
                background: var(--hotel-panel);
                border: 1px solid var(--hotel-line);
                border-radius: 14px;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }
            .hotel-shell .hotel-topbar {
                display: grid;
                grid-template-columns: 1.3fr 1fr auto;
                gap: 12px;
                margin-bottom: 16px;
            }
            .hotel-shell .hotel-title h3 { margin-bottom: 4px; }
            .hotel-shell .hotel-kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
                margin-bottom: 16px;
            }
            .hotel-shell .hotel-kpi {
                padding: 16px;
                min-height: 122px;
            }
            .hotel-shell .hotel-kpi strong {
                display: block;
                font-size: 28px;
                line-height: 1.1;
                color: var(--hotel-ink);
            }
            .hotel-shell .hotel-mini-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }
            .hotel-shell .hotel-mini-panel {
                padding: 12px 14px;
            }
            .hotel-shell .hotel-main-grid {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(320px, 0.9fr);
                gap: 16px;
                margin-bottom: 16px;
            }
            .hotel-shell .hotel-subgrid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }
            .hotel-shell .hotel-list-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 0;
                border-bottom: 1px solid var(--hotel-line);
            }
            .hotel-shell .hotel-list-item:last-child { border-bottom: 0; }
            .hotel-shell .hotel-status-stack a {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 10px 0;
                color: inherit;
                text-decoration: none;
                border-bottom: 1px solid var(--hotel-line);
            }
            .hotel-shell .hotel-status-stack a:last-child { border-bottom: 0; }
            .hotel-shell .hotel-alert {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                padding: 10px 12px;
                border-radius: 10px;
                text-decoration: none;
                color: inherit;
                margin-bottom: 8px;
                background: var(--hotel-soft);
                border: 1px solid var(--hotel-line);
            }
            .hotel-shell .hotel-alert[data-tone="danger"] { border-color: #fecaca; background: #fff1f2; }
            .hotel-shell .hotel-alert[data-tone="warning"] { border-color: #fed7aa; background: #fff7ed; }
            .hotel-shell .hotel-alert[data-tone="info"] { border-color: #bfdbfe; background: #eff6ff; }
            .hotel-shell .hotel-table table { margin-bottom: 0; }
            @media (max-width: 1199px) {
                .hotel-shell .hotel-topbar,
                .hotel-shell .hotel-main-grid,
                .hotel-shell .hotel-subgrid { grid-template-columns: 1fr; }
                .hotel-shell .hotel-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .hotel-shell .hotel-mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 767px) {
                .hotel-shell .hotel-kpi-grid,
                .hotel-shell .hotel-mini-grid { grid-template-columns: 1fr; }
            }
        </style>
        @endsection

        <div class="row g-3 mb-3">
        <div class="page-wrapper hotel-shell">
            <div class="content container-fluid">
                <div class="hotel-topbar">
                    <div class="hotel-panel hotel-title p-3">
                        <small class="text-uppercase text-muted">Dashboard</small>
                        <h3>Hotel management summary</h3>
                        <div class="text-muted">{{ $property?->name ?? 'All Properties' }} · Business Date {{ now()->format('d M Y') }}</div>
                    </div>
                    <form method="GET" class="hotel-panel p-3 d-flex flex-wrap gap-2 align-items-end">
                        <div>
                            <label class="form-label small mb-1">Property</label>
                            <select name="property_id" class="form-select form-select-sm">
                                <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                                @foreach($properties as $propertyOption)
                                    <option value="{{ $propertyOption->id }}" {{ (int) $propertyId === (int) $propertyOption->id ? 'selected' : '' }}>{{ $propertyOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label small mb-1">Date Range</label>
                            <select name="range" class="form-select form-select-sm">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Revenue by Department</h5></div>
                    <div class="card-body">
                        <div id="hotel-revenue-department" style="min-height: 300px"></div>
                    </div>
                </div>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small mb-1">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                        </div>
                        <button class="btn btn-sm btn-primary">Refresh</button>
                    </form>
                    <div class="hotel-panel p-3 d-grid gap-2">
                        <a href="{{ route('hotel.reservations.create') }}" class="btn btn-sm btn-outline-primary">New Reservation</a>
                        <a href="{{ route('hotel.walkin.create') }}" class="btn btn-sm btn-outline-success">Walk-In</a>
                        <a href="{{ route('hotel.checkin.index') }}" class="btn btn-sm btn-outline-info">Check In</a>
                        <a href="{{ route('hotel.checkout.index') }}" class="btn btn-sm btn-outline-warning">Checkout</a>
                    </div>
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Reservation Source</h5></div>
                <div class="hotel-kpi-grid">
                    <a href="{{ route('hotel.rooms.index', ['status' => 'occupied']) }}" class="hotel-panel hotel-kpi text-decoration-none">
                        <small class="text-muted text-uppercase">Occupancy</small>
                        <strong>{{ number_format($occupancyRate, 1) }}%</strong>
                        <div class="text-muted">{{ $occupiedRooms }} / {{ $totalRooms }} rooms occupied</div>
                    </a>
                    <a href="{{ route('hotel.rooms.index', ['status' => 'available']) }}" class="hotel-panel hotel-kpi text-decoration-none">
                        <small class="text-muted text-uppercase">Available Rooms</small>
                        <strong>{{ $availableRooms }}</strong>
                        <div class="text-muted">Ready to sell immediately</div>
                    </a>
                    <a href="{{ route('hotel.in_house') }}" class="hotel-panel hotel-kpi text-decoration-none">
                        <small class="text-muted text-uppercase">In-House Guests</small>
                        <strong>{{ $inHouseGuests }}</strong>
                        <div class="text-muted">Active stays on property</div>
                    </a>
                    <a href="{{ route('hotel.reports.index') }}" class="hotel-panel hotel-kpi text-decoration-none">
                        <small class="text-muted text-uppercase">Today's Revenue</small>
                        <strong>{{ number_format((float) $todayRevenue, 2) }}</strong>
                        <div class="text-muted">Room, service, and POS charges posted today</div>
                    </a>
                                @foreach($reservationSourceRows as $source)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div class="hotel-mini-grid">
                    <a href="{{ route('hotel.frontdesk') }}" class="hotel-panel hotel-mini-panel text-decoration-none"><small class="text-muted">Today's Arrivals</small><div class="fw-semibold mt-1">{{ $todayArrivals }}</div></a>
                    <a href="{{ route('hotel.frontdesk') }}" class="hotel-panel hotel-mini-panel text-decoration-none"><small class="text-muted">Today's Departures</small><div class="fw-semibold mt-1">{{ $todayDepartures }}</div></a>
                    <a href="{{ route('hotel.rooms.index', ['status' => 'reserved']) }}" class="hotel-panel hotel-mini-panel text-decoration-none"><small class="text-muted">Reserved Rooms</small><div class="fw-semibold mt-1">{{ $reservedRooms }}</div></a>
                    <a href="{{ route('hotel.housekeeping.index') }}" class="hotel-panel hotel-mini-panel text-decoration-none"><small class="text-muted">Dirty Rooms</small><div class="fw-semibold mt-1">{{ $dirtyRooms }}</div></a>
                    <a href="{{ route('hotel.folios.index') }}" class="hotel-panel hotel-mini-panel text-decoration-none"><small class="text-muted">Outstanding Folios</small><div class="fw-semibold mt-1">{{ number_format((float) $folioBalances, 2) }}</div></a>
                        @endif
                    </div>
                <div class="hotel-main-grid">
                    <div class="hotel-panel p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                                </div>
                            @endforeach
                        </div>
                        <div id="hotel-occupancy-trend" style="min-height: 320px"></div>
                    </div>
                    <div class="d-grid gap-3">
                        <div class="hotel-panel p-3">
                            <h5 class="mb-3">Room Status</h5>
                            <div id="hotel-room-status-chart" style="min-height: 190px"></div>
                            <div class="hotel-status-stack mt-2">
                                @foreach($roomStatusBreakdown as $statusItem)
                                    <a href="{{ $statusItem['route'] }}">
                                        <span>{{ $statusItem['label'] }}</span>
                                        <strong>{{ $statusItem['count'] }}</strong>
                                    </a>
                                @endforeach
                            </div>
            <div class="col-xl-4">
                        <div class="hotel-panel p-3">
                            <h5 class="mb-3">Secondary Metrics</h5>
                            <div class="hotel-list-item"><span>Deposits Held</span><strong>{{ number_format((float) $reservationDeposits, 2) }}</strong></div>
                            <div class="hotel-list-item"><span>Maintenance Rooms</span><strong>{{ $maintenanceRooms }}</strong></div>
                            <div class="hotel-list-item"><span>Out of Order</span><strong>{{ $outOfOrderRooms }}</strong></div>
                            <div class="hotel-list-item"><span>ADR</span><strong>{{ number_format((float) $adr, 2) }}</strong></div>
                            <div class="hotel-list-item"><span>RevPAR</span><strong>{{ number_format((float) $revpar, 2) }}</strong></div>
                        <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-secondary">Receive Payment</a>
                        <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark">Open Front Desk</a>
                        <a href="{{ route('hotel.rooms.create') }}" class="btn btn-outline-primary">Add Room</a>
                        <form method="POST" action="{{ route('hotel.night_audit.run') }}">
                <div class="hotel-subgrid">
                    <div class="hotel-panel p-3 hotel-table">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Today's Arrivals</h5>
                            <a href="{{ route('hotel.frontdesk') }}" class="btn btn-sm btn-light">View All</a>
                                <tr>
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Guest</th><th>Room</th><th>Arrival</th><th>Deposit</th></tr></thead>
                            <tbody>
                            @forelse($arrivalsPanel->take(6) as $arrival)
                                <tr>
                                    <td>{{ $arrival->customer?->customer_name ?? 'Walk-In' }}</td>
                                    <td>{{ $arrival->room?->room_number ?? 'Unassigned' }}</td>
                                    <td>{{ optional($arrival->arrival_date)->format('d M') }}</td>
                                    <td>{{ (float) $arrival->deposit_received > 0 ? 'Received' : 'Pending' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No arrivals scheduled.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="hotel-panel p-3 hotel-table">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Today's Departures</h5>
                            <a href="{{ route('hotel.frontdesk') }}" class="btn btn-sm btn-light">View All</a>
                        <table class="table table-sm align-middle mb-0">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Guest</th><th>Room</th><th>Checkout</th><th>Balance</th></tr></thead>
                            <tbody>
                            @forelse($departuresPanel->take(6) as $departure)
                                @php $depBalance = (float) ($departure->balance ?? 0); @endphp
                                <tr>
                                    <td>{{ $departure->customer?->customer_name ?? 'Walk-In' }}</td>
                                    <td>{{ $departure->room?->room_number ?? 'Unassigned' }}</td>
                                    <td>{{ optional($departure->departure_date)->format('d M') }}</td>
                                    <td class="{{ $depBalance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($depBalance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No departures scheduled.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="hotel-panel p-3">
                        <h5 class="mb-3">Revenue by Department</h5>
                        <div id="hotel-revenue-department" style="min-height: 210px"></div>
                    </div>
                    <div class="hotel-panel p-3">
                        <h5 class="mb-3">Management Alerts</h5>
                        @forelse($managementAlerts as $alert)
                            <a href="{{ $alert['route'] }}" class="hotel-alert" data-tone="{{ $alert['tone'] }}">
                                <span>{{ $alert['label'] }}</span>
                                <strong>{{ $alert['count'] }}</strong>
                            </a>
                        @empty
                            <div class="text-muted">No active management alerts.</div>
                        @endforelse
                    </div>
                    <div class="hotel-panel p-3">
                        <h5 class="mb-3">Recent Activity</h5>
                        @foreach($todayActivity as $label => $value)
                            <div class="hotel-list-item">
                                <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                                <strong>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</strong>
                            </div>
                        @endforeach
                    </div>
                    <div class="hotel-panel p-3">
                        <h5 class="mb-3">Reservation Sources</h5>
                        @forelse($reservationSourceRows as $source)
                            <div class="hotel-list-item">
                                <span>{{ ucfirst((string) $source->source) }}</span>
                                <strong>{{ $source->total_count }}</strong>
                            </div>
                        @empty
                            <div class="text-muted">No reservation source data available.</div>
                        @endforelse
                    </div>
                    <div class="hotel-panel p-3">
                        <h5 class="mb-3">Payment Method Distribution</h5>
                        @forelse($paymentMethodDistributionRows as $pay)
                            <div class="hotel-list-item">
                                <span>{{ strtoupper((string) $pay->service_code) }}</span>
                                <strong>{{ number_format((float) $pay->total_amount, 2) }}</strong>
                            </div>
                        @empty
                            <div class="text-muted">No payment records available.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endsection

        @section('script')
        <script src="{{ URL::asset('/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
        <script>
            (function () {
                const occupancyLabels = @json(collect($occupancyTrend)->pluck('label')->values());
                const occupancyValues = @json(collect($occupancyTrend)->pluck('value')->values());
                const roomRevenueValues = @json(collect($revenueTrend)->pluck('room')->values());
                const otherRevenueValues = @json(collect($revenueTrend)->pluck('other')->values());
                const statusLabels = @json(collect($roomStatusBreakdown)->pluck('label')->values());
                const statusValues = @json(collect($roomStatusBreakdown)->pluck('count')->values());

                const trendEl = document.querySelector('#hotel-occupancy-trend');
                if (trendEl) {
                    const trendChart = new ApexCharts(trendEl, {
                        chart: { type: 'line', height: 320, toolbar: { show: false } },
                        stroke: { curve: 'smooth', width: [3, 2, 2] },
                        series: [
                            { name: 'Occupancy %', type: 'line', data: occupancyValues },
                            { name: 'Room Revenue', type: 'column', data: roomRevenueValues },
                            { name: 'Other Revenue', type: 'column', data: otherRevenueValues }
                        ],
                        xaxis: { categories: occupancyLabels },
                        yaxis: [{ title: { text: 'Occupancy %' } }, { opposite: true, title: { text: 'Revenue' } }],
                        colors: ['#2563eb', '#16a34a', '#94a3b8'],
                        legend: { position: 'top' }
                    });
                    trendChart.render();
                }

                const depEl = document.querySelector('#hotel-revenue-department');
                if (depEl) {
                    const depChart = new ApexCharts(depEl, {
                        chart: { type: 'donut', height: 220 },
                        series: @json(array_values($revenueByDepartment)),
                        labels: @json(array_keys($revenueByDepartment)),
                        legend: { position: 'bottom' },
                        colors: ['#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#ef4444', '#94a3b8']
                    });
                    depChart.render();
                }

                const roomStatusEl = document.querySelector('#hotel-room-status-chart');
                if (roomStatusEl) {
                    const statusChart = new ApexCharts(roomStatusEl, {
                        chart: { type: 'donut', height: 190 },
                        series: statusValues,
                        labels: statusLabels,
                        legend: { show: false },
                        colors: ['#16a34a', '#2563eb', '#f59e0b', '#ef4444', '#0ea5e9', '#d97706', '#334155']
                    });
                    statusChart.render();
                }
            })();
        </script>
        @endsection
