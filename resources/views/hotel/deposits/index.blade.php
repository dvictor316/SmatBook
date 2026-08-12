@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid"><h3>Reservation Deposits</h3>
@php $isPaginator = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
@if(($isPaginator && $deposits->count()===0) || (!$isPaginator && $deposits->isEmpty()))
    <div class="alert alert-info">No deposits found.</div>
@else
<table class="table table-bordered"><thead><tr><th>Reservation</th><th>Guest</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
@foreach($deposits as $deposit)
<tr>
<td>{{ $deposit->reservation_number }}</td>
<td>{{ $deposit->customer?->name ?? 'N/A' }}</td>
<td>{{ number_format($deposit->deposit_received,2) }}</td>
<td>{{ $deposit->status }}</td>
<td>{{ $deposit->created_at }}</td>
</tr>
@endforeach
</tbody></table>
@if($isPaginator){{ $deposits->links() }}@endif
@endif
</div></div>
@endsection
