@extends('layout.app')

@section('title', 'RFQ #{{ $rfq->rfq_number }}')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">RFQ #{{ $rfq->rfq_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rfq.index') }}">RFQs</a></li>
                    <li class="breadcrumb-item active">#{{ $rfq->rfq_number }}</li>
                </ul>
            </div>
            <div class="col-auto d-flex gap-2">
                @if($rfq->status === 'draft')
                    <a href="{{ route('rfq.edit', $rfq) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                    <form action="{{ route('rfq.send', $rfq) }}" method="POST">
                        @csrf
                        <button class="btn btn-primary btn-sm">Send to Suppliers</button>
                    </form>
                @endif
                @if($rfq->status === 'quoted' && $rfq->suppliers->count() > 1)
                    <a href="{{ route('rfq.compare', $rfq) }}" class="btn btn-info btn-sm text-white">Compare Quotes</a>
                @endif
                @if($rfq->status !== 'closed')
                    <form action="{{ route('rfq.destroy', $rfq) }}" method="POST"
                          onsubmit="return confirm('Delete this RFQ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            {{-- RFQ Details --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Title:</strong> {{ $rfq->title }}</p>
                            <p class="mb-1"><strong>Description:</strong> {{ $rfq->description ?? '—' }}</p>
                            <p class="mb-0"><strong>Notes:</strong> {{ $rfq->notes ?? '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Required Date:</strong> {{ $rfq->required_date?->format('d M Y') ?? '—' }}</p>
                            <p class="mb-1"><strong>Closing Date:</strong> {{ $rfq->closing_date?->format('d M Y') ?? '—' }}</p>
                            <p class="mb-0"><strong>Status:</strong>
                                <span class="badge bg-{{ match($rfq->status) {
                                    'draft'=>'secondary','sent'=>'primary','quoted'=>'info','closed'=>'success', default=>'secondary'
                                } }}">{{ ucfirst($rfq->status) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Items</h5></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Product</th><th>Description</th><th>Qty</th><th>Unit</th></tr>
                        </thead>
                        <tbody>
                            @foreach($rfq->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->product->name ?? '—' }}</td>
                                <td>{{ $item->description ?? '—' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Suppliers + Quotes --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Suppliers</h5>
                    @if(in_array($rfq->status, ['sent','quoted']))
                        <a href="{{ route('rfq.suppliers.add', $rfq) }}" class="btn btn-outline-primary btn-sm">Add</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>Supplier</th><th>Quoted</th><th>Winner</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($rfq->suppliers as $rs)
                            <tr>
                                <td>{{ $rs->supplier->name ?? '—' }}</td>
                                <td>
                                    @if($rs->quoted_at)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rs->is_winner)
                                        <span class="badge bg-warning text-dark"><i class="fe fe-star"></i> Winner</span>
                                    @elseif($rs->quoted_at && !$rfq->suppliers->where('is_winner', true)->count())
                                        <form action="{{ route('rfq.winner', [$rfq, $rs]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-xs btn-outline-warning">Select</button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('rfq.suppliers.remove', [$rfq, $rs]) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs btn-outline-danger">×</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No suppliers added.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
