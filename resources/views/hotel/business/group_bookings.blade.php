@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3 class="mb-3">Group Bookings</h3>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Reservation</th><th>Lead Guest</th><th>Adults</th><th>Children</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>{{ $group->reservation_number }}</td>
                            <td>{{ $group->customer?->customer_name ?? $group->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $group->adults }}</td>
                            <td>{{ $group->children }}</td>
                            <td>{{ number_format((float)$group->total,2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',(string)$group->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No group booking data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $groups->links() }}</div>
    </div>
</div>
@endsection
