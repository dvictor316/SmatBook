@extends('layout.mainlayout')

@section('content')
{{-- 
    We use a "wizard-mode" class on the wrapper to give us 
    complete control over the layout via CSS 
--}}
<div class="page-wrapper ms-0 wizard-mode">
    <div class="content container-fluid">
        
        <div class="row justify-content-center align-items-center" style="min-height: 90vh;">
            <div class="col-lg-5 col-md-7">
                
                <div class="card shadow-lg border-0 mt-5">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="avatar avatar-xxl mb-3">
                                <img src="{{ URL::asset('/assets/img/logo.png') }}" alt="Logo" class="img-fluid">
                            </div>
                            <h3 class="fw-bold">Finalize Workspace</h3>
                            <p class="text-muted">Enter your business details to activate your account.</p>
                        </div>

                        <form action="{{ route('saas.store') }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Workspace URL</label>
                                <div class="input-group input-group-lg">
                                    <input type="text" name="domain_name" class="form-control" placeholder="my-business" required>
                                    <span class="input-group-text bg-light text-muted">.myapp.com</span>
                                </div>
                                <small class="text-muted">No spaces or special characters allowed.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Team Size</label>
                                <select name="employees" class="form-select form-select-lg">
                                    <option value="1-5">1 - 5 Staff</option>
                                    <option value="6-20">6 - 20 Staff</option>
                                    <option value="21-100">21 - 100 Staff</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">
                                Save and Continue <i class="fe fe-check-circle ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- HARD RESTRICTION CSS --}}
<style>
    /* 1. Physically hide the Sidebar and Top Header */
    .sidebar, 
    .header, 
    .footer,
    #sidebar-menu,
    .sidebar-overlay { 
        display: none !important; 
        visibility: hidden !important;
        width: 0 !important;
    }
    
    /* 2. Force the Page Wrapper to take up the full screen */
    .page-wrapper { 
        margin-left: 0 !important; 
        padding-left: 0 !important;
        padding-top: 0 !important; 
        left: 0 !important;
        width: 100% !important;
        background-color: #f8f9fa !important;
    }

    /* 3. Remove any padding/margins that might exist in mainlayout */
    body {
        overflow-x: hidden;
    }
</style>
@endsection