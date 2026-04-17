<?php $page = 'saas-settings'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* Consistent Sidebar Spacing */
    .page-wrapper {
        margin-left: 270px;
        transition: all 0.3s ease-in-out;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }

    @media (max-width: 1200px) {
        .page-wrapper { margin-left: 0 !important; }
    }

    /* Print Logic per User Request */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .sidebar, .header, .modal, .btn, .modal-footer, .d-print-none { 
            display: none !important; 
        }
        .col-xl-9 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .form-control, .select { border: 1px solid #ccc !important; appearance: none !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4 d-print-none">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="page-header mb-3">
                            <div class="content-page-header">
                                <h5 class="fw-bold text-primary">Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">SAAS Settings</h5>
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
                                <i class="fas fa-print me-1"></i> Print Settings
                            </button>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Select Default Currency</label>
                                        <select class="select form-control" name="saas_default_currency">
                                            @php $saasCurrency = $settings['saas_default_currency'] ?? 'United States Dollar ( USD )'; @endphp
                                            <option {{ $saasCurrency === 'United States Dollar ( USD )' ? 'selected' : '' }}>United States Dollar ( USD )</option>
                                            <option {{ $saasCurrency === 'Euro (€)' ? 'selected' : '' }}>Euro (€)</option>
                                            <option {{ $saasCurrency === 'Japanese Yen (¥)' ? 'selected' : '' }}>Japanese Yen (¥)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Days between initial warning and subscription ends</label>
                                        <input type="text" class="form-control" name="saas_warning_days" value="{{ $settings['saas_warning_days'] ?? '7' }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Interval days between warnings</label>
                                        <input type="text" class="form-control" name="saas_warning_interval_days" value="{{ $settings['saas_warning_interval_days'] ?? '2' }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Maximum Free Domain A Subscriber Can Create</label>
                                        <input type="text" class="form-control" name="saas_max_free_domains" value="{{ $settings['saas_max_free_domains'] ?? '1' }}">
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="payment-toggle d-flex align-items-center justify-content-between">
                                        <h5 class="form-title mb-0">Email Verification</h5>
                                        <div class="status-toggle">
                                            <input type="hidden" name="saas_email_verification" value="0">
                                            <input id="rating_1" class="check" type="checkbox" name="saas_email_verification" value="1" {{ !empty($settings['saas_email_verification']) ? 'checked' : '' }}>
                                            <label for="rating_1" class="checktoggle checkbox-bg">checkbox</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-4">
                                    <div class="payment-toggle d-flex align-items-center justify-content-between">
                                        <h5 class="form-title mb-0">Auto approve Domain creation request</h5>
                                        <div class="status-toggle">
                                            <input type="hidden" name="saas_auto_approve_domain" value="0">
                                            <input id="rating_2" class="check" type="checkbox" name="saas_auto_approve_domain" value="1" {{ !empty($settings['saas_auto_approve_domain']) ? 'checked' : '' }}>
                                            <label for="rating_2" class="checktoggle checkbox-bg">checkbox</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 d-print-none">
                                    <div class="modal-footer p-0 border-top pt-4">
                                        <button type="button" class="btn btn-back cancel-btn me-2">Cancel</button>
                                        <button type="submit" class="btn btn-primary paid-continue-btn">Save Changes</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@includeIf('Settings.partials.seo-modals') 
@endsection
