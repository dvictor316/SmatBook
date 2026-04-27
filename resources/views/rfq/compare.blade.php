@extends('layout.app')

@section('title', 'Compare Quotes – RFQ #{{ $rfq->rfq_number }}')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Quote Comparison – RFQ #{{ $rfq->rfq_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rfq.index') }}">RFQs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rfq.show', $rfq) }}">#{{ $rfq->rfq_number }}</a></li>
                    <li class="breadcrumb-item active">Compare</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-outline-secondary btn-sm">← Back to RFQ</a>
            </div>
        </div>
    </div>

    @php
        $supplierList = $rfq->suppliers->where('quoted_at', '!=', null);
    @endphp

    @if($supplierList->isEmpty())
        <div class="alert alert-info">No supplier quotes have been submitted yet.</div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:180px;">Item</th>
                                <th>Qty</th>
                                @foreach($supplierList as $rs)
                                    <th class="text-center {{ $rs->is_winner ? 'table-success' : '' }}">
                                        {{ $rs->supplier->name ?? '—' }}
                                        @if($rs->is_winner)
                                            <br><span class="badge bg-success">Winner</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rfq->items as $item)
                            <tr>
                                <td>{{ $item->product->name ?? $item->description ?? '—' }}</td>
                                <td>{{ $item->quantity }} {{ $item->unit }}</td>
                                @foreach($supplierList as $rs)
                                    @php
                                        $qi = $rs->quoteItems->firstWhere('rfq_item_id', $item->id);
                                    @endphp
                                    <td class="text-center {{ $rs->is_winner ? 'table-success' : '' }}">
                                        @if($qi)
                                            {{ number_format($qi->unit_price, 2) }}
                                            @if($qi->delivery_days)
                                                <br><small class="text-muted">{{ $qi->delivery_days }}d delivery</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach

                            {{-- Total row --}}
                            <tr class="fw-bold">
                                <td colspan="2">Total (estimated)</td>
                                @foreach($supplierList as $rs)
                                    @php
                                        $total = $rs->quoteItems->sum(function($qi) use ($rfq) {
                                            $item = $rfq->items->firstWhere('id', $qi->rfq_item_id);
                                            return ($item->quantity ?? 0) * $qi->unit_price;
                                        });
                                    @endphp
                                    <td class="text-center {{ $rs->is_winner ? 'table-success' : '' }}">
                                        {{ number_format($total, 2) }}
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Notes row --}}
                            <tr>
                                <td colspan="2" class="text-muted">Notes</td>
                                @foreach($supplierList as $rs)
                                    <td class="text-center {{ $rs->is_winner ? 'table-success' : '' }}">
                                        {{ $rs->notes ?? '—' }}
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Action row --}}
                            @if(!$rfq->suppliers->where('is_winner', true)->count())
                            <tr>
                                <td colspan="2"></td>
                                @foreach($supplierList as $rs)
                                    <td class="text-center">
                                        <form action="{{ route('rfq.winner', [$rfq, $rs]) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Select as Winner</button>
                                        </form>
                                    </td>
                                @endforeach
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
