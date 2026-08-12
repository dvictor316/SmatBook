@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Deposits</h3>
                <p class="text-muted mb-0">Reservation deposits held and outstanding pre-arrival funding</p>
            </div>
        </div>
        @php $isPaginator = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
        @if(($isPaginator && $deposits->count()===0) || (!$isPaginator && $deposits->isEmpty()))
            <div class="alert alert-info">No deposits found.</div>
        @else
            <div class="card"><div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0"><thead><tr><th>Reservation</th><th>Guest</th><th>Deposit Received</th><th>Deposit Gap</th><th>Status</th><th>Date</th></tr></thead><tbody>
                @foreach($deposits as $deposit)
                    <tr>
                        <td>{{ $deposit->reservation_number }}</td>
                        <td>{{ $deposit->customer?->customer_name ?? $deposit->customer?->name ?? 'N/A' }}</td>
                        <td>{{ number_format((float)$deposit->deposit_received,2) }}</td>
                        <td>{{ number_format(max(0, (float)$deposit->deposit_required - (float)$deposit->deposit_received),2) }}</td>
                        <td><span class="badge {{ (float)$deposit->deposit_required > (float)$deposit->deposit_received ? 'bg-warning text-dark' : 'bg-success' }}">{{ (float)$deposit->deposit_required > (float)$deposit->deposit_received ? 'More Required' : 'Covered' }}</span></td>
                        <td>{{ optional($deposit->created_at)->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody></table>
            </div></div>
            @if($isPaginator)<div class="mt-3">{{ $deposits->links() }}</div>@endif
        @endif
    </div>
</div>
@endsection
