@extends('layout.mainlayout')

@section('page-title', 'Estimates')

@section('content')
@php
    $currencySymbol = $currencySymbol ?? config('app.currency_symbol', '₦');
    $totalEstimates = $estimates->count();
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Estimates
            @endslot
        @endcomponent

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Total Estimates</div>
                                <div class="h4 fw-bold mb-0">{{ number_format($totalEstimates) }}</div>
                            </div>
                            <span class="badge bg-soft-primary text-primary">All</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Sent</div>
                                <div class="h4 fw-bold mb-0">{{ number_format($sent) }}</div>
                            </div>
                            <span class="badge bg-soft-success text-success">Sent</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Draft</div>
                                <div class="h4 fw-bold mb-0">{{ number_format($draft) }}</div>
                            </div>
                            <span class="badge bg-soft-warning text-warning">Draft</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Expired</div>
                                <div class="h4 fw-bold mb-0">{{ number_format($expired) }}</div>
                            </div>
                            <span class="badge bg-soft-danger text-danger">Expired</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">All Estimates</h5>
                    <div class="text-muted small">Track, edit, and convert estimates with confidence.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('estimates.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-circle-plus me-2"></i>Create Estimate
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($estimates->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-2 text-muted">No estimates yet.</div>
                        <a href="{{ route('estimates.create') }}" class="btn btn-outline-primary">
                            Create your first estimate
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>Estimate</th>
                                    <th>Customer</th>
                                    <th>Issue</th>
                                    <th>Expiry</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($estimates as $estimate)
                                    @php
                                        $status = strtolower((string) ($estimate->status ?? 'draft'));
                                        $badge = match ($status) {
                                            'sent' => 'success',
                                            'accepted' => 'primary',
                                            'declined' => 'danger',
                                            'expired' => 'danger',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $estimate->estimate_number ?? ('EST-' . str_pad($estimate->id, 5, '0', STR_PAD_LEFT)) }}
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $estimate->customer?->name ?? 'N/A' }}</div>
                                            <div class="text-muted small">{{ $estimate->customer?->email ?? $estimate->customer?->phone }}</div>
                                        </td>
                                        <td>{{ optional($estimate->issue_date)->format('d M Y') ?? '-' }}</td>
                                        <td>{{ optional($estimate->expiry_date)->format('d M Y') ?? '-' }}</td>
                                        <td class="fw-semibold">{{ $currencySymbol }}{{ number_format($estimate->amount ?? $estimate->total_amount ?? 0, 2) }}</td>
                                        <td>
                                            <span class="badge bg-soft-{{ $badge }} text-{{ $badge }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <a href="#" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('estimates.show', $estimate->id) }}">
                                                        <i class="fa-solid fa-eye me-2"></i>View
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('estimates.edit', $estimate->id) }}">
                                                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit
                                                    </a>
                                                    <form action="{{ route('estimates.destroy', $estimate->id) }}" method="POST" onsubmit="return confirm('Delete this estimate?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fa-solid fa-trash-can me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
