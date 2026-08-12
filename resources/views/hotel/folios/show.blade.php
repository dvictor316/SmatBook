@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Folio {{ $folio->folio_number }}</h3>
        <p>Status: {{ $folio->status }} | Balance: {{ number_format($folio->balance,2) }}</p>
        <form method="POST" action="{{ route('hotel.folios.items.store', $folio) }}">
            @csrf
            <div class="row">
                <div class="col-md-6"><input type="text" name="description" class="form-control" placeholder="Description" required></div>
                <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                <div class="col-md-3"><button class="btn btn-primary">Post</button></div>
            </div>
        </form>

        <hr>
        <h5>Items</h5>
        <ul>
            @foreach($items as $item)
                <li>{{ $item->description }} — {{ number_format($item->amount,2) }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
