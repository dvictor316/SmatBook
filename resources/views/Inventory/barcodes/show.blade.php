@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Barcode Details</h5>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Barcode</dt>
                    <dd class="col-sm-9">{{ $barcode->barcode }}</dd>
                    <dt class="col-sm-3">Product</dt>
                    <dd class="col-sm-9">{{ $barcode->product?->name ?? 'N/A' }}</dd>
                    <dt class="col-sm-3">Type</dt>
                    <dd class="col-sm-9">{{ $barcode->barcode_type ?? 'EAN13' }}</dd>
                    <dt class="col-sm-3">Primary</dt>
                    <dd class="col-sm-9">{!! $barcode->is_primary ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-light text-dark">No</span>' !!}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection