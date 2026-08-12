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
    </div>
</div>
@endsection
