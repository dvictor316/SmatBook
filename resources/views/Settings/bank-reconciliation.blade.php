<?php $page = 'bank-reconciliation'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    .recon-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .recon-shell {
        display: grid;
        gap: 20px;
    }

    .recon-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }

    .recon-card .card-body {
        padding: 18px;
    }

    .recon-settings-card {
        position: sticky;
        top: 92px;
    }

    .recon-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .recon-summary-tile {
        padding: 14px;
        border-radius: 16px;
        border: 1px solid #dbe7ff;
        background: #fff;
    }

    .recon-summary-tile small {
        display: block;
        margin-bottom: 8px;
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .recon-summary-tile strong {
        font-size: 1.25rem;
        line-height: 1;
        color: #0f172a;
    }

    .recon-bank-row {
        border: 1px solid #e6eefc;
        border-radius: 16px;
        background: #fff;
        padding: 14px;
        margin-bottom: 16px;
    }

    .recon-bank-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .recon-bank-name {
        font-size: 0.92rem;
        font-weight: 800;
        color: #0f172a;
    }

    .recon-balance-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .recon-balance-chip {
        padding: 12px;
        border-radius: 14px;
        background: #f8fbff;
        border: 1px solid #dbe7ff;
    }

    .recon-balance-chip small {
        display: block;
        margin-bottom: 6px;
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
    }

    .recon-balance-chip strong {
        font-size: 0.88rem;
        color: #0f172a;
    }

    .content-page-header h4,
    .content-page-header h5 {
        font-size: 1.05rem;
    }

    .content-page-header p {
        font-size: 0.85rem;
    }

    .recon-good { color: #166534 !important; }
    .recon-warn { color: #b45309 !important; }
    .recon-bad { color: #b91c1c !important; }

    .recon-adjust-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(180px, 0.4fr) auto;
        gap: 12px;
        align-items: end;
    }

    .recon-import-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr);
        gap: 18px;
    }

    .recon-import-list {
        display: grid;
        gap: 12px;
    }

    .recon-import-item {
        border: 1px solid #dbe7ff;
        background: #fff;
        border-radius: 14px;
        padding: 12px 14px;
    }

    @media (max-width: 991px) {
        .recon-settings-card {
            position: static;
        }

        .recon-summary-grid,
        .recon-balance-grid,
        .recon-adjust-form,
        .recon-import-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .recon-page-header,
        .recon-page-header > *,
        .recon-page-header .badge,
        .recon-adjust-form .btn,
        .recon-import-item .btn {
            width: 100%;
        }

        .recon-bank-row,
        .recon-card .card-body {
            padding: 14px;
        }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card recon-settings-card">
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
                <div class="content-page-header recon-page-header mb-3">
                    <div>
                        <h4 class="mb-1">Bank Reconciliation</h4>
                        <p class="text-muted mb-0">Compare bank balances against ledger balances and post balancing adjustments when required.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">Accounting Control Workspace</span>
                </div>

                <div class="recon-shell">
                    <div class="card recon-card">
                        <div class="card-body">
                            <div class="recon-summary-grid">
                                <div class="recon-summary-tile">
                                    <small>Bank Accounts</small>
                                    <strong>{{ $summary['bank_count'] }}</strong>
                                </div>
                                <div class="recon-summary-tile">
                                    <small>Matched Ledgers</small>
                                    <strong>{{ $summary['matched_count'] }}</strong>
                                </div>
                                <div class="recon-summary-tile">
                                    <small>Needs Attention</small>
                                    <strong class="{{ $summary['mismatch_count'] > 0 ? 'recon-warn' : 'recon-good' }}">{{ $summary['mismatch_count'] }}</strong>
                                </div>
                                <div class="recon-summary-tile">
                                    <small>Net Difference</small>
                                    <strong class="{{ abs($summary['difference_total']) > 0.009 ? 'recon-bad' : 'recon-good' }}">
                                        {{ number_format((float) $summary['difference_total'], 2) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card recon-card">
                        <div class="card-body">
                            <div class="recon-import-grid">
                                <div>
                                    <h5 class="mb-1">Import Bank Statement</h5>
                                    <p class="text-muted mb-3">Upload a CSV statement into the current tenant and branch workspace so we can build full reconciliation on top of real bank lines.</p>
                                    <form method="POST" action="{{ route('settings.bank-reconciliation.import') }}" enctype="multipart/form-data" class="row g-3">
                                        @csrf
                                        <div class="col-md-6">
                                            <label class="form-label">Bank Account</label>
                                            <select name="bank_id" class="form-select" required>
                                                <option value="">Select bank</option>
                                                @foreach($banks as $bank)
                                                    <option value="{{ $bank->id }}">{{ $bank->name }}{{ $bank->account_number ? ' - ' . $bank->account_number : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Currency</label>
                                            <input type="text" name="currency" class="form-control" placeholder="NGN">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Statement File</label>
                                            <input type="file" name="statement_file" class="form-control" accept=".csv,.txt" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Optional import note">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Import Statement CSV</button>
                                        </div>
                                    </form>
                                </div>
                                <div>
                                    <h5 class="mb-1">Recent Imports</h5>
                                    <p class="text-muted mb-3">Latest statement batches within your current workspace.</p>
                                    <div class="recon-import-list">
                                        @forelse($recentImports as $import)
                                            <div class="recon-import-item">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div class="fw-semibold">{{ optional($import->bank)->name ?: 'Bank Account' }}</div>
                                                    <a href="{{ route('bank-statement-imports', ['import_id' => $import->id]) }}" class="btn btn-sm btn-outline-primary">Review</a>
                                                </div>
                                                <div class="small text-muted mb-2">{{ $import->source_file_name }}</div>
                                                <div class="small">
                                                    {{ number_format((float) $import->line_count) }} lines
                                                    @if($import->statement_date_from || $import->statement_date_to)
                                                        •
                                                        {{ $import->statement_date_from ? $import->statement_date_from->format('d M Y') : 'N/A' }}
                                                        to
                                                        {{ $import->statement_date_to ? $import->statement_date_to->format('d M Y') : 'N/A' }}
                                                    @endif
                                                </div>
                                                <div class="small text-muted">
                                                    Closing balance:
                                                    <strong>{{ $import->closing_balance !== null ? number_format((float) $import->closing_balance, 2) : 'N/A' }}</strong>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">No statement imports yet for this workspace.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card recon-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">Bank Match Review</h5>
                                    <p class="text-muted mb-0">Each row compares the bank balance saved in bank settings against the current ledger balance of the mapped account.</p>
                                </div>
                            </div>

                            @forelse($reconciliations as $item)
                                <div class="recon-bank-row">
                                    <div class="recon-bank-header">
                                        <div>
                                            <div class="recon-bank-name">{{ $item['bank']->name ?: 'Bank Account' }}</div>
                                            <div class="text-muted small">
                                                {{ $item['bank']->account_number ?: 'No account number' }}
                                                @if($item['bank']->branch)
                                                    • {{ $item['bank']->branch }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            @if($item['is_balanced'])
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Balanced</span>
                                            @elseif(!$item['is_matched'])
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Ledger Match Needed</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Out of Balance</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="recon-balance-grid">
                                        <div class="recon-balance-chip">
                                            <small>Mapped Ledger</small>
                                            <strong>{{ optional($item['account'])->code ?: 'Unmatched' }}{{ $item['account'] ? ' - ' . $item['account']->name : '' }}</strong>
                                        </div>
                                        <div class="recon-balance-chip">
                                            <small>Bank Balance</small>
                                            <strong>{{ number_format((float) $item['bank_balance'], 2) }}</strong>
                                        </div>
                                        <div class="recon-balance-chip">
                                            <small>Book Balance</small>
                                            <strong>{{ number_format((float) $item['book_balance'], 2) }}</strong>
                                        </div>
                                        <div class="recon-balance-chip">
                                            <small>Difference</small>
                                            <strong class="{{ abs($item['difference']) < 0.01 ? 'recon-good' : 'recon-bad' }}">
                                                {{ number_format((float) $item['difference'], 2) }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="text-muted small">
                                            Last ledger activity:
                                            <strong>
                                                {{ $item['last_transaction_date'] ? \Illuminate\Support\Carbon::parse($item['last_transaction_date'])->format('d M Y') : 'No transactions yet' }}
                                            </strong>
                                        </div>

                                        @if($item['account'] && !$item['is_balanced'])
                                            <form method="POST" action="{{ route('settings.bank-reconciliation.adjustment') }}" class="recon-adjust-form">
                                                @csrf
                                                <input type="hidden" name="bank_id" value="{{ $item['bank']->id }}">
                                                <input type="hidden" name="account_id" value="{{ $item['account']->id }}">
                                                <input type="hidden" name="difference" value="{{ $item['difference'] }}">
                                                <div>
                                                    <label class="form-label mb-1">Adjustment memo</label>
                                                    <input type="text" name="memo" class="form-control" value="Reconciliation adjustment for {{ $item['bank']->name }}" placeholder="Optional note">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1">Date</label>
                                                    <input type="date" name="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                                </div>
                                                <div>
                                                    <button type="submit" class="btn btn-primary w-100">Post Adjustment</button>
                                                </div>
                                            </form>
                                        @elseif(!$item['account'])
                                            <div class="small text-warning">Create or rename a bank ledger account to match this bank before posting adjustments.</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">
                                    No bank accounts found yet. Add at least one bank account to begin reconciliation.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
