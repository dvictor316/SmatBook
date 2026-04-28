@extends('layout.app')

@section('title', 'Milestone Details')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">{{ $milestone->name }}</h3></div>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Project:</strong> {{ $milestone->project->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Customer:</strong> {{ $milestone->customer->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}</div>
                <div class="col-md-4"><strong>Due Date:</strong> {{ $milestone->due_date?->format('d M Y') ?: '—' }}</div>
                <div class="col-md-4"><strong>Billing Amount:</strong> {{ number_format((float) $milestone->billing_amount, 2) }}</div>
                <div class="col-md-4"><strong>Billing Type:</strong> {{ ucfirst(str_replace('_', ' ', $milestone->billing_type)) }}</div>
                <div class="col-12"><strong>Description:</strong> {{ $milestone->description ?: 'No description' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
