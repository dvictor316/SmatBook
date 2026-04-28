@extends('layout.app')

@section('title', 'Manufacturing Orders')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Manufacturing Orders</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Manufacturing</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('manufacturing.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Order
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>MO #</th><th>Product</th><th>Qty Planned</th><th>Qty Produced</th><th>BOM</th><th>Scheduled Start</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $mo)
                            <tr>
                                <td>{{ $mo->mo_number }}</td>
                                <td>{{ $mo->product->name ?? '—' }}</td>
                                <td>{{ number_format((float) ($mo->planned_quantity ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($mo->produced_quantity ?? 0), 2) }}</td>
                                <td>{{ $mo->bom->bom_number ?? '—' }}</td>
                                <td>{{ $mo->planned_start_date ? $mo->planned_start_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($mo->status) {
                                        'draft' => 'secondary', 'planned' => 'primary', 'in_progress' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst(str_replace('_', ' ', $mo->status)) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('manufacturing.show', $mo) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if(in_array($mo->status, ['draft', 'planned'], true))
                                        <form action="{{ route('manufacturing.start', $mo) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary me-1">Start</button>
                                        </form>
                                    @endif
                                    @if($mo->status === 'in_progress')
                                        <form action="{{ route('manufacturing.complete', $mo) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="quantity_produced" value="{{ $mo->planned_quantity }}">
                                            <button class="btn btn-sm btn-outline-success me-1">Complete</button>
                                        </form>
                                    @endif
                                    @if(in_array($mo->status, ['draft', 'planned'], true))
                                        <form action="{{ route('manufacturing.cancel', $mo) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No manufacturing orders found. <a href="{{ route('manufacturing.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
