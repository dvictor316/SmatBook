<?php $page = 'company-settings'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        /* Sidebar Offset Consistency */
        .page-wrapper {
            margin-left: 270px;
            transition: all 0.3s ease-in-out;
            background-color: #fdfaf0; /* Matches your Analytics theme */
            min-height: 100vh;
        }
        body.mini-sidebar .page-wrapper { margin-left: 80px; }
        
        @media (max-width: 1200px) {
            .page-wrapper { margin-left: 0 !important; }
        }

        /* Modern Form Styling */
        .card { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .input-block label { font-weight: 600; color: #0369a1; font-size: 13px; margin-bottom: 8px; }
        .form-control { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 15px; }
        .form-control:focus { border-color: #d4af37; box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1); }

        /* Logo Upload Styling */
        .logo-upload { border: 2px dashed #d1d5db; border-radius: 12px; padding: 20px; transition: all 0.2s; background: #fafafa; }
        .logo-upload:hover { border-color: #0369a1; background: #f0f9ff; }
        .sites-logo img { border-radius: 8px; border: 1px solid #eee; margin-top: 10px; max-height: 60px; }

        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; background: white !important; }
            .col-xl-3, .btn, .sidebar, .header, .logo-upload input, .btn-path { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .card { box-shadow: none !important; border: none !important; }
            .form-control { border: none !important; padding: 5px 0 !important; font-weight: bold; }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            
            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="page-header mb-3">
                                <div class="content-page-header">
                                    <h5 class="fw-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Settings</h5>
                                </div>
                            </div>
                            @component('components.settings-menu')
                            @endcomponent
                        </div>
                    </div>
                </div>

                <div class="col-xl-9 col-md-8">
                    <div class="card company-settings-new shadow-sm">
                        <div class="card-body w-100">
                            <div class="content-page-header p-0 mb-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold">Company Profile Settings</h5>
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-print-none">
                                    <i class="fas fa-print me-1"></i> Print Info
                                </button>
                            </div>

                            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @php
                                $siteLogo = \App\Models\Setting::mediaUrl($settings['site_logo'] ?? null, asset('assets/img/logos.png'));
                                $faviconLogo = \App\Models\Setting::mediaUrl($settings['favicon'] ?? null, asset('assets/img/favicon.png'));
                            @endphp
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Company Name</label>
                                        <input type="text" class="form-control" name="company_name" value="{{ $settings['company_name'] ?? '' }}" placeholder="e.g. Acme Corp">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Phone Number</label>
                                        <input type="text" class="form-control" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}" placeholder="+234 ...">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="input-block mb-3">
                                        <label>Company Email</label>
                                        <input type="email" class="form-control" name="company_email" value="{{ $settings['company_email'] ?? '' }}" placeholder="contact@company.com">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Address Line 1</label>
                                        <input type="text" class="form-control" name="address_line_1" value="{{ $settings['address_line_1'] ?? '' }}" placeholder="Street Address">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Address Line 2</label>
                                        <input type="text" class="form-control" name="address_line_2" value="{{ $settings['address_line_2'] ?? '' }}" placeholder="Suite, Building, etc.">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="input-block mb-3">
                                        <label>City</label>
                                        <input type="text" class="form-control" name="company_city" value="{{ $settings['company_city'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="input-block mb-3">
                                        <label>State</label>
                                        <input type="text" class="form-control" name="company_state" value="{{ $settings['company_state'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="input-block mb-3">
                                        <label>Country</label>
                                        <input type="text" class="form-control" name="company_country" value="{{ $settings['company_country'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="input-block mb-3">
                                        <label>Pincode</label>
                                        <input type="text" class="form-control" name="company_pincode" value="{{ $settings['company_pincode'] ?? '' }}">
                                    </div>
                                </div>

                                <hr class="my-4 text-light">

                                <div class="col-lg-12 col-12 mb-4">
                                    <div class="input-block mb-0">
                                        <label>Site Logo</label>
                                        <div class="logo-upload d-flex align-items-center justify-content-between">
                                            <div class="drag-drop flex-grow-1">
                                                <h6 class="drop-browse mb-1 text-dark">
                                                    <span class="text-info fw-bold">Click to upload</span> or drag and drop
                                                </h6>
                                                <p class="text-muted small mb-0">PNG, JPG (Recommended: 800x400px)</p>
                                                <input type="file" name="site_logo" class="position-absolute opacity-0" style="width:100px" accept=".jpg,.jpeg,.png,.svg">
                                            </div>
                                            <span class="sites-logo"><img src="{{ $siteLogo }}" alt="logo"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6 col-12">
                                    <div class="input-block mb-4">
                                        <label>Favicon</label>
                                        <div class="logo-upload d-flex align-items-center justify-content-between">
                                            <div class="drag-drop">
                                                <h6 class="text-dark small mb-0"><span class="text-info">Replace</span> icon</h6>
                                                <input type="file" name="favicon" class="position-absolute opacity-0" style="width:50px" accept=".jpg,.jpeg,.png,.svg">
                                            </div>
                                            <span class="sites-logo"><img src="{{ $faviconLogo }}" alt="favicon" style="max-height: 30px;"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="btn-path text-end mt-4">
                                        <a href="{{ route('settings.index') }}" class="btn btn-cancel bg-primary-light me-3 px-4">Cancel</a>
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
    @endsection
