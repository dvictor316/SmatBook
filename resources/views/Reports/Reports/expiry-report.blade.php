<?php $page = 'expiry-report'; ?>
@extends('layout.mainlayout')

@section('style')
<style>
    .card-table { border-radius: 15px; overflow: hidden; border: none; }
    .pagination { margin-bottom: 0; gap: 4px; }
    .page-link { border-radius: 12px !important; border: 1px solid #d7e2f0; background: #f8f9fa; color: #102a5a; }
    .page-item.active .page-link { background-color: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,.24); border-color: #2563eb; }
    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .breadcrumb, .btn, .pagination-container { display: none !important; }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .table { width: 100% !important; border-collapse: collapse; }
        .table td, .table th { border: 1px solid #ddd !important; padding: 8px; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ __('Expiry Date Report') }}</h3>
                    <ul class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Expiry Report') }}</li>
                    </ul>
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-white text-dark border rounded-pill shadow-sm">
                        <i class="feather-printer me-1"></i> {{ __('Print') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Days filter --}}
        <form method="GET" action="{{ route('reports.expiry-report') }}" class="row g-2 mb-4 no-print">
            <div class="col-auto">
                <label class="form-label small mb-1">Expiring within (days)</label>
                <select name="days_ahead" class="form-select form-select-sm">
                    @foreach([7, 14, 30, 60, 90] as $d)
                        <option value="{{ $d }}" @selected($daysAhead == $d)>{{ $d }} days</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-primary btn-sm rounded-pill">Apply</button>
            </div>
        </form>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="feather-alert-octagon text-danger fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-semibold text-uppercase">Expired</p>
                            <h4 class="fw-bold mb-0 text-danger">{{ $expired->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="feather-clock text-warning fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-semibold text-uppercase">Expiring in {{ $daysAhead }}d</p>
                            <h4 class="fw-bold mb-0 text-warning">{{ $expiring_soon->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- EXPIRED --}}
        @if($expired->isNotEmpty())
        <div class="card card-table shadow-sm border-0 mb-4">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="feather-alert-octagon me-2"></i>Expired Products ({{ $expired->count() }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="expiredTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Product Name') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th>{{ __('Expiry Date') }}</th>
                                <th>{{ __('Days Overdue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expired as $i => $product)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td><span class="badge bg-light text-muted">{{ $product->sku ?? '—' }}</span></td>
                                <td>{{ number_format($product->stock ?? 0) }}</td>
                                <td class="text-danger fw-semibold">
                                    {{ \Carbon\Carbon::parse($product->expiry_date)->format('d M Y') }}
                                </td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ \Carbon\Carbon::parse($product->expiry_date)->diffInDays(now()) }} days ago
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- EXPIRING SOON --}}
        <div class="card card-table shadow-sm border-0">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="feather-clock me-2"></i>Expiring Within {{ $daysAhead }} Days ({{ $expiring_soon->count() }})
            </div>
            <div class="card-body p-0">
                @if($expiring_soon->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="feather-check-circle fs-1 text-success d-block mb-2"></i>
                        No products expiring within the next {{ $daysAhead }} days.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="expiringTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Product Name') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th>{{ __('Expiry Date') }}</th>
                                <th>{{ __('Days Left') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiring_soon as $i => $product)
                            @php $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($product->expiry_date), false); @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td><span class="badge bg-light text-muted">{{ $product->sku ?? '—' }}</span></td>
                                <td>{{ number_format($product->stock ?? 0) }}</td>
                                <td class="fw-semibold {{ $daysLeft <= 7 ? 'text-danger' : 'text-warning' }}">
                                    {{ \Carbon\Carbon::parse($product->expiry_date)->format('d M Y') }}
                                </td>
                                <td>
                                    <span class="badge {{ $daysLeft <= 7 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $daysLeft }} day{{ $daysLeft == 1 ? '' : 's' }}
                                    </span>
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
