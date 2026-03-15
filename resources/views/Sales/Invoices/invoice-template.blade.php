@extends('layout.mainlayout')

@section('title', 'Invoice Template Library')

@section('content')
@php
    $invoiceCompany = optional(auth()->user())->company;
    $logoPath = \App\Models\Setting::where('key', 'invoice_logo')->value('value')
        ?: \App\Models\Setting::where('key', 'site_logo')->value('value');
    $logoUrl = $logoPath ? asset($logoPath) : asset('assets/img/logos.png');
    $companyName = $invoiceCompany?->company_name
        ?: $invoiceCompany?->name
        ?: \App\Models\Setting::where('key', 'company_name')->value('value')
        ?: config('app.name', 'SmartProbook');
    $selectedInvoiceTemplate = \App\Models\Setting::where('key', 'invoice_template')->value('value') ?: 'template_1';
    $templates = [
        ['key' => 'template_1', 'name' => 'Executive Blue', 'route' => route('invoice-one-a'), 'summary' => 'Classic branded invoice with a clean commercial layout.'],
        ['key' => 'template_2', 'name' => 'Compact Ledger', 'route' => route('invoice-two'), 'summary' => 'Condensed invoice view for faster operational printing.'],
        ['key' => 'template_3', 'name' => 'Statement Pro', 'route' => route('invoice-three'), 'summary' => 'Detailed client-facing invoice with stronger totals emphasis.'],
        ['key' => 'template_4', 'name' => 'Corporate Edge', 'route' => route('invoice-four-a'), 'summary' => 'Wide professional layout suited for enterprise billing.'],
        ['key' => 'template_5', 'name' => 'Premium Signature', 'route' => route('invoice-five'), 'summary' => 'Presentation-heavy invoice for premium proposals and collections.'],
    ];
@endphp

<div id="main-content-wrapper" class="container-fluid px-4 pb-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1">Invoice Template Library</h4>
                <p class="text-muted mb-0">Preview, choose, and brand invoice templates for {{ $companyName }}.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('template-invoice') }}" class="btn btn-outline-secondary border">
                    <i class="fas fa-layer-group me-1"></i> Manage Templates
                </a>
                <a href="{{ route('invoice-settings') }}" class="btn btn-primary text-white">
                    <i class="fas fa-plus me-1"></i> Add Template Assets
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @foreach($templates as $template)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100 {{ $selectedInvoiceTemplate === $template['key'] ? 'border border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="rounded-3 border bg-light d-flex align-items-center justify-content-center p-4 mb-3" style="min-height: 180px;">
                            <img src="{{ $logoUrl }}" alt="Invoice Template Preview" style="max-height: 80px; width: auto;">
                        </div>
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h5 class="mb-1">{{ $template['name'] }}</h5>
                                <p class="text-muted small mb-0">{{ $template['summary'] }}</p>
                            </div>
                            @if($selectedInvoiceTemplate === $template['key'])
                                <span class="badge bg-primary-subtle text-primary">Default</span>
                            @endif
                        </div>
                        <div class="mt-auto pt-3 d-flex align-items-center justify-content-between gap-2">
                            <a href="{{ $template['route'] }}" class="btn btn-light border">
                                <i class="fas fa-eye me-1"></i> Preview
                            </a>
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="invoice_template" value="{{ $template['key'] }}">
                                <button type="submit" class="btn {{ $selectedInvoiceTemplate === $template['key'] ? 'btn-outline-secondary' : 'btn-primary text-white' }}">
                                    <i class="fas fa-star me-1"></i>
                                    {{ $selectedInvoiceTemplate === $template['key'] ? 'Selected' : 'Make Default' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
