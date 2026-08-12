@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid"><h3>Guest Folios</h3>
@if($folios->count() === 0)
    <div class="alert alert-info">No guest folios found.</div>
@else
<table class="table table-bordered">
<thead><tr><th>Folio</th><th>Guest</th><th>Charges</th><th>Payments</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
@foreach($folios as $folio)
<tr>
<td>{{ $folio->folio_number }}</td>
<td>{{ $folio->customer?->name ?? 'N/A' }}</td>
<td>{{ number_format($folio->total_charges,2) }}</td>
<td>{{ number_format($folio->total_payments,2) }}</td>
<td>{{ number_format($folio->balance,2) }}</td>
<td>{{ $folio->status }}</td>
<td><a href="{{ route('hotel.folios.show', $folio) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
</tr>
@endforeach
</tbody>
</table>
{{ $folios->links() }}
@endif
</div></div>
@endsection
