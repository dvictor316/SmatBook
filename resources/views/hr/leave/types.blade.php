@extends('layout.app')

@section('title', 'Leave Types')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Leave Types</h3></div>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.leave.types.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Days / Year</label><input type="number" min="0" name="days_allowed_per_year" class="form-control" required></div>
                <div class="col-md-4 d-flex align-items-end gap-3">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_paid" value="1" checked><label class="form-check-label">Paid</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="carry_forward" value="1"><label class="form-check-label">Carry forward</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="requires_approval" value="1" checked><label class="form-check-label">Approval required</label></div>
                </div>
                <div class="col-md-3"><label class="form-label">Max Carry Forward Days</label><input type="number" min="0" name="max_carry_forward_days" class="form-control"></div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Add Leave Type</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Code</th><th>Days / Year</th><th>Paid</th><th>Carry Forward</th></tr></thead>
                    <tbody>
                        @forelse($leaveTypes as $type)
                            <tr>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->code ?: '—' }}</td>
                                <td>{{ $type->days_allowed_per_year }}</td>
                                <td>{{ $type->is_paid ? 'Yes' : 'No' }}</td>
                                <td>{{ $type->carry_forward ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No leave types found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
