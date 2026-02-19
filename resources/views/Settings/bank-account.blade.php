<?php $page = 'bank-account'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        /* Sidebar Offset to match your Dashboard and Analytics pages */
        .page-wrapper {
            margin-left: 270px;
            transition: all 0.3s ease-in-out;
        }
        body.mini-sidebar .page-wrapper { margin-left: 80px; }
        
        @media (max-width: 1200px) {
            .page-wrapper { margin-left: 0 !important; }
        }

        .card { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .thead-light th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        
        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .col-xl-3, .btn, .dropdown, .sidebar, .header { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .card { box-shadow: none !important; border: 1px solid #eee !important; }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            
            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card shadow-sm">
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
                    <div class="card shadow-sm">
                        <div class="card-body w-100">
                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="content-page-header p-0 mb-4 d-flex justify-content-between align-items-center">
                                <h5>Bank Accounts</h5>
                                <div class="list-btn d-flex gap-2">
                                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>
                                    <a href="#" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#bank_details">
                                        <i class="fa fa-plus-circle me-1"></i> Add Bank
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="card-table border-0 shadow-none">
                                        <div class="card-body p-0">
                                            <div class="table-responsive no-pagination">
                                                <table class="table table-center table-hover datatable mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>Bank Name</th>
                                                            <th>Branch</th>
                                                            <th>Account Number</th>
                                                            <th>IFSC Code</th>
                                                            <th class="no-sort text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse (($bankAccounts ?? collect()) as $account)
                                                            <tr>
                                                                <td>{{ $account->id }}</td>
                                                                <td>
                                                                    <h2 class="table-avatar">
                                                                        <a href="{{ url('profile') }}" class="text-dark fw-bold">{{ $account->account_holder_name ?? $account->name }}</a>
                                                                    </h2>
                                                                </td>
                                                                <td><span class="badge bg-soft-info text-info">{{ $account->name }}</span></td>
                                                                <td>{{ $account->branch ?? 'N/A' }}</td>
                                                                <td class="fw-bold">{{ $account->account_number }}</td>
                                                                <td><code>{{ $account->swift_code ?? ($account->ifsc_code ?? 'N/A') }}</code></td>
                                                                <td class="text-end">
                                                                    <div class="dropdown dropdown-action">
                                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                                            <i class="fas fa-ellipsis-v"></i>
                                                                        </a>
                                                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                                            <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_bank_{{ $account->id }}"><i class="far fa-eye me-2 text-info"></i>View</a>
                                                                            <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_bank_{{ $account->id }}"><i class="far fa-edit me-2 text-primary"></i>Edit</a>
                                                                            <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_bank_{{ $account->id }}"><i class="far fa-trash-alt me-2 text-danger"></i>Delete</a>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center py-5 text-muted">No bank accounts found.</td>
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
    </div>
    <style>
        .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
        .btn-action-icon { color: #6c757d; padding: 5px 10px; }
        .btn-action-icon:hover { background: #eee; border-radius: 5px; }
    </style>

    @foreach (($bankAccounts ?? collect()) as $account)
        <div class="modal fade" id="view_bank_{{ $account->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bank Account Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2"><strong>Bank Name:</strong> {{ $account->name }}</div>
                        <div class="mb-2"><strong>Account Holder:</strong> {{ $account->account_holder_name ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>Account Number:</strong> {{ $account->account_number }}</div>
                        <div class="mb-2"><strong>Branch:</strong> {{ $account->branch ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>IFSC/SWIFT:</strong> {{ $account->ifsc_code ?? ($account->swift_code ?? 'N/A') }}</div>
                        <div class="mb-0"><strong>Opening Balance:</strong> {{ number_format((float) ($account->balance ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="edit_bank_{{ $account->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('settings.bank-account.update', $account->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Bank Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $account->name) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" class="form-control" name="account_holder_name" value="{{ old('account_holder_name', $account->account_holder_name ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" name="account_number" value="{{ old('account_number', $account->account_number) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Branch</label>
                                <input type="text" class="form-control" name="branch" value="{{ old('branch', $account->branch ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" class="form-control" name="ifsc_code" value="{{ old('ifsc_code', $account->ifsc_code ?? ($account->swift_code ?? '')) }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="opening_balance" value="{{ old('opening_balance', (float) ($account->balance ?? 0)) }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete_bank_{{ $account->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <h5 class="mb-2">Delete Bank Account?</h5>
                        <p class="text-muted mb-4">This action cannot be undone.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('settings.bank-account.destroy', $account->id) }}" method="POST">
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

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('bank_details');
            if (modalEl && window.bootstrap) {
                new bootstrap.Modal(modalEl).show();
            }
        });
    </script>
    @endif
@endsection
