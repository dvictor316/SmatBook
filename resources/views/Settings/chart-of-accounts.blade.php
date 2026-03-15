<?php $page = 'chart-of-accounts'; ?>
@extends('layout.mainlayout')
@section('content')

@php
    $currency = \App\Support\GeoCurrency::currentCurrency();
@endphp

<style>
    .coa-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .coa-summary-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        padding: 18px;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.06);
    }

    .coa-summary-card small {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 8px;
    }

    .coa-summary-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }

    .coa-summary-meta {
        margin-top: 8px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .coa-block {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .coa-block-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #ebf1ff;
        background: linear-gradient(90deg, #f8fbff 0%, #ffffff 100%);
    }

    .coa-block-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }

    .coa-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        background: #eef4ff;
        color: #315efb;
    }

    .coa-add-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }

    .coa-empty {
        padding: 26px;
        text-align: center;
        color: #64748b;
    }

    .badge-soft-success {
        background: #dcfce7;
        color: #166534;
    }

    .badge-soft-secondary {
        background: #e5e7eb;
        color: #475569;
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4">
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
                <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">Chart of Accounts</h4>
                        <p class="text-muted mb-0">Manage the account structure behind your ledger, reports, and financial statements.</p>
                    </div>
                    <span class="coa-chip"><i class="fe fe-book-open"></i> Core Accounting Control</span>
                </div>

                <div class="coa-summary-grid">
                    @foreach($accountSummary as $summary)
                        <div class="coa-summary-card">
                            <small>{{ $summary['type'] }}</small>
                            <div class="coa-summary-value">{{ number_format($summary['balance'], 2) }}</div>
                            <div class="coa-summary-meta">{{ $summary['count'] }} account(s)</div>
                        </div>
                    @endforeach
                </div>

                <div class="card coa-add-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="mb-1">Add Account</h5>
                                <p class="text-muted mb-0">Create a new ledger account for assets, liabilities, equity, revenue, or expenses.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('settings.chart-of-accounts.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="1000" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Account Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Cash at Bank" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="">Select type</option>
                                        @foreach([\App\Models\Account::TYPE_ASSET, \App\Models\Account::TYPE_LIABILITY, \App\Models\Account::TYPE_EQUITY, \App\Models\Account::TYPE_REVENUE, \App\Models\Account::TYPE_EXPENSE] as $type)
                                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sub Type</label>
                                    <input type="text" name="sub_type" class="form-control" value="{{ old('sub_type') }}" placeholder="Current Asset">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Opening Balance ({{ $currency }})</label>
                                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="3" class="form-control" placeholder="Optional internal note for the account purpose">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">Add Account</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @forelse($accountGroups as $type => $group)
                    <div class="coa-block">
                        <div class="coa-block-head">
                            <h5 class="coa-block-title">{{ $type }}</h5>
                            <span class="coa-chip">{{ $group->count() }} account(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Sub Type</th>
                                        <th>Opening Balance</th>
                                        <th>Current Balance</th>
                                        <th>Transactions</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group as $account)
                                        <tr>
                                            <td><strong>{{ $account->code }}</strong></td>
                                            <td>
                                                <div class="fw-semibold">{{ $account->name }}</div>
                                                @if(!empty($account->description))
                                                    <small class="text-muted">{{ $account->description }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $account->sub_type ?: 'General' }}</td>
                                            <td>{{ number_format((float) ($account->opening_balance ?? 0), 2) }}</td>
                                            <td>{{ number_format((float) ($account->current_balance ?? 0), 2) }}</td>
                                            <td>{{ $account->transactions_count ?? 0 }}</td>
                                            <td>
                                                @if($account->is_active)
                                                    <span class="badge badge-soft-success">Active</span>
                                                @else
                                                    <span class="badge badge-soft-secondary">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="coa-block">
                        <div class="coa-empty">
                            No accounts have been created yet. Start with your core cash, receivable, payable, revenue, and expense accounts.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
