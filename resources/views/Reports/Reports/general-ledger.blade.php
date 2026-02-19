<?php $page = 'general-ledger'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-1">General Ledger</h4>
                    <p class="text-muted mb-0 small">Detailed debit/credit journal by account and period.</p>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('trial-balance') }}" class="btn btn-outline-secondary btn-sm">Trial Balance</a>
                    <a href="{{ route('balance-sheet') }}" class="btn btn-outline-secondary btn-sm">Balance Sheet</a>
                </div>
            </div>
        </div>

        @if(!empty($message))
            <div class="alert alert-warning">{{ $message }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Account</label>
                        <select name="account_id" class="form-select">
                            <option value="">All Accounts</option>
                            @foreach(($accounts ?? collect()) as $account)
                                <option value="{{ $account->id }}" @selected((string)($selectedAccountId ?? '') === (string)$account->id)>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Reference/Description" value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-primary btn-sm text-white">Apply Filter</button>
                        <a href="{{ route('general-ledger') }}" class="btn btn-light btn-sm border">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Total Debit</div><div class="h5 mb-0">₦{{ number_format((float)($totals['debit'] ?? 0), 2) }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Total Credit</div><div class="h5 mb-0">₦{{ number_format((float)($totals['credit'] ?? 0), 2) }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Difference</div><div class="h5 mb-0">₦{{ number_format(abs((float)($totals['debit'] ?? 0) - (float)($totals['credit'] ?? 0)), 2) }}</div></div></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($entries ?? collect()) as $entry)
                            <tr>
                                <td>{{ optional($entry->transaction_date)->format('Y-m-d') }}</td>
                                <td>{{ $entry->reference ?? '-' }}</td>
                                <td>{{ optional($entry->account)->code }} {{ optional($entry->account)->name }}</td>
                                <td>{{ $entry->description ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$entry->debit, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$entry->credit, 2) }}</td>
                                <td>{{ $entry->transaction_type ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No ledger entries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(is_object($entries ?? null) && method_exists($entries, 'links'))
                <div class="card-footer bg-white">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
