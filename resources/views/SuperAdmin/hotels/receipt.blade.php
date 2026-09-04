@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-receipt-page { background:#eef3f8; color:#061b33; }
    .hotel-receipt-shell { max-width:820px; margin:0 auto; }
    .hotel-receipt-actions { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
    .hotel-receipt { background:#fff; border:1px solid #d8e2ee; border-radius:10px; overflow:hidden; box-shadow:0 16px 38px rgba(15,23,42,.09); }
    .hotel-receipt-head { background:#061b33; color:#fff; padding:22px; display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
    .hotel-receipt-head h2, .hotel-receipt-head p, .hotel-receipt-head small { color:#fff !important; margin:0; }
    .hotel-receipt-head small { color:#f5c451 !important; text-transform:uppercase; letter-spacing:.12em; font-weight:800; }
    .hotel-receipt-body { padding:22px; }
    .hotel-receipt-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
    .hotel-receipt-box { border:1px solid #d8e2ee; border-radius:8px; padding:13px; background:#f8fbff; }
    .hotel-receipt-box span { display:block; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.08em; font-weight:800; }
    .hotel-receipt-box strong { display:block; margin-top:4px; color:#061b33; font-size:18px; }
    .hotel-receipt-table th { background:#f1f5f9; color:#334155; text-transform:uppercase; font-size:12px; }
    .hotel-receipt-total { display:flex; justify-content:space-between; align-items:center; border-top:2px solid #061b33; padding-top:14px; margin-top:14px; font-size:22px; font-weight:900; }
    .hotel-receipt-foot { padding:16px 22px 22px; color:#64748b; }
    @media print {
        @page { size:A4; margin:12mm; }
        .header, .sidebar, .hotel-receipt-actions, .page-wrapper:before { display:none !important; }
        .page-wrapper, .content, .hotel-receipt-page { margin:0 !important; padding:0 !important; background:#fff !important; }
        .hotel-receipt-shell { max-width:none; }
        .hotel-receipt { border:0; box-shadow:none; border-radius:0; }
        body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    }
    @media(max-width:767px){.hotel-receipt-grid{grid-template-columns:1fr}.hotel-receipt-head{display:block}.hotel-receipt-head .text-end{text-align:left !important; margin-top:12px}}
</style>
@endsection

@section('content')
@php
    $paymentTypes = ['payment', 'deposit_applied'];
    $isPayment = in_array((string) $item->type, $paymentTypes, true);
    $receiptNo = 'HTL-RCP-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT);
    $guestName = $folio?->customer?->customer_name ?? $folio?->customer?->name ?? 'Guest';
    $roomNo = $folio?->stay?->room?->room_number ?? 'N/A';
    $backRoute = !empty($isSuperAdminReceipt)
        ? route('super_admin.hotels.index', ['panel' => 'services', 'company_id' => $item->company_id])
        : route('hotel.folios.show', $folio);
@endphp
<div class="page-wrapper hotel-receipt-page">
    <div class="content container-fluid">
        <div class="hotel-receipt-shell">
            <div class="hotel-receipt-actions">
                <a href="{{ $backRoute }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Receipt</button>
            </div>
            <article class="hotel-receipt" data-print-scope data-hotel-receipt-print>
                <div class="hotel-receipt-head">
                    <div>
                        <small>SmartProbook Hotel PMS</small>
                        <h2>{{ $isPayment ? 'Payment Receipt' : 'Service Sales Receipt' }}</h2>
                        <p>{{ $receiptNo }}</p>
                    </div>
                    <div class="text-end">
                        <p>{{ optional($item->service_date ?? $item->created_at)->format('d M Y') }}</p>
                        <small>{{ strtoupper((string) ($item->service_code ?: $item->type)) }}</small>
                    </div>
                </div>
                <div class="hotel-receipt-body">
                    <div class="hotel-receipt-grid">
                        <div class="hotel-receipt-box"><span>Guest</span><strong>{{ $guestName }}</strong></div>
                        <div class="hotel-receipt-box"><span>Room / Folio</span><strong>Room {{ $roomNo }} · {{ $folio?->folio_number ?? 'N/A' }}</strong></div>
                        <div class="hotel-receipt-box"><span>Company / Property</span><strong>{{ $item->company_id }} / {{ $item->property_id }}</strong></div>
                        <div class="hotel-receipt-box"><span>Status</span><strong>{{ ucfirst(str_replace('_', ' ', (string) ($folio?->status ?? 'posted'))) }}</strong></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table hotel-receipt-table align-middle">
                            <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ number_format((float) ($item->quantity ?? 1), 3) }}</td>
                                    <td>{{ number_format((float) ($item->unit_price ?? $item->amount), 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="hotel-receipt-total"><span>Total</span><span>{{ number_format((float) $item->amount, 2) }}</span></div>
                </div>
                <div class="hotel-receipt-foot">
                    Posted by {{ $item->posted_by ? 'User #'.$item->posted_by : 'system' }}. This receipt is backed by the hotel folio ledger and posts into financial reporting through the accounting ledger.
                </div>
            </article>
        </div>
    </div>
</div>
@endsection

@if(request()->boolean('print'))
@section('script')
<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        if (window.smartProbookTriggerPrint) {
            window.smartProbookTriggerPrint('[data-hotel-receipt-print]');
            return;
        }
        window.print();
    }, 350);
});
</script>
@endsection
@endif
