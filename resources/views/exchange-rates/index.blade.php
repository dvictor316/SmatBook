@extends('layout.app')

@section('title', 'Exchange Rates')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Exchange Rates</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Exchange Rates</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('exchange-rates.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> Add Rate
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
                            <th>From</th><th>To</th><th>Rate</th><th>Date</th><th>Source</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                            <tr>
                                <td>{{ $rate->base_currency }}</td>
                                <td>{{ $rate->target_currency }}</td>
                                <td>{{ number_format($rate->rate, 6) }}</td>
                                <td>{{ $rate->effective_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $rate->source ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('exchange-rates.edit', $rate) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('exchange-rates.destroy', $rate) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this rate?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No exchange rates. <a href="{{ route('exchange-rates.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rates->hasPages())
            <div class="card-footer">{{ $rates->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
