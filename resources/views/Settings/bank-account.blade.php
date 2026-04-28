<?php $page = 'bank-account'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        .bank-page .card {
            border-radius: 18px;
            border: 1px solid #dbe7ff;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
        }

        .bank-page .thead-light th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .bank-settings-card {
            position: sticky;
            top: 92px;
        }

        .bank-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .bank-toolbar {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .bank-table-wrap {
            overflow-x: auto;
        }

        .bank-table {
            min-width: 760px;
        }

        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .col-xl-3, .btn, .dropdown, .sidebar, .header { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .card { box-shadow: none !important; border: 1px solid #eee !important; }
        }

        @media (max-width: 991.98px) {
            .bank-settings-card {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .bank-page-header,
            .bank-toolbar,
            .bank-toolbar .btn,
            .bank-action-group,
            .bank-action-group .btn {
                width: 100%;
            }

            .bank-toolbar .btn,
            .bank-action-group .btn {
                justify-content: center;
            }
        }
    </style>

    <div class="page-wrapper bank-page">
        <div class="content container-fluid">

            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card shadow-sm bank-settings-card">
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

                            <div class="content-page-header p-0 mb-4 bank-page-header">
                                <h5 class="mb-0">Bank Accounts & Payment Channels</h5>
                                <div class="list-btn bank-toolbar">
                                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>
                                    <a href="#" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#bank_details">
                                        <i class="fa fa-plus-circle me-1"></i> Add Channel
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted mb-4">Use this page for bank accounts and settlement channels like Moniepoint, Opay, transfer wallets, or other collection accounts that should feed your books and balance sheet.</p>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="card-table border-0 shadow-none">
                                        <div class="card-body p-0">
                                            <div class="table-responsive no-pagination bank-table-wrap">
                                                <table class="table table-center table-hover datatable mb-0 bank-table">
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
                                                                    <div class="bank-action-group">
                                                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#view_bank_{{ $account->id }}">
                                                                            <i class="far fa-eye me-1"></i>View
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit_bank_{{ $account->id }}">
                                                                            <i class="far fa-edit me-1"></i>Edit
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete_bank_{{ $account->id }}">
                                                                            <i class="far fa-trash-alt me-1"></i>Delete
                                                                        </button>
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
        .bank-action-group {
            display: inline-flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .bank-action-group .btn {
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            display: inline-flex;
            align-items: center;
        }
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
                                <input type="text" class="form-control js-bank-code-source" data-bank-code-part="bank" name="bank_name" value="{{ old('bank_name', $account->name) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" class="form-control" name="account_holder_name" value="{{ old('account_holder_name', $account->account_holder_name ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control js-bank-code-source" data-bank-code-part="account" name="account_number" value="{{ old('account_number', $account->account_number) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Branch</label>
                                <input type="text" class="form-control js-bank-code-source" data-bank-code-part="branch" name="branch" value="{{ old('branch', $account->branch ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" class="form-control js-bank-code-target" name="ifsc_code" value="{{ old('ifsc_code', $account->ifsc_code ?? ($account->swift_code ?? '')) }}">
                                <small class="text-muted d-block mt-2">A suggested code is filled from the bank, branch and account number if you leave this blank.</small>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sanitizeAlphaNum = (value) => String(value || '').replace(/[^a-z0-9]/gi, '').toUpperCase();
            const sanitizeDigits = (value) => String(value || '').replace(/\D/g, '');
            const buildSuggestedCode = (bank, branch, account) => {
                const bankSeed = (sanitizeAlphaNum(bank).slice(0, 4) || 'BANK').padEnd(4, 'X');
                const branchSeed = (sanitizeAlphaNum(branch).slice(0, 3) || 'BRN').padEnd(3, 'X');
                const accountSeed = (sanitizeDigits(account).slice(-4) || '0000').padStart(4, '0');
                return `${bankSeed}-${branchSeed}-${accountSeed}`;
            };

            document.querySelectorAll('form').forEach((form) => {
                const target = form.querySelector('.js-bank-code-target');
                if (!target) return;

                const bankInput = form.querySelector('.js-bank-code-source[data-bank-code-part="bank"]');
                const branchInput = form.querySelector('.js-bank-code-source[data-bank-code-part="branch"]');
                const accountInput = form.querySelector('.js-bank-code-source[data-bank-code-part="account"]');

                let manuallyEdited = target.value.trim() !== '';

                const syncSuggestedCode = () => {
                    if (manuallyEdited) return;
                    target.value = buildSuggestedCode(
                        bankInput ? bankInput.value : '',
                        branchInput ? branchInput.value : '',
                        accountInput ? accountInput.value : ''
                    );
                };

                [bankInput, branchInput, accountInput].forEach((input) => {
                    if (!input) return;
                    input.addEventListener('input', syncSuggestedCode);
                });

                target.addEventListener('input', () => {
                    manuallyEdited = target.value.trim() !== '';
                });

                syncSuggestedCode();
            });
        });
    </script>
@endsection
