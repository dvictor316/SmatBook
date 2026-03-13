<?php $page = 'invoice-settings'; ?>
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

    /* Upload Zone Styling */
    .logo-upload {
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        background: #fbfcfd;
        transition: all 0.3s;
        position: relative;
    }
    .logo-upload:hover {
        border-color: #0369a1;
        background: #f0f7ff;
    }
    .logo-upload img {
        width: 40px;
        margin-bottom: 10px;
        opacity: 0.7;
    }
    .preview-logo {
        max-height: 56px;
        width: auto;
        border-radius: 6px;
    }
    .file-meta {
        font-size: 12px;
        color: #64748b;
        margin-top: 8px;
    }

    /* Print Optimization */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .btn, .sidebar, .header, .btn-path, .text-info { display: none !important; }
        .col-xl-9 { width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .form-control { border: none !important; font-weight: bold; padding: 0 !important; }
        .logo-upload { border: 1px solid #eee !important; }
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
                                <h5 class="fw-bold text-primary"><i class="fas fa-file-invoice me-2"></i>Settings</h5>
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
                            <h5 class="fw-bold mb-0">Invoice Settings</h5>
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-print-none">
                                <i class="fas fa-print me-1"></i> Print Settings
                            </button>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                        @php
                            $invoiceLogo = !empty($settings['invoice_logo']) ? asset($settings['invoice_logo']) : (!empty($settings['site_logo']) ? asset($settings['site_logo']) : asset('assets/img/logos.png'));
                            $digitalSignature = !empty($settings['digital_signature']) ? asset($settings['digital_signature']) : null;
                        @endphp
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label fw-semibold">Invoice Prefix</label>
                                    <input type="text" class="form-control" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? '' }}" placeholder="e.g. INV-">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label fw-semibold">Digital Signature Name</label>
                                    <input type="text" class="form-control" name="invoice_signatory_name" value="{{ $settings['invoice_signatory_name'] ?? '' }}" placeholder="Enter Signatory Name">
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-12 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label fw-semibold">Invoice Logo</label>
                                    <div class="logo-upload">
                                        <div>
                                            <span><img src="{{ URL::asset('/assets/img/icons/img-drop.svg') }}" alt="upload"></span>
                                            <h6 class="drop-browse">
                                                <span class="text-primary fw-bold">Click to Replace</span><br>or Drag and Drop
                                            </h6>
                                            <p class="text-muted small">Recommended: 200x50px (SVG, PNG)</p>
                                            <input type="file" id="invoice_logo" name="invoice_logo" class="d-none" accept=".jpg,.jpeg,.png,.svg,.webp">
                                            <div class="d-flex justify-content-center gap-2 mt-2">
                                                <label for="invoice_logo" class="btn btn-sm btn-outline-primary">Choose Image</label>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearInvoiceFile('invoice_logo', 'invoice_logo_preview', '{{ $invoiceLogo }}', 'invoice_logo_name')">Reset</button>
                                            </div>
                                            <div class="mt-3">
                                                <img id="invoice_logo_preview" src="{{ $invoiceLogo }}" alt="Current Invoice Logo" class="preview-logo">
                                                <div id="invoice_logo_name" class="file-meta">{{ !empty($settings['invoice_logo']) ? 'Saved invoice logo' : 'Using primary company logo' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-12 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label fw-semibold">Digital Signature Image</label>
                                    <div class="logo-upload">
                                        <div>
                                            <span><img src="{{ URL::asset('/assets/img/icons/img-drop.svg') }}" alt="upload"></span>
                                            <h6 class="drop-browse">
                                                <span class="text-primary fw-bold">Click to Upload</span><br>or Drag and Drop
                                            </h6>
                                            <p class="text-muted small">Clear background PNG preferred</p>
                                            <input type="file" id="digital_signature" name="digital_signature" class="d-none" accept=".jpg,.jpeg,.png,.svg,.webp">
                                            <div class="d-flex justify-content-center gap-2 mt-2">
                                                <label for="digital_signature" class="btn btn-sm btn-outline-primary">Choose Image</label>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearInvoiceFile('digital_signature', 'digital_signature_preview', '{{ $digitalSignature ?? '' }}', 'digital_signature_name')">Reset</button>
                                            </div>
                                            <div class="mt-3">
                                                <img id="digital_signature_preview" src="{{ $digitalSignature ?? $invoiceLogo }}" alt="Current Signature" class="preview-logo">
                                                <div id="digital_signature_name" class="file-meta">{{ $digitalSignature ? 'Saved digital signature' : 'Upload a signature image to enable sign-off' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 mt-4">
                                <div class="btn-path text-end border-top pt-4">
                                    <a href="{{ route('template-invoice') }}" class="btn btn-cancel bg-primary-light me-3 px-4">Back to Templates</a>
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

<script>
    function bindInvoicePreview(inputId, previewId, fileNameId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const fileName = document.getElementById(fileNameId);
        if (!input || !preview || !fileName) return;

        input.addEventListener('change', function () {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            preview.src = URL.createObjectURL(file);
            fileName.textContent = file.name;
        });
    }

    function clearInvoiceFile(inputId, previewId, fallbackSrc, fileNameId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const fileName = document.getElementById(fileNameId);
        if (input) input.value = '';
        if (preview && fallbackSrc) preview.src = fallbackSrc;
        if (fileName) fileName.textContent = 'Reverted to current saved image';
    }

    bindInvoicePreview('invoice_logo', 'invoice_logo_preview', 'invoice_logo_name');
    bindInvoicePreview('digital_signature', 'digital_signature_preview', 'digital_signature_name');
</script>
@endsection
