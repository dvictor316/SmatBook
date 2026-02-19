<?php $page = 'seo-settings'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    /* Print Customization per User Instructions */
    @media print {
        .d-print-none, .sidebar, .header, .btn, .modal-footer, .col-xl-3 { 
            display: none !important; 
        }
        .page-wrapper { margin: 0 !important; padding: 0 !important; margin-left: 0 !important; }
        .col-xl-9 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        .card { border: none !important; }
        .form-control { border: 1px solid #ddd !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4 d-print-none">
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
                <div class="card">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center">
                            <h5>SEO Settings</h5>
                            <button onclick="window.print()" class="btn btn-white btn-sm d-print-none">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </div>
                        
                        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label>Meta Title</label>
                                        <input type="text" class="form-control" name="seo_meta_title" value="{{ $settings['seo_meta_title'] ?? '' }}" placeholder="Enter Title">
                                    </div>
                                </div>
                                <div class="col-md-12 description-box">
                                    <div class="input-block mb-3">
                                        <label class="form-control-label">Meta Description</label>
                                        <textarea class="summernote form-control" name="seo_meta_description" placeholder="Type your message">{{ $settings['seo_meta_description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-12 description-box">
                                    <div class="input-block mb-3">
                                        <label class="form-control-label">Meta Keywords</label>
                                        <textarea class="summernote form-control" name="seo_meta_keywords" placeholder="Type your message">{{ $settings['seo_meta_keywords'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="seo-setting">
                                        <h6 class="mb-3">Meta Image</h6>
                                        <div class="profile-picture">
                                            <div class="upload-profile">
                                                <div class="profile-img company-profile-img">
                                                    <img id="company-img" class="img-fluid me-0" src="{{ !empty($settings['seo_meta_image']) ? asset($settings['seo_meta_image']) : URL::asset('/assets/img/companies/company-add-img.svg') }}" alt="profile-img">
                                                </div>
                                                <div class="add-profile">
                                                    <h5>Upload a New Photo</h5>
                                                    <span>Profile-pic.jpg</span>
                                                </div>
                                            </div>
                                            <div class="img-upload d-print-none">
                                                <label class="btn btn-upload">
                                                    Upload <input type="file" name="seo_meta_image" accept=".jpg,.jpeg,.png,.svg">
                                                </label>
                                                <a class="btn btn-remove">Remove</a>
                                            </div>                                      
                                        </div>
                                    </div>                                      
                                </div>

                                <div class="col-md-12 d-print-none">
                                    <div class="modal-footer p-0 pt-3">
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

@includeIf('Settings.seo-modals')

@endsection
