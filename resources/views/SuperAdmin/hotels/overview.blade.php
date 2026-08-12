@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Hotel Overview</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active">Hotel module summary and live metrics</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Hotel Tenants</h5>
                        <h3>{{ $totalHotelTenants }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Active Hotel Subscriptions</h5>
                        <h3>{{ $activeHotelSubscriptions }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Properties</h5>
                        <h3>{{ $totalProperties }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Rooms</h5>
                        <h3>{{ $totalRooms }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Available Rooms</h5>
                        <h3>{{ $availableRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Occupied Rooms</h5>
                        <h3>{{ $occupiedRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Reserved Rooms</h5>
                        <h3>{{ $reservedRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Reservations Today</h5>
                        <h3>{{ $todayReservations }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Current In-House Guests</h5>
                        <h3>{{ $currentInHouseGuests }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Hotel Revenue Today</h5>
                        <h3>{{ number_format($hotelRevenueToday, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Hotel Revenue This Month</h5>
                        <h3>{{ number_format($hotelRevenueThisMonth, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Outstanding Receivables</h5>
                        <h3>{{ number_format($outstandingReceivables, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="panel" value="{{ $panel }}">
                    <div class="col-md-4">
                        <label class="form-label">Hotel Tenant</label>
                        <select name="company_id" class="form-control">
                            <option value="">All Hotel Tenants</option>
                            @foreach($hotelCompanies as $company)
                                <option value="{{ $company->id }}" {{ (int) $selectedCompanyId === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        @if($panel !== 'overview')
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0 text-capitalize">{{ str_replace('_', ' ', $panel) }}</h5>
            </div>
            <div class="card-body">
                @php $isPaginator = $panelData instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
                @if(($isPaginator && $panelData->count() === 0) || (!$isPaginator && $panelData->isEmpty()))
                    <div class="alert alert-info mb-0">No {{ str_replace('_', ' ', $panel) }} found.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    @foreach(array_keys((array) (($isPaginator ? $panelData->first() : $panelData->first()) ?? [])) as $col)
                                        <th>{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($panelData as $row)
                                    <tr>
                                        @foreach((array) $row as $value)
                                            <td>{{ is_scalar($value) || is_null($value) ? $value : json_encode($value) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($isPaginator)
                        {{ $panelData->links() }}
                    @endif
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
