<?php $page = 'template-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
    @php
        $selectedPurchaseTemplate = \App\Models\Setting::where('key', 'purchase_template')->value('value') ?: 'purchase_template_1';
        $selectedReceiptTemplate = \App\Models\Setting::where('key', 'receipt_template')->value('value') ?: 'receipt_template_1';
        $templateLogo = !empty($settings['invoice_logo'])
            ? asset($settings['invoice_logo'])
            : (!empty($settings['site_logo']) ? asset($settings['site_logo']) : asset('/assets/img/logos.png'));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="content-page-header">
                                    <h5>Settings</h5>
                                </div>
                            </div>
                            @component('components.settings-menu')
                            @endcomponent
                            </div>
                    </div>
                </div>
                <div class="col-xl-9 col-md-8">
                    <div class="w-100 pt-0">
                        <div class="content-page-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="mb-0">Invoice Templates</h5>
                                <a href="{{ route('invoice-settings') }}" class="btn btn-primary btn-sm text-white">
                                    <i class="fas fa-plus me-1"></i> Add Template Assets
                                </a>
                            </div>
                        </div>
                        <div class="card invoices-tabs-card">
                            <div class="invoice-template-tab invoices-main-tabs">
                                <div class="row align-items-center">
                                    <div class="col-lg-12">
                                        <div class="invoices-tabs">
                                            <ul class="nav nav-tabs">
                                                <li class="nav-item">
                                                    <a id="invoice-tab" data-bs-toggle="tab" data-bs-target="#invoice_tab"
                                                        type="button" role="tab" aria-controls="invoice_tab"
                                                        aria-selected="true" href="javascript:void(0);"
                                                        class="active">Invoice</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a id="purchases-tab" data-bs-toggle="tab"
                                                        data-bs-target="#purchases_tab" type="button" role="tab"
                                                        aria-controls="purchases_tab" aria-selected="true"
                                                        href="javascript:void(0);">Purchases
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt_tab"
                                                        type="button" role="tab" aria-controls="receipt_tab"
                                                        aria-selected="true" href="javascript:void(0);">Receipt
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-content">
                            @livewire('template-invoice')

                            <div class="tab-pane" id="purchases_tab" role="tabpanel" aria-labelledby="purchases-tab" tabindex="0">
                                <div class="card template-invoice-card">
                                    <div class="card-body pb-0">
                                        <div class="invoice-card-title">
                                            <h6>Purchases</h6>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 col-xl-3 col-sm-12 d-md-flex d-sm-block">
                                                <div class="blog grid-blog invoice-blog flex-fill d-flex flex-wrap align-content-betweens active">
                                                    <div class="blog-image">
                                                        <a href="javascript:;" class="img-general">
                                                            <img class="img-fluid" src="{{ $templateLogo }}" alt="Purchase Template Preview" style="height:130px;object-fit:contain;background:#f8fafc;padding:10px;">
                                                        </a>
                                                        <a href="javascript:void(0);" class="preview-invoice">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    </div>
                                                    <div class="invoice-content-title">
                                                        <a href="javascript:;">Operational Purchase</a>
                                                        <form action="{{ route('settings.update') }}" method="POST" class="ms-2">
                                                            @csrf
                                                            <input type="hidden" name="purchase_template" value="purchase_template_1">
                                                            <button type="submit" class="invoice-star border-0 bg-transparent p-0" data-bs-toggle="tooltip" data-bs-placement="left" title="Make as default">
                                                                <i class="{{ $selectedPurchaseTemplate === 'purchase_template_1' ? 'fa-solid text-warning' : 'fa-regular' }} fa-star"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="receipt_tab" role="tabpanel" aria-labelledby="receipt-tab" tabindex="0">
                                <div class="card template-invoice-card mb-0">
                                    <div class="card-body pb-0">
                                        <div class="invoice-card-title">
                                            <h6>Receipt</h6>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 col-xl-3 col-sm-12 d-md-flex d-sm-block">
                                                <div class="blog grid-blog invoice-blog flex-fill d-flex flex-wrap align-content-betweens active">
                                                    <div class="blog-image">
                                                        <a href="javascript:;" class="img-general">
                                                            <img class="img-fluid" src="{{ $templateLogo }}" alt="Receipt Template Preview" style="height:130px;object-fit:contain;background:#f8fafc;padding:10px;">
                                                        </a>
                                                        <a href="javascript:void(0);" class="preview-invoice">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    </div>
                                                    <div class="invoice-content-title">
                                                        <a href="javascript:;">Receipt Standard</a>
                                                        <form action="{{ route('settings.update') }}" method="POST" class="ms-2">
                                                            @csrf
                                                            <input type="hidden" name="receipt_template" value="receipt_template_1">
                                                            <button type="submit" class="invoice-star border-0 bg-transparent p-0" data-bs-toggle="tooltip" data-bs-placement="left" title="Make as default">
                                                                <i class="{{ $selectedReceiptTemplate === 'receipt_template_1' ? 'fa-solid text-warning' : 'fa-regular' }} fa-star"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printContent(el) {
            var restorepage = document.body.innerHTML;
            var printcontent = document.getElementById(el).innerHTML;
            document.body.innerHTML = printcontent;
            window.print();
            document.body.innerHTML = restorepage;
            location.reload(); // Reload to restore functional JS bindings
        }
    </script>
@endsection
