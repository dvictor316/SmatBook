<?php $page = 'ledger'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Vendor Ledger
            @endslot
        @endcomponent

        @isset($vendor)
            @php
                $transactionCount = $transactions->count();
                $credits = $transactions->filter(fn ($item) => $item->amount >= 0)->sum('amount');
                $debits = abs($transactions->filter(fn ($item) => $item->amount < 0)->sum('amount'));
            @endphp

            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <div class="fw-semibold mb-1">Please review the form and try again.</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <style>
                .vendor-ledger-shell {
                    display: grid;
                    gap: 24px;
                }
                .vendor-ledger-hero,
                .vendor-ledger-card {
                    background: #fff;
                    border: 1px solid #e9eef5;
                    border-radius: 24px;
                    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
                }
                .vendor-ledger-hero {
                    padding: 28px;
                }
                .vendor-ledger-head {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 20px;
                    flex-wrap: wrap;
                }
                .vendor-ledger-identity {
                    display: flex;
                    align-items: center;
                    gap: 18px;
                    min-width: 0;
                }
                .vendor-ledger-avatar {
                    width: 82px;
                    height: 82px;
                    border-radius: 24px;
                    overflow: hidden;
                    border: 4px solid #f8fafc;
                    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.10);
                    flex-shrink: 0;
                    background: #eef4ff;
                }
                .vendor-ledger-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .vendor-ledger-title {
                    font-size: 2rem;
                    font-weight: 800;
                    color: #0f172a;
                    line-height: 1.1;
                    margin-bottom: 6px;
                }
                .vendor-ledger-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 14px;
                    color: #64748b;
                    font-size: 0.95rem;
                }
                .vendor-ledger-meta a {
                    color: inherit;
                    text-decoration: none;
                }
                .vendor-ledger-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    justify-content: flex-end;
                }
                .vendor-ledger-summary {
                    margin-top: 24px;
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 16px;
                }
                .vendor-ledger-stat {
                    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
                    border: 1px solid #e6edf7;
                    border-radius: 20px;
                    padding: 18px;
                }
                .vendor-ledger-stat-label {
                    font-size: 0.78rem;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    font-weight: 700;
                    color: #94a3b8;
                    margin-bottom: 8px;
                }
                .vendor-ledger-stat-value {
                    font-size: 1.55rem;
                    font-weight: 800;
                    color: #0f172a;
                    line-height: 1;
                }
                .vendor-ledger-stat-note {
                    margin-top: 8px;
                    color: #64748b;
                    font-size: 0.86rem;
                }
                .vendor-ledger-card {
                    padding: 24px;
                }
                .vendor-ledger-card-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    flex-wrap: wrap;
                    margin-bottom: 18px;
                }
                .vendor-ledger-tools {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .vendor-ledger-filter {
                    display: none;
                    padding: 18px;
                    border-radius: 18px;
                    background: #f8fafc;
                    border: 1px solid #e6edf7;
                    margin-bottom: 18px;
                }
                .vendor-ledger-filter.show {
                    display: block;
                }
                .vendor-ledger-table {
                    border-collapse: separate;
                    border-spacing: 0 12px;
                }
                .vendor-ledger-table thead th {
                    border: 0;
                    background: #f3f6fb;
                    color: #334155;
                    font-size: 0.8rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    padding: 14px 16px;
                }
                .vendor-ledger-table thead th:first-child {
                    border-top-left-radius: 16px;
                    border-bottom-left-radius: 16px;
                }
                .vendor-ledger-table thead th:last-child {
                    border-top-right-radius: 16px;
                    border-bottom-right-radius: 16px;
                }
                .vendor-ledger-table tbody td {
                    background: #fff;
                    border-top: 1px solid #edf2f7;
                    border-bottom: 1px solid #edf2f7;
                    padding: 16px;
                    vertical-align: middle;
                }
                .vendor-ledger-table tbody td:first-child {
                    border-left: 1px solid #edf2f7;
                    border-top-left-radius: 18px;
                    border-bottom-left-radius: 18px;
                }
                .vendor-ledger-table tbody td:last-child {
                    border-right: 1px solid #edf2f7;
                    border-top-right-radius: 18px;
                    border-bottom-right-radius: 18px;
                }
                .ledger-entry-title {
                    font-weight: 700;
                    color: #0f172a;
                    margin-bottom: 4px;
                }
                .ledger-entry-sub {
                    color: #64748b;
                    font-size: 0.87rem;
                }
                .ledger-amount-credit {
                    color: #16a34a;
                    font-weight: 800;
                }
                .ledger-amount-debit {
                    color: #dc2626;
                    font-weight: 800;
                }
                .vendor-ledger-empty {
                    padding: 52px 18px;
                    text-align: center;
                    color: #64748b;
                }
                .vendor-ledger-empty i {
                    font-size: 2rem;
                    color: #94a3b8;
                    margin-bottom: 12px;
                }
                @media (max-width: 991.98px) {
                    .vendor-ledger-summary {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                }
                @media (max-width: 767.98px) {
                    .vendor-ledger-hero,
                    .vendor-ledger-card {
                        padding: 18px;
                        border-radius: 20px;
                    }
                    .vendor-ledger-title {
                        font-size: 1.55rem;
                    }
                    .vendor-ledger-summary {
                        grid-template-columns: 1fr;
                    }
                    .vendor-ledger-actions,
                    .vendor-ledger-tools {
                        width: 100%;
                    }
                    .vendor-ledger-actions .btn,
                    .vendor-ledger-tools .btn {
                        flex: 1 1 auto;
                        justify-content: center;
                    }
                }
            </style>

            <div class="vendor-ledger-shell">
                <section class="vendor-ledger-hero">
                    <div class="vendor-ledger-head">
                        <div class="vendor-ledger-identity">
                            <div class="vendor-ledger-avatar">
                                <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}">
                            </div>
                            <div>
                                <div class="vendor-ledger-title">{{ $vendor->name }}</div>
                                <div class="vendor-ledger-meta">
                                    <span><i class="fa-regular fa-envelope me-1"></i><a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a></span>
                                    <span><i class="fa-solid fa-phone me-1"></i>{{ $vendor->phone ?: 'No phone added' }}</span>
                                    <span><i class="fa-solid fa-location-dot me-1"></i>{{ $vendor->address ?: 'No address added' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="vendor-ledger-actions">
                            <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#vendorProfileModal">
                                <i class="fa-regular fa-image me-2"></i>Update Profile
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                <i class="fa-solid fa-plus me-2"></i>Add Transaction
                            </button>
                        </div>
                    </div>

                    <div class="vendor-ledger-summary">
                        <div class="vendor-ledger-stat">
                            <div class="vendor-ledger-stat-label">Closing Balance</div>
                            <div class="vendor-ledger-stat-value">₦{{ number_format($closingBalance ?? 0, 2) }}</div>
                            <div class="vendor-ledger-stat-note">Live running balance across all entries.</div>
                        </div>
                        <div class="vendor-ledger-stat">
                            <div class="vendor-ledger-stat-label">Credits</div>
                            <div class="vendor-ledger-stat-value">₦{{ number_format($credits, 2) }}</div>
                            <div class="vendor-ledger-stat-note">Positive value added to the ledger.</div>
                        </div>
                        <div class="vendor-ledger-stat">
                            <div class="vendor-ledger-stat-label">Debits</div>
                            <div class="vendor-ledger-stat-value">₦{{ number_format($debits, 2) }}</div>
                            <div class="vendor-ledger-stat-note">Outflows and negative adjustments.</div>
                        </div>
                        <div class="vendor-ledger-stat">
                            <div class="vendor-ledger-stat-label">Transactions</div>
                            <div class="vendor-ledger-stat-value">{{ $transactionCount }}</div>
                            <div class="vendor-ledger-stat-note">Chronological history for this vendor.</div>
                        </div>
                    </div>
                </section>

                <section class="vendor-ledger-card">
                    <div class="vendor-ledger-card-head">
                        <div>
                            <h4 class="mb-1">Ledger Activity</h4>
                            <p class="text-muted mb-0">Review, filter, export, print, and manage all entries from one place.</p>
                        </div>
                        <div class="vendor-ledger-tools">
                            <button type="button" class="btn btn-light border" id="toggleLedgerFilters">
                                <i class="fa-solid fa-filter me-2"></i>Filter
                            </button>
                            <button type="button" class="btn btn-light border" id="exportLedgerCsv">
                                <i class="fa-solid fa-download me-2"></i>Export CSV
                            </button>
                            <button type="button" class="btn btn-light border" onclick="window.print()">
                                <i class="fa-solid fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>

                    <div class="vendor-ledger-filter" id="ledgerFilterPanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" id="ledgerSearch" placeholder="Search reference, name, or mode">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mode</label>
                                <select class="form-select" id="ledgerMode">
                                    <option value="">All modes</option>
                                    @foreach($transactions->pluck('mode')->filter()->unique()->sort()->values() as $mode)
                                        <option value="{{ $mode }}">{{ $mode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" id="ledgerDate">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary w-100" id="resetLedgerFilters">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table vendor-ledger-table align-middle mb-0" id="vendorLedgerTable">
                            <thead>
                                <tr>
                                    <th>Entry</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th>Running Balance</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $runningBalance = 0; @endphp
                                @forelse ($transactions as $transaction)
                                    @php $runningBalance += $transaction->amount; @endphp
                                    <tr
                                        class="ledger-row"
                                        data-name="{{ strtolower($transaction->name) }}"
                                        data-reference="{{ strtolower($transaction->reference) }}"
                                        data-mode="{{ strtolower($transaction->mode) }}"
                                        data-date="{{ $transaction->created_at->format('Y-m-d') }}"
                                    >
                                        <td>
                                            <div class="ledger-entry-title">{{ $transaction->name }}</div>
                                            <div class="ledger-entry-sub">Recorded for {{ $vendor->name }}</div>
                                        </td>
                                        <td>{{ $transaction->reference }}</td>
                                        <td>{{ $transaction->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $transaction->amount >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $transaction->mode }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="{{ $transaction->amount >= 0 ? 'ledger-amount-credit' : 'ledger-amount-debit' }}">
                                                {{ $transaction->amount >= 0 ? '+' : '-' }}₦{{ number_format(abs($transaction->amount), 2) }}
                                            </span>
                                        </td>
                                        <td>₦{{ number_format($runningBalance, 2) }}</td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editTransactionModal{{ $transaction->id }}">
                                                            <i class="fa-regular fa-pen-to-square me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('vendors.transactions.destroy', ['id' => $vendor->id, 'transactionId' => $transaction->id]) }}" method="POST" onsubmit="return confirm('Delete this transaction permanently?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa-regular fa-trash-can me-2"></i>Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="ledgerEmptyState">
                                        <td colspan="7">
                                            <div class="vendor-ledger-empty">
                                                <i class="fa-regular fa-folder-open d-block"></i>
                                                <div class="fw-semibold mb-1">No transactions found yet</div>
                                                <div class="mb-3">Start the ledger by adding the first transaction for this vendor.</div>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                                    <i class="fa-solid fa-plus me-2"></i>Add First Transaction
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="modal fade" id="vendorProfileModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Vendor Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('vendors.profile.update', ['id' => $vendor->id]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor Image</label>
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                        <div class="mt-3">
                                            <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}" class="img-fluid rounded-4 border" style="max-height: 180px; object-fit: cover;">
                                        </div>
                                        <div class="form-text">Upload a clear logo or profile image for this vendor.</div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" rows="4" class="form-control">{{ old('address', $vendor->address) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Ledger Transaction</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('vendors.transactions.store', ['id' => $vendor->id]) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Transaction Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Invoice Payment, Adjustment, Refund" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reference</label>
                                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="REF-001" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mode</label>
                                        <select class="form-select" name="mode" required>
                                            <option value="">Select mode</option>
                                            <option value="Cash" @selected(old('mode') === 'Cash')>Cash</option>
                                            <option value="Bank Transfer" @selected(old('mode') === 'Bank Transfer')>Bank Transfer</option>
                                            <option value="Card" @selected(old('mode') === 'Card')>Card</option>
                                            <option value="Cheque" @selected(old('mode') === 'Cheque')>Cheque</option>
                                            <option value="System" @selected(old('mode') === 'System')>System</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Amount</label>
                                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="Positive for credit, negative for debit" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Transaction</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @foreach ($transactions as $transaction)
                <div class="modal fade" id="editTransactionModal{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Transaction</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('vendors.transactions.update', ['id' => $vendor->id, 'transactionId' => $transaction->id]) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Transaction Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $transaction->name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Reference</label>
                                            <input type="text" name="reference" class="form-control" value="{{ $transaction->reference }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mode</label>
                                            <select class="form-select" name="mode" required>
                                                @foreach (['Cash', 'Bank Transfer', 'Card', 'Cheque', 'System'] as $mode)
                                                    <option value="{{ $mode }}" @selected($transaction->mode === $mode)>{{ $mode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Amount</label>
                                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ $transaction->amount }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Transaction</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const filterPanel = document.getElementById('ledgerFilterPanel');
                    const toggleBtn = document.getElementById('toggleLedgerFilters');
                    const searchInput = document.getElementById('ledgerSearch');
                    const modeInput = document.getElementById('ledgerMode');
                    const dateInput = document.getElementById('ledgerDate');
                    const resetBtn = document.getElementById('resetLedgerFilters');
                    const rows = Array.from(document.querySelectorAll('.ledger-row'));
                    const exportBtn = document.getElementById('exportLedgerCsv');

                    function applyFilters() {
                        const search = (searchInput?.value || '').trim().toLowerCase();
                        const mode = (modeInput?.value || '').trim().toLowerCase();
                        const date = (dateInput?.value || '').trim();

                        rows.forEach((row) => {
                            const matchesSearch =
                                !search ||
                                row.dataset.name.includes(search) ||
                                row.dataset.reference.includes(search) ||
                                row.dataset.mode.includes(search);
                            const matchesMode = !mode || row.dataset.mode === mode;
                            const matchesDate = !date || row.dataset.date === date;
                            row.style.display = matchesSearch && matchesMode && matchesDate ? '' : 'none';
                        });
                    }

                    toggleBtn?.addEventListener('click', function () {
                        filterPanel?.classList.toggle('show');
                    });

                    [searchInput, modeInput, dateInput].forEach((input) => {
                        input?.addEventListener('input', applyFilters);
                        input?.addEventListener('change', applyFilters);
                    });

                    resetBtn?.addEventListener('click', function () {
                        if (searchInput) searchInput.value = '';
                        if (modeInput) modeInput.value = '';
                        if (dateInput) dateInput.value = '';
                        applyFilters();
                    });

                    exportBtn?.addEventListener('click', function () {
                        const visibleRows = rows.filter((row) => row.style.display !== 'none');
                        let csv = 'Entry,Reference,Date,Mode,Amount,Running Balance\n';

                        visibleRows.forEach((row) => {
                            const cols = Array.from(row.querySelectorAll('td')).slice(0, 6).map((cell) => {
                                return '"' + cell.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
                            });
                            csv += cols.join(',') + '\n';
                        });

                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'vendor-ledger-{{ $vendor->id }}.csv';
                        link.click();
                        URL.revokeObjectURL(link.href);
                    });
                });
            </script>
        @else
            <div class="alert alert-info border-0 shadow-sm">
                Select a specific vendor from the vendor list to view a detailed ledger workspace.
            </div>
        @endisset
    </div>
</div>
@endsection
