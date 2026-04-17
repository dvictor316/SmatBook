<?php $page = 'email-settings'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        /* Sidebar Offset Consistency */
        .page-wrapper {
            margin-left: 270px;
            transition: all 0.3s ease-in-out;
            background-color: #f8fafc;
        }
        body.mini-sidebar .page-wrapper { margin-left: 80px; }

        @media (max-width: 1200px) {
            .page-wrapper { margin-left: 0 !important; }
        }

        /* Email Specific Styling */
        .mail-provider {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .mail-provider:hover { border-color: #0369a1; box-shadow: 0 4px 12px rgba(3, 105, 161, 0.08); }
        .mail-provider h4 { margin-bottom: 0; font-size: 16px; font-weight: 700; color: #1e293b; }

        .mail-setting { display: flex; align-items: center; gap: 15px; }
        .mail-setting i { font-size: 18px; color: #64748b; transition: color 0.2s; }
        .mail-setting i:hover { color: #0369a1; }

        .mail-title { font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px; }

        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .col-xl-3, .btn, .sidebar, .header, .status-toggle, .btn-path { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .card { border: 1px solid #eee !important; box-shadow: none !important; }
            .mail-provider { border: 1px solid #ddd !important; }
            input.form-control { border: none !important; font-weight: bold; padding: 0 !important; }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="page-header mb-3">
                                <div class="content-page-header">
                                    <h5 class="fw-bold text-primary"><i class="fas fa-envelope-open-text me-2"></i>Settings</h5>
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
                                <h5 class="fw-bold mb-0">Email Settings</h5>
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-print-none">
                                    <i class="fas fa-print me-1"></i> Print Config
                                </button>
                            </div>

                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mail-title">Mail Providers</h5>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <div class="mail-provider">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-soft-primary p-2 rounded me-3 d-print-none">
                                                <i class="fab fa-php fa-lg text-primary"></i>
                                            </div>
                                            <h4>PHP Mail</h4>
                                        </div>
                                        <div class="mail-setting">
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#php_mail_config">
                                                <i class="fe fe-settings"></i>
                                            </a>
                                            <div class="status-toggle">
                                                <input id="php_mail" class="check" type="checkbox" name="mail_php_enabled" value="1" {{ !empty($settings['mail_php_enabled']) ? 'checked' : '' }}>
                                                <label for="php_mail" class="checktoggle checkbox-bg">checkbox</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <div class="mail-provider">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-soft-info p-2 rounded me-3 d-print-none">
                                                <i class="fas fa-server fa-lg text-info"></i>
                                            </div>
                                            <h4>SMTP</h4>
                                        </div>
                                        <div class="mail-setting">
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#smtp_config">
                                                <i class="fe fe-settings"></i>
                                            </a>
                                            <div class="status-toggle">
                                                <input id="smtp_mail" class="check" type="checkbox" name="mail_smtp_enabled" value="1" {{ !empty($settings['mail_smtp_enabled']) ? 'checked' : '' }}>
                                                <label for="smtp_mail" class="checktoggle checkbox-bg">checkbox</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 opacity-25">

                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email From Name</label>
                                        <input type="text" class="form-control" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? '' }}" placeholder="e.g. Billing Department">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email From Address</label>
                                        <input type="email" class="form-control" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}" placeholder="noreply@company.com">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email Global Footer</label>
                                        <textarea class="form-control" rows="3" name="mail_global_footer" placeholder="Enter Email Global Footer">{{ $settings['mail_global_footer'] ?? '' }}</textarea>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-12 mt-3">
                                    <div class="bg-light p-3 rounded-3 mb-4">
                                        <label class="form-label fw-bold text-primary small">SEND TEST EMAIL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="mail_test_address" value="{{ $settings['mail_test_address'] ?? '' }}" placeholder="Enter email address to test">
                                            <button class="btn btn-outline-primary" type="button">Send Test</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="btn-path text-end">
                                        <button type="button" class="btn btn-cancel bg-primary-light me-3 px-4">Cancel</button>
                                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
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
    <style>
        .bg-soft-primary { background-color: rgba(3, 105, 161, 0.1); }
        .bg-soft-info { background-color: rgba(14, 165, 233, 0.1); }
    </style>
@endsection
