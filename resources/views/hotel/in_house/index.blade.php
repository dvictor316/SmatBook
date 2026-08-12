@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid"><h3>In-House Guests</h3>
@php $isPaginator = $stays instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
@if(($isPaginator && $stays->count()===0) || (!$isPaginator && $stays->isEmpty()))
    <div class="alert alert-info">No in-house guests found.</div>
@else
<table class="table table-bordered"><thead><tr><th>ID</th><th>Room</th><th>Check-In</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($stays as $stay)
<tr>
<td>{{ $stay->id }}</td>
<td>{{ $stay->room_id }}</td>
<td>{{ $stay->checkin_at }}</td>
<td>{{ $stay->status }}</td>
<td>
    <form method="POST" action="{{ route('hotel.checkout', $stay->id) }}">@csrf<button class="btn btn-sm btn-danger">Checkout</button></form>
</td>
</tr>
@endforeach
</tbody></table>
@if($isPaginator){{ $stays->links() }}@endif
@endif
</div></div>
@endsection
