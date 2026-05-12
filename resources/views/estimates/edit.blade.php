@extends('layout.mainlayout')

@section('page-title', 'Edit Estimate')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">Edit Estimate</h5>
                    <div class="text-muted small">Update the item rows, price list, and customer terms.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('estimates.show', $estimate->id) }}" class="btn btn-light">
                        <i class="fa-solid fa-eye me-2"></i>View
                    </a>
                    <a href="{{ route('estimates.index') }}" class="btn btn-outline-secondary">Back to Estimates</a>
                </div>
            </div>
        </div>

        @include('estimates._form', ['action' => route('estimates.update', $estimate->id)])
    </div>
</div>
@endsection
