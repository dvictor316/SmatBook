@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Guests</h3>
                <p class="text-muted mb-0">Hotel CRM and returning guest visibility</p>
            </div>
        </div>
        @php $isPaginator = $guests instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
        @if(($isPaginator && $guests->count()===0) || (!$isPaginator && $guests->isEmpty()))
            <div class="alert alert-info">No hotel guests found.</div>
        @else
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Guest</th><th>Contact</th><th>Last Stay</th><th>Total Stays</th><th>Total Spend</th><th>Balance</th></tr></thead>
                        <tbody>
                        @foreach($guests as $guest)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $guest->customer_name ?? $guest->name }}</div>
                                    <small class="text-muted">ID #{{ $guest->id }}</small>
                                </td>
                                <td>
                                    <div>{{ $guest->phone ?: 'No phone' }}</div>
                                    <small class="text-muted">{{ $guest->email ?: 'No email' }}</small>
                                </td>
                                <td>{{ $guest->last_stay ? \Carbon\Carbon::parse($guest->last_stay)->format('d M Y') : 'No stay yet' }}</td>
                                <td>{{ $guest->total_stays ?? 0 }}</td>
                                <td>{{ number_format((float) ($guest->total_spend ?? 0), 2) }}</td>
                                <td><span class="{{ (float)($guest->outstanding_balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) ($guest->outstanding_balance ?? 0), 2) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($isPaginator)<div class="mt-3">{{ $guests->links() }}</div>@endif
        @endif
    </div>
</div>
@endsection
