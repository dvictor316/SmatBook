@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $palette = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#0891b2','#f97316','#ec4899'];
@endphp
<style>
.sbc2-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.sbc2-table tbody td{font-size:.8rem;vertical-align:middle;}
.sbc2-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;}
.sbc2-bar-wrap{background:#f1f5f9;border-radius:4px;height:10px;overflow:hidden;min-width:60px;}
.sbc2-bar-fill{height:10px;border-radius:4px;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Stock by Category</h5>
                <p class="text-muted mb-0">Inventory grouped and valued by product category.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm sbc2-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Category</th>
                        <th class="text-center">Products</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Value</th>
                        <th style="min-width:130px">Value Share</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        @php
                            $color = $palette[$i % count($palette)];
                            $pct = $grandValue > 0 ? round(($row->total_value / $grandValue) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span class="sbc2-dot" style="background:{{ $color }}"></span><span class="fw-bold">{{ $row->category ?? 'Unassigned' }}</span></td>
                            <td class="text-center">{{ number_format($row->product_count) }}</td>
                            <td class="text-end">{{ number_format($row->total_qty) }}</td>
                            <td class="text-end fw-bold">{{ $fmt($row->total_value) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sbc2-bar-wrap flex-grow-1"><div class="sbc2-bar-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div></div>
                                    <span class="small">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No stock categories found.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr class="table-active fw-bold">
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-center">{{ number_format(collect($rows)->sum('product_count')) }}</td>
                        <td class="text-end">{{ number_format(collect($rows)->sum('total_qty')) }}</td>
                        <td class="text-end">{{ $fmt($grandValue) }}</td>
                        <td></td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
