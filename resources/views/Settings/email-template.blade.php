<?php $page = 'email-template'; ?>
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

    /* Template Card Enhancements */
    .template-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        transition: transform 0.2s, box-shadow 0.2s;
        background: #fff;
        margin-bottom: 24px;
    }
    .template-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    .template-icon {
        width: 45px;
        height: 45px;
        background: #f1f5f9;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0369a1;
        margin-bottom: 15px;
    }
    .template-card h5 {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
        height: 40px; /* Ensures alignment */
        overflow: hidden;
    }

    /* Print Styles */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .btn, .sidebar, .header, .list-btn, .package-edit { display: none !important; }
        .col-xl-9 { width: 100% !important; }
        .template-card { border: 1px solid #ddd !important; break-inside: avoid; }
        .col-xl-4 { width: 33.33% !important; float: left; padding: 10px; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="page-header mb-3">
                            <div class="content-page-header">
                                <h5 class="fw-bold text-primary"><i class="fas fa-layer-group me-2"></i>Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="page-header">
                    <div class="content-page-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold">Email Templates</h5>
                        <div class="list-btn d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="fas fa-print me-1"></i> Print List
                            </button>
                            <a class="btn btn-primary btn-sm rounded-pill px-3" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_custom">
                                <i class="fa fa-plus-circle me-1"></i> Add Template
                            </a>
                        </div>
                    </div>
                </div>

                <div class="email-template-card">
                    <div class="row">
                        @foreach(($emailTemplates ?? []) as $template)
                        <div class="col-xl-4 col-md-6 d-flex">
                            <div class="card template-card w-100">
                                <div class="card-body">
                                    <div class="template-icon">
                                        <i class="fe fe-mail"></i>
                                    </div>
                                    <h5>{{ $template['title'] ?? 'Template' }}</h5>
                                    <p class="text-muted small mb-2">{{ $template['subject'] ?? '' }}</p>
                                    <div class="d-flex package-edit border-top pt-3 mt-2">
                                        <a class="btn btn-soft-primary btn-sm flex-fill me-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_email_{{ $template['id'] }}">
                                            <i class="fe fe-edit me-1"></i> Edit
                                        </a>
                                        <a class="btn btn-soft-danger btn-sm" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_email_{{ $template['id'] }}">
                                            <i class="fe fe-trash-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="modal custom-modal fade" id="add_custom" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h4 class="fw-bold">Add Email Template</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('settings.email-templates.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Template Title</label>
                                        <input type="text" class="form-control" name="title" placeholder="e.g. Monthly Newsletter" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Email Subject</label>
                                        <input type="text" class="form-control" name="subject" placeholder="Enter Subject Line" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Content Editor</label>
                                        <textarea class="summernote form-control" name="content"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-0">
                                <button type="button" data-bs-dismiss="modal" class="btn btn-light me-2">Cancel</button>
                                <button type="submit" class="btn btn-primary">Create Template</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @foreach(($emailTemplates ?? []) as $template)
            <div class="modal custom-modal fade" id="edit_email_{{ $template['id'] }}" role="dialog">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h4 class="fw-bold">Edit Email Template</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('settings.email-templates.update', $template['id']) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-block mb-3">
                                            <label class="form-label">Template Title</label>
                                            <input type="text" class="form-control" name="title" value="{{ $template['title'] ?? '' }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-block mb-3">
                                            <label class="form-label">Email Subject</label>
                                            <input type="text" class="form-control" name="subject" value="{{ $template['subject'] ?? '' }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-block mb-3">
                                            <label class="form-label">Content Editor</label>
                                            <textarea class="summernote form-control" name="content">{{ $template['content'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-0">
                                    <button type="button" data-bs-dismiss="modal" class="btn btn-light me-2">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Template</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal custom-modal fade" id="delete_email_{{ $template['id'] }}" role="dialog">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content text-center p-4">
                        <div class="text-danger mb-3"><i class="fe fe-trash-2 fa-3x"></i></div>
                        <h3 class="fw-bold">Delete Template?</h3>
                        <p class="text-muted">This action cannot be undone.</p>
                        <div class="d-flex gap-2">
                            <button type="button" data-bs-dismiss="modal" class="btn btn-light flex-fill">Cancel</button>
                            <form action="{{ route('settings.email-templates.destroy', $template['id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger flex-fill">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>

<style>
    .btn-soft-primary { background: rgba(3, 105, 161, 0.1); color: #0369a1; }
    .btn-soft-primary:hover { background: #0369a1; color: #fff; }
    .btn-soft-danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .btn-soft-danger:hover { background: #dc3545; color: #fff; }
</style>
@endsection
