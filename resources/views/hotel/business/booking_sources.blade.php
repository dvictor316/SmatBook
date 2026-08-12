@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Travel Agents / Booking Sources</h3>
                <p class="text-muted mb-0">Channel performance and booking value comparison</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Source</th><th>Bookings</th><th>Revenue</th><th>Average Value</th></tr></thead>
                    <tbody>
                    @forelse($sources as $source)
                        <tr>
                            <td>{{ ucfirst((string)$source->booking_source) }}</td>
                            <td>{{ $source->reservations_count }}</td>
                            <td>{{ number_format((float)$source->gross_value,2) }}</td>
                            <td>{{ number_format($source->reservations_count > 0 ? ((float)$source->gross_value / (int)$source->reservations_count) : 0,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No booking source data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
