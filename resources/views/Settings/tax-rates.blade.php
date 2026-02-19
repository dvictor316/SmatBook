<?php $page = 'tax-rates'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    /* Print Customization per User Instructions */
    @media print {
        .d-print-none, .sidebar, .header, .btn, .btn-path, .col-xl-3, .dropdown, .status-toggle { 
            display: none !important; 
        }
        .page-wrapper { margin: 0 !important; padding: 0 !important; margin-left: 0 !important; }
        .col-xl-9 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        .card { border: none !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        th, td { border: 1px solid #dee2e6 !important; padding: 8px !important; }
        .no-sort, th:last-child, td:last-child { display: none !important; } /* Hides Action column */
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
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                        <div class="content-page-header p-0">
                            <h5>Tax Rates</h5>
                            <div class="list-btn d-print-none">
                                <a href="javascript:window.print()" class="btn btn-white border me-2">
                                    <i class="fa fa-print me-2"></i>Print
                                </a>
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_tax_rate_dynamic">
                                    <i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Tax
                                </a>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="card-table">
                                    <div class="card-body">
                                        <div class="table-responsive no-pagination">
                                            <table class="table table-center table-hover datatable">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Tax Rate</th>
                                                        <th>Status</th>
                                                        <th class="no-sort d-print-none">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach (($taxes ?? []) as $tax)
                                                        <tr>
                                                            <td>{{ $tax['Id'] }}</td>
                                                            <td><h2 class="tax-name">{{ $tax['Name'] }}</h2></td>
                                                            <td>{{ $tax['TaxRate'] }}</td>
                                                            <td>
                                                                <span class="d-none d-print-block">{{ $tax['Status'] }}</span>
                                                                <div class="status-toggle d-print-none">
                                                                    @php
                                                                        $taxToggleKey = 'tax_rate_' . $tax['Id'] . '_enabled';
                                                                        $taxToggleOn = isset($settings[$taxToggleKey]) ? (bool) $settings[$taxToggleKey] : true;
                                                                    @endphp
                                                                    <input type="hidden" name="{{ $taxToggleKey }}" value="0">
                                                                    <input id="{{ $tax['StatusId'] }}" class="check" type="checkbox" name="{{ $taxToggleKey }}" value="1" {{ $taxToggleOn ? 'checked' : '' }}>
                                                                    <label for="{{ $tax['StatusId'] }}" class="checktoggle checkbox-bg">{{ $tax['Status'] }}</label>
                                                                </div>
                                                            </td>
                                                            <td class="d-flex align-items-center d-print-none">
                                                                <div class="dropdown dropdown-action">
                                                                    <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu dropdown-menu-end">
                                                                        <ul>
                                                                            <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_tax_rate_{{ $tax['Id'] }}"><i class="far fa-edit me-2"></i>Edit</a></li>
                                                                            <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_tax_rate_{{ $tax['Id'] }}"><i class="far fa-trash-alt me-2"></i>Delete</a></li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            
                                            <div class="col-lg-12 d-print-none">
                                                <div class="btn-path text-end mt-4">
                                                    <a href="{{ route('tax-rates') }}" class="btn btn-cancel bg-primary-light me-3">Cancel</a>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

<div class="modal fade" id="add_tax_rate_dynamic" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('settings.tax-rates.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Tax Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tax Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Tax Rate</label>
                        <input type="text" name="rate" class="form-control" placeholder="e.g. 7.5%" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach (($taxes ?? []) as $tax)
    <div class="modal fade" id="edit_tax_rate_{{ $tax['Id'] }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('settings.tax-rates.update', $tax['Id']) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tax Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tax Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $tax['Name'] }}" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Tax Rate</label>
                            <input type="text" name="rate" class="form-control" value="{{ $tax['TaxRate'] }}" required>
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

    <div class="modal fade" id="delete_tax_rate_{{ $tax['Id'] }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <h5 class="mb-2">Delete tax rate?</h5>
                    <p class="text-muted mb-3">{{ $tax['Name'] }} ({{ $tax['TaxRate'] }})</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('settings.tax-rates.destroy', $tax['Id']) }}">
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
