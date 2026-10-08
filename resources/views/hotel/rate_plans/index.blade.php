@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Rate Plans</h3>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Create Rate Plan</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('hotel.rate_plans.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Room Type</label><select name="room_type_id" class="form-control" required><option value="">Select</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Meal Plan</label><select name="meal_plan" class="form-control"><option value="room_only">Room Only</option><option value="bed_breakfast">Bed & Breakfast</option><option value="half_board">Half Board</option><option value="full_board">Full Board</option></select></div>
                    <div class="col-md-2"><label class="form-label">Rate</label><input type="number" step="0.01" name="rate" class="form-control" required></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Configured Plans</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Name</th><th>Code</th><th>Room Type</th><th>Meal Plan</th><th>Base Rate</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }}</td>
                            <td>{{ $plan->code }}</td>
                            <td>{{ $plan->roomType?->name ?? 'N/A' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',(string)$plan->meal_plan)) }}</td>
                            <td>{{ number_format((float)$plan->rate, 2) }}</td>
                            <td><span class="badge {{ $plan->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('hotel.rate_plans.duplicate', $plan) }}">@csrf<button class="btn btn-sm btn-light">Duplicate</button></form>
                                    <form method="POST" action="{{ route('hotel.rate_plans.toggle', $plan) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No rate plans configured yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Inventory Restrictions</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('hotel.rate_restrictions.store') }}" class="row g-2 align-items-end mb-3">@csrf
                    <div class="col-md-3"><label class="form-label">Rate Plan</label><select name="rate_plan_id" class="form-select" required><option value="">Select plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="end_date" class="form-control" required></div>
                    <div class="col-md-1"><label class="form-label">Min stay</label><input type="number" min="1" name="min_stay" class="form-control"></div>
                    <div class="col-md-1"><label class="form-label">Max stay</label><input type="number" min="1" name="max_stay" class="form-control"></div>
                    <div class="col-md-2 d-flex flex-column gap-1"><label><input type="checkbox" name="stop_sell" value="1"> Stop sell</label><label><input type="checkbox" name="closed_to_arrival" value="1"> Closed to arrival</label><label><input type="checkbox" name="closed_to_departure" value="1"> Closed to departure</label></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary">Add</button></div>
                </form>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Plan</th><th>Period</th><th>Stay</th><th>Controls</th><th></th></tr></thead><tbody>
                    @forelse($restrictions as $restriction)<tr><td>{{ $restriction->ratePlan?->name ?? 'Plan' }}</td><td>{{ $restriction->start_date?->format('d M Y') }} - {{ $restriction->end_date?->format('d M Y') }}</td><td>{{ $restriction->min_stay ?: 1 }} min / {{ $restriction->max_stay ?: 'No' }} max</td><td>@if($restriction->stop_sell)<span class="badge bg-danger">Stop sell</span>@endif @if($restriction->closed_to_arrival)<span class="badge bg-warning text-dark">CTA</span>@endif @if($restriction->closed_to_departure)<span class="badge bg-warning text-dark">CTD</span>@endif</td><td><form method="POST" action="{{ route('hotel.rate_restrictions.destroy', $restriction) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr>
                    @empty<tr><td colspan="5" class="text-muted">No restrictions configured.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>
@endsection
