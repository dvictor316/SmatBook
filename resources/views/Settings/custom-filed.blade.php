<?php $page = 'custom-filed'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        /* Sidebar Offset Consistency */
        .page-wrapper {
            margin-left: 270px;
            transition: all 0.3s ease-in-out;
        }
        body.mini-sidebar .page-wrapper { margin-left: 80px; }

        @media (max-width: 1200px) {
            .page-wrapper { margin-left: 0 !important; }
        }

        /* Custom Field Table Styling */
        .card { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .thead-light th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #64748b; }
        .badge-status { padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
        .type-pill { background: #e0e7ff; color: #4338ca; padding: 2px 10px; border-radius: 4px; font-size: 12px; }

        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .col-xl-3, .btn, .btn-action-icon, .sidebar, .header, .dataTables_filter, .dataTables_length { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .table { width: 100% !important; border-collapse: collapse; }
            .table th, .table td { border: 1px solid #dee2e6 !important; padding: 8px !important; }
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
                                    <h5 class="fw-bold text-primary"><i class="fas fa-cog me-2"></i>Settings</h5>
                                </div>
                            </div>
                            @component('components.settings-menu')
                            @endcomponent
                        </div>
                    </div>
                </div>

                <div class="col-xl-9 col-md-8">
                    @component('components.page-header')
                        @slot('title')
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <span>Custom Fields</span>
                                <div class="d-flex gap-2">
                                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>
                                    <a href="#" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#add_custom_field">
                                        <i class="fa fa-plus-circle me-1"></i> Add Field
                                    </a>
                                </div>
                            </div>
                        @endslot
                    @endcomponent

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card-table">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <div class="companies-table filed">
                                            <table class="table table-center table-hover datatable mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Modules</th>
                                                        <th>Label</th>
                                                        <th>Type</th>
                                                        <th>Default Value</th>
                                                        <th>Required</th>
                                                        <th class="no-sort text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse (($customFields ?? collect()) as $custom)
                                                        @php
                                                            $isArray = is_array($custom);
                                                            $id = $isArray ? ($custom['id'] ?? $custom['Id'] ?? '-') : ($custom->id ?? '-');
                                                            $module = $isArray ? ($custom['module'] ?? $custom['Modules'] ?? 'General') : ($custom->module ?? 'General');
                                                            $label = $isArray ? ($custom['label'] ?? $custom['Label'] ?? '-') : ($custom->label ?? '-');
                                                            $type = $isArray ? ($custom['type'] ?? $custom['Type'] ?? 'text') : ($custom->type ?? 'text');
                                                            $defaultValue = $isArray ? ($custom['default_value'] ?? $custom['DefaultValue'] ?? '') : ($custom->default_value ?? '');
                                                            $required = $isArray ? ($custom['required'] ?? $custom['Required'] ?? false) : ($custom->required ?? false);
                                                            $isRequired = in_array(strtolower((string) $required), ['1', 'true', 'yes'], true);
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $id }}</td>
                                                            <td class="fw-bold text-dark">{{ $module }}</td>
                                                            <td>{{ $label }}</td>
                                                            <td><span class="type-pill">{{ $type }}</span></td>
                                                            <td class="text-muted italic">{{ $defaultValue ?: '—' }}</td>
                                                            <td>
                                                                @if($isRequired)
                                                                    <span class="badge bg-soft-danger text-danger badge-status">Yes</span>
                                                                @else
                                                                    <span class="badge bg-soft-secondary text-secondary badge-status">No</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="d-flex justify-content-end gap-2">
                                                                    <a class="btn-action-icon" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_custom_field_{{ $id }}">
                                                                        <i class="fe fe-edit text-primary"></i>
                                                                    </a>
                                                                    <a class="btn-action-icon" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_custom_field_{{ $id }}">
                                                                        <i class="fe fe-trash-2 text-danger"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center py-5 text-muted">No custom fields defined.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
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
    <style>
        .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
        .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); }
        .btn-action-icon { 
            width: 32px; 
            height: 32px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 8px; 
            background: #f8f9fa;
        }
        .btn-action-icon:hover { background: #eee; }
    </style>

    <div class="modal fade" id="add_custom_field" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('settings.custom-fields.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Custom Field</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Module</label>
                            <input type="text" name="module" class="form-control" placeholder="e.g. Invoice" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Customer TIN" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control" required>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                                <option value="textarea">Textarea</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Value</label>
                            <input type="text" name="default_value" class="form-control">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="required_add_custom_field" name="required">
                            <label class="form-check-label" for="required_add_custom_field">Required field</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach (($customFields ?? collect()) as $custom)
        @php
            $isArray = is_array($custom);
            $id = $isArray ? ($custom['id'] ?? $custom['Id'] ?? 0) : ($custom->id ?? 0);
            $module = $isArray ? ($custom['module'] ?? $custom['Modules'] ?? 'General') : ($custom->module ?? 'General');
            $label = $isArray ? ($custom['label'] ?? $custom['Label'] ?? '') : ($custom->label ?? '');
            $type = $isArray ? ($custom['type'] ?? $custom['Type'] ?? 'text') : ($custom->type ?? 'text');
            $defaultValue = $isArray ? ($custom['default_value'] ?? $custom['DefaultValue'] ?? '') : ($custom->default_value ?? '');
            $required = $isArray ? ($custom['required'] ?? $custom['Required'] ?? false) : ($custom->required ?? false);
            $isRequired = in_array(strtolower((string) $required), ['1', 'true', 'yes'], true);
        @endphp
        <div class="modal fade" id="edit_custom_field_{{ $id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('settings.custom-fields.update', $id) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Custom Field</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Module</label>
                                <input type="text" name="module" class="form-control" value="{{ $module }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" value="{{ $label }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-control" required>
                                    <option value="text" {{ $type === 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="number" {{ $type === 'number' ? 'selected' : '' }}>Number</option>
                                    <option value="date" {{ $type === 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="select" {{ $type === 'select' ? 'selected' : '' }}>Select</option>
                                    <option value="textarea" {{ $type === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Default Value</label>
                                <input type="text" name="default_value" class="form-control" value="{{ $defaultValue }}">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="required_custom_field_{{ $id }}" name="required" {{ $isRequired ? 'checked' : '' }}>
                                <label class="form-check-label" for="required_custom_field_{{ $id }}">Required field</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete_custom_field_{{ $id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <h5 class="mb-2">Delete custom field?</h5>
                        <p class="text-muted mb-3">{{ $label }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST" action="{{ route('settings.custom-fields.destroy', $id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
