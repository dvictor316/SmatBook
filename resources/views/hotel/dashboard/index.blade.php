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

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Occupancy & Revenue Trend</h5>
                        <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($fromDate)->format('d M') }} - {{ \Illuminate\Support\Carbon::parse($toDate)->format('d M Y') }}</small>
                    </div>
                    <div class="card-body">
                        <div id="hotel-occupancy-trend" style="min-height: 300px"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Revenue by Department</h5></div>
                    <div class="card-body">
                        <div id="hotel-revenue-department" style="min-height: 300px"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Reservation Source</h5></div>
                    <div class="card-body">
                        @if($reservationSourceRows->isEmpty())
                            <p class="text-muted mb-0">No reservation source data available.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($reservationSourceRows as $source)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ ucfirst((string) $source->source) }}</span>
                                        <span class="badge bg-primary">{{ $source->total_count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Payment Method Distribution</h5></div>
                    <div class="card-body">
                        @if($paymentMethodDistributionRows->isEmpty())
                            <p class="text-muted mb-0">No payment records available.</p>
                        @else
                            @foreach($paymentMethodDistributionRows as $pay)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ strtoupper((string) $pay->service_code) }}</span>
                                    <strong>{{ number_format((float) $pay->total_amount, 2) }}</strong>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('hotel.reservations.create') }}" class="btn btn-outline-primary">New Reservation</a>
                        <a href="{{ route('hotel.walkin.create') }}" class="btn btn-outline-success">Walk-In</a>
                        <a href="{{ route('hotel.checkin.index') }}" class="btn btn-outline-info">Check In</a>
                        <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-warning">Checkout</a>
                        <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-secondary">Receive Payment</a>
                        <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark">Open Front Desk</a>
                        <a href="{{ route('hotel.rooms.create') }}" class="btn btn-outline-primary">Add Room</a>
                        <form method="POST" action="{{ route('hotel.night_audit.run') }}">
                            @csrf
                            <input type="hidden" name="audit_date" value="{{ now()->toDateString() }}">
                            <button class="btn btn-outline-danger w-100">Run Night Audit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Today's Activity</h5></div>
                    <div class="card-body">
                        @foreach($todayActivity as $label => $value)
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                                <strong>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Next Arrivals</h5>
                        <a href="{{ route('hotel.frontdesk') }}" class="btn btn-sm btn-light">View All Arrivals</a>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Guest</th><th>Room/Type</th><th>Arrival</th><th>Status</th><th>Deposit</th></tr></thead>
                            <tbody>
                            @forelse($arrivalsPanel as $arrival)
                                <tr>
                                    <td>{{ $arrival->customer?->customer_name ?? 'Walk-in' }}</td>
                                    <td>{{ $arrival->room?->room_number ?? '-' }} / {{ $arrival->roomType?->name ?? '-' }}</td>
                                    <td>{{ optional($arrival->arrival_date)->format('d M') }} {{ $arrival->arrival_time ?? '' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',(string) $arrival->status)) }}</span></td>
                                    <td>{{ (float) $arrival->deposit_received > 0 ? 'Received' : 'Pending' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No upcoming arrivals.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Expected Departures</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Guest</th><th>Room</th><th>Checkout</th><th>Balance</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse($departuresPanel as $departure)
                                <tr>
                                    <td>{{ $departure->customer?->customer_name ?? 'Walk-in' }}</td>
                                    <td>{{ $departure->room?->room_number ?? '-' }}</td>
                                    <td>{{ optional($departure->departure_date)->format('d M') }} {{ $departure->departure_time ?? '' }}</td>
                                    <td>
                                        @php $depBalance = (float) ($departure->balance ?? 0); @endphp
                                        <span class="{{ $depBalance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($depBalance,2) }}</span>
                                    </td>
                                    <td><span class="badge {{ $depBalance > 0 ? 'bg-danger' : 'bg-success' }}">{{ $depBalance > 0 ? 'Payment Due' : 'Clear' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No expected departures.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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

        const trendEl = document.querySelector('#hotel-occupancy-trend');
        if (trendEl) {
            const trendChart = new ApexCharts(trendEl, {
                chart: { type: 'line', height: 300, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: [3, 2, 2] },
                series: [
                    { name: 'Occupancy %', type: 'line', data: occupancyValues },
                    { name: 'Room Revenue', type: 'column', data: roomRevenueValues },
                    { name: 'Other Revenue', type: 'column', data: otherRevenueValues }
                ],
                xaxis: { categories: occupancyLabels },
                yaxis: [{ title: { text: 'Occupancy %' } }, { opposite: true, title: { text: 'Revenue' } }],
                colors: ['#0d6efd', '#20c997', '#6f42c1'],
                legend: { position: 'top' }
            });
            trendChart.render();
        }

        const depEl = document.querySelector('#hotel-revenue-department');
        if (depEl) {
            const depChart = new ApexCharts(depEl, {
                chart: { type: 'donut', height: 300 },
                series: @json(array_values($revenueByDepartment)),
                labels: @json(array_keys($revenueByDepartment)),
                legend: { position: 'bottom' },
                colors: ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#dc3545', '#6c757d']
            });
            depChart.render();
        }
    })();
</script>
@endsection
