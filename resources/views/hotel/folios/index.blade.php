@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Guest Folios</h3>
                <p class="text-muted mb-0">Guest account statements and outstanding balances</p>
            </div>
        </div>
        @if($folios->count() === 0)
            <div class="alert alert-info">No guest folios found.</div>
        @else
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Folio</th><th>Guest</th><th>Room</th><th>Charges</th><th>Payments</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        @foreach($folios as $folio)
                            <tr>
                                <td>{{ $folio->folio_number }}</td>
                                <td>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'N/A' }}</td>
                                <td>{{ $folio->stay?->room?->room_number ?? 'N/A' }}</td>
                                <td>{{ number_format((float)$folio->total_charges,2) }}</td>
                                <td>{{ number_format((float)$folio->total_payments,2) }}</td>
                                <td><span class="{{ (float)$folio->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$folio->balance,2) }}</span></td>
                                <td><span class="badge {{ $folio->status === 'open' ? 'bg-warning text-dark' : 'bg-success' }}">{{ ucfirst((string) $folio->status) }}</span></td>
                                <td><a href="{{ route('hotel.folios.show', $folio) }}" class="btn btn-sm btn-outline-primary">Open Statement</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $folios->links() }}</div>
        @endif
    </div>
</div>
@endsection
