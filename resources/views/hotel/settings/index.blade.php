@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid"><h3>Hotel Settings</h3>
@if(!$property)
    <div class="alert alert-info">No property configuration found for this branch. Use Hotel Setup to configure your property.</div>
@else
    <div class="card"><div class="card-body">
        <p><strong>Property:</strong> {{ $property->name }}</p>
        <p><strong>Code:</strong> {{ $property->code }}</p>
        <p><strong>Address:</strong> {{ $property->address }}</p>
        <p><strong>Timezone:</strong> {{ $property->timezone }}</p>
    </div></div>
@endif
</div></div>
@endsection
