<?php $page = 'bank-statement-imports'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    .statement-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .statement-shell {
        display: grid;
        gap: 20px;
    }

    .statement-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }

    .statement-card .card-body {
        padding: 18px;
    }

    .statement-settings-card {
        position: sticky;
        top: 92px;
    }

    .statement-layout {
        display: grid;
        grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.6fr);
        gap: 20px;
    }

    .statement-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .statement-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .statement-summary-box,
    .statement-import-box,
    .statement-line-box {
        border: 1px solid #dbe7ff;
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
    }

    .statement-summary-box small,
    .statement-line-meta small {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
    }

    .statement-summary-box strong {
        font-size: 1.15rem;
        color: #0f172a;
    }

    .statement-import-list,
    .statement-line-list {
        display: grid;
        gap: 12px;
    }

    .statement-import-box.active {
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.08);
    }

    .statement-line-top {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .statement-line-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .statement-suggestion-list {
        display: grid;
        gap: 8px;
    }

    .statement-suggestion {
        border: 1px solid #dbe7ff;
        border-radius: 12px;
        padding: 10px 12px;
        background: #f8fbff;
    }

    .statement-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        color: #64748b;
        background: #fff;
    }

    @media (max-width: 1199px) {
        .statement-settings-card {
            position: static;
        }

        .statement-layout,
        .statement-filter-grid,
        .statement-summary-grid,
        .statement-line-meta {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .statement-page-header,
        .statement-page-header > *,
        .statement-page-header .btn,
        .statement-line-top > *,
        .statement-line-top .text-end,
        .statement-suggestion form {
            width: 100%;
        }

        .statement-card .card-body,
        .statement-summary-box,
        .statement-import-box,
        .statement-line-box {
            padding: 14px;
        }

        .statement-suggestion form {
            min-width: 0 !important;
        }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card statement-settings-card">
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
                <div class="content-page-header statement-page-header mb-3">
                    <div>
                        <h4 class="mb-1">Statement Imports</h4>
                        <p class="text-muted mb-0">Review imported bank lines, inspect mapped ledger activity, and mark confirmed matches inside the current tenant and branch workspace.</p>
                    </div>
                    <a href="{{ route('bank-reconciliation') }}" class="btn btn-outline-primary">Back to Reconciliation</a>
                </div>

                <div class="statement-shell">
                    <div class="card statement-card">
                        <div class="card-body">
                            <form method="GET" action="{{ route('bank-statement-imports') }}" class="statement-filter-grid">
                                <div>
                                    <label class="form-label">Bank</label>
                                    <select name="bank_id" class="form-select">
                                        <option value="">All banks</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}" @selected((string) request('bank_id') === (string) $bank->id)>{{ $bank->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Imported From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div>
                                    <label class="form-label">Imported To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if($selectedImport)
                        <div class="card statement-card">
                            <div class="card-body">
                                <div class="statement-summary-grid">
                                    <div class="statement-summary-box">
                                        <small>Selected Bank</small>
                                        <strong>{{ optional($selectedBank)->name ?: 'Bank Account' }}</strong>
                                    </div>
                                    <div class="statement-summary-box">
                                        <small>Ledger Match</small>
                                        <strong>{{ $selectedAccount ? $selectedAccount->code . ' - ' . $selectedAccount->name : 'Not mapped' }}</strong>
                                    </div>
                                    <div class="statement-summary-box">
                                        <small>Matched Lines</small>
                                        <strong>{{ number_format((int) $statusSummary['matched']) }}</strong>
                                    </div>
                                    <div class="statement-summary-box">
                                        <small>Unmatched Lines</small>
                                        <strong>{{ number_format((int) $statusSummary['unmatched']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="statement-layout">
                        <div class="card statement-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1">Imported Batches</h5>
                                        <p class="text-muted mb-0">Choose a batch to review its lines.</p>
                                    </div>
                                </div>

                                <div class="statement-import-list">
                                    @forelse($imports as $import)
                                        <a href="{{ route('bank-statement-imports', array_filter(array_merge(request()->query(), ['import_id' => $import->id]))) }}" class="text-decoration-none">
                                            <div class="statement-import-box {{ (int) optional($selectedImport)->id === (int) $import->id ? 'active' : '' }}">
                                                <div class="fw-semibold text-dark">{{ optional($import->bank)->name ?: 'Bank Account' }}</div>
                                                <div class="small text-muted">{{ $import->source_file_name }}</div>
                                                <div class="small text-muted mt-1">
                                                    {{ $import->created_at ? $import->created_at->format('d M Y H:i') : '' }}
                                                    • {{ number_format((int) $import->lines_count) }} lines
                                                </div>
                                                <div class="small text-dark mt-1">
                                                    Period:
                                                    {{ $import->statement_date_from ? $import->statement_date_from->format('d M Y') : 'N/A' }}
                                                    to
                                                    {{ $import->statement_date_to ? $import->statement_date_to->format('d M Y') : 'N/A' }}
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="statement-empty">No statement imports found yet in this workspace.</div>
                                    @endforelse
                                </div>

                                @if(method_exists($imports, 'links'))
                                    <div class="mt-3">{{ $imports->links() }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="card statement-card">
                            <div class="card-body">
                                @if($selectedImport)
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div>
                                            <h5 class="mb-1">Imported Lines</h5>
                                            <p class="text-muted mb-0">{{ $selectedImport->source_file_name }}{{ $selectedAccount ? ' mapped to ' . $selectedAccount->name : ' has no mapped ledger account yet' }}</p>
                                        </div>
                                        <form method="GET" action="{{ route('bank-statement-imports') }}" class="d-flex gap-2 flex-wrap">
                                            @foreach(request()->except('status') as $queryKey => $queryValue)
                                                @if(is_array($queryValue))
                                                    @foreach($queryValue as $nestedValue)
                                                        <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                                @endif
                                            @endforeach
                                            <select name="status" class="form-select">
                                                <option value="">All statuses</option>
                                                <option value="matched" @selected(request('status') === 'matched')>Matched</option>
                                                <option value="unmatched" @selected(request('status') === 'unmatched')>Unmatched</option>
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                                        </form>
                                    </div>

                                    <div class="statement-line-list">
                                        @forelse($selectedLines as $line)
                                            <div class="statement-line-box">
                                                <div class="statement-line-top">
                                                    <div>
                                                        <div class="fw-semibold">{{ $line->description ?: 'Statement line' }}</div>
                                                        <div class="small text-muted">{{ $line->reference ?: 'No reference' }}</div>
                                                    </div>
                                                    <div class="text-end">
                                                        @if($line->status === 'matched')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Matched</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Unmatched</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="statement-line-meta">
                                                    <div>
                                                        <small>Date</small>
                                                        <div>{{ $line->line_date ? $line->line_date->format('d M Y') : 'N/A' }}</div>
                                                    </div>
                                                    <div>
                                                        <small>Amount</small>
                                                        <div>{{ number_format((float) $line->amount, 2) }}</div>
                                                    </div>
                                                    <div>
                                                        <small>Running Balance</small>
                                                        <div>{{ $line->balance !== null ? number_format((float) $line->balance, 2) : 'N/A' }}</div>
                                                    </div>
                                                    <div>
                                                        <small>Matched Ledger Ref</small>
                                                        <div>{{ optional($line->matchedTransaction)->reference ?: 'Not matched' }}</div>
                                                    </div>
                                                </div>

                                                @if($line->matchedTransaction)
                                                    <div class="statement-suggestion mb-2">
                                                        <div class="fw-semibold text-success">Current Match</div>
                                                        <div class="small text-muted">
                                                            {{ optional($line->matchedTransaction->account)->name ?: 'Ledger Account' }}
                                                            • {{ $line->matchedTransaction->transaction_date ? \Illuminate\Support\Carbon::parse($line->matchedTransaction->transaction_date)->format('d M Y') : 'N/A' }}
                                                            • Ref: {{ $line->matchedTransaction->reference ?: 'N/A' }}
                                                        </div>
                                                        <div class="small text-dark mt-1">{{ $line->matchedTransaction->description ?: 'No description' }}</div>
                                                        <form method="POST" action="{{ route('settings.bank-statement-lines.unmatch', $line) }}" class="mt-2">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Unmatch Line</button>
                                                        </form>
                                                    </div>
                                                @elseif($selectedAccount && $line->suggestedTransactions->isNotEmpty())
                                                    <div class="statement-suggestion-list">
                                                        @foreach($line->suggestedTransactions as $candidate)
                                                            <div class="statement-suggestion">
                                                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                    <div>
                                                                        <div class="fw-semibold">{{ optional($candidate['transaction']->account)->name ?: 'Ledger Account' }}</div>
                                                                        <div class="small text-muted">
                                                                            {{ \Illuminate\Support\Carbon::parse($candidate['transaction']->transaction_date)->format('d M Y') }}
                                                                            • Ref: {{ $candidate['transaction']->reference ?: 'N/A' }}
                                                                            • Score: {{ $candidate['score'] }}
                                                                        </div>
                                                                        <div class="small text-dark mt-1">{{ $candidate['transaction']->description ?: 'No description' }}</div>
                                                                    </div>
                                                                    <form method="POST" action="{{ route('settings.bank-statement-lines.match', $line) }}" class="d-grid gap-2" style="min-width: 220px;">
                                                                        @csrf
                                                                        <input type="hidden" name="transaction_id" value="{{ $candidate['transaction']->id }}">
                                                                        <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Optional note">
                                                                        <button type="submit" class="btn btn-sm btn-primary">Match to This Entry</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif(!$selectedAccount)
                                                    <div class="statement-empty">No mapped bank ledger account was found for this bank yet. Create or rename the related bank account in chart of accounts first.</div>
                                                @else
                                                    <div class="statement-empty">No close ledger candidates were found for this line yet.</div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="statement-empty">No lines found for the selected filters.</div>
                                        @endforelse
                                    </div>

                                    @if(method_exists($selectedLines, 'links'))
                                        <div class="mt-3">{{ $selectedLines->links() }}</div>
                                    @endif
                                @else
                                    <div class="statement-empty">Choose an imported batch from the left to begin reviewing statement lines.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
