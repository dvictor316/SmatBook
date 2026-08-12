@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid"><h3>Hotel Guests</h3>
@php $isPaginator = $guests instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp
@if(($isPaginator && $guests->count()===0) || (!$isPaginator && $guests->isEmpty()))
    <div class="alert alert-info">No hotel guests found.</div>
@else
<table class="table table-bordered"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>
@foreach($guests as $guest)
<tr><td>{{ $guest->id }}</td><td>{{ $guest->name }}</td><td>{{ $guest->email }}</td><td>{{ $guest->phone }}</td></tr>
@endforeach
</tbody></table>
@if($isPaginator){{ $guests->links() }}@endif
@endif
</div></div>
@endsection
