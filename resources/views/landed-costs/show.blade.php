@extends('layout.app')

@section('title', 'Landed Cost Details')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">{{ $landedCost->cost_type }}</h3></div>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Amount:</strong> {{ number_format((float) $landedCost->amount, 2) }}</div>
                <div class="col-md-4"><strong>Currency:</strong> {{ $landedCost->currency ?: '—' }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($landedCost->status) }}</div>
                <div class="col-md-6"><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $landedCost->allocation_method)) }}</div>
                <div class="col-md-6"><strong>GRN:</strong> {{ $landedCost->grn->grn_number ?? '—' }}</div>
                <div class="col-12"><strong>Description:</strong> {{ $landedCost->description ?: '—' }}</div>
                <div class="col-12"><strong>Notes:</strong> {{ $landedCost->notes ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
