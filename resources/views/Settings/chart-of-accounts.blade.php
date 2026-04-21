<?php $page = 'chart-of-accounts'; ?>
@extends('layout.mainlayout')
@section('content')

@php
    $currency = \App\Support\GeoCurrency::currentCurrency();

    $typeColors = [
        'Asset'     => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'dot' => '#0ea5e9'],
        'Liability' => ['bg' => '#fef3c7', 'text' => '#92400e', 'dot' => '#f59e0b'],
        'Equity'    => ['bg' => '#f3e8ff', 'text' => '#6b21a8', 'dot' => '#a855f7'],
        'Revenue'   => ['bg' => '#dcfce7', 'text' => '#166534', 'dot' => '#22c55e'],
        'Expense'   => ['bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];
@endphp

<style>
    /* ── Layout ───────────────────────────────────────────── */
    .coa-wrap { display: flex; gap: 20px; align-items: flex-start; }
    .coa-left  { flex: 1 1 0; min-width: 0; }
    .coa-right { width: 340px; flex-shrink: 0; position: sticky; top: 80px; }

    @media (max-width: 991px) {
        .coa-wrap  { flex-direction: column-reverse; }
        .coa-right { width: 100%; position: static; margin-bottom: 18px; }
        .coa-left { width: 100%; }
    }

    @media (max-width: 767px) {
        .coa-stats {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .coa-stat {
            padding: 12px 10px;
            font-size: 1rem;
        }
        .coa-toolbar {
            flex-direction: column;
            gap: 8px;
            padding: 8px 6px;
        }
        .coa-block-head, .coa-panel-head {
            flex-direction: column;
            align-items: flex-start;
            padding: 10px 8px;
        }
        .coa-block-title { font-size: 1rem; }
        .coa-type-chip, .coa-count-badge { font-size: 0.8rem; padding: 4px 10px; }
        .coa-panel-body { padding: 12px 8px; }
        .coa-input, .coa-search-input, .coa-filter-select { font-size: 1rem; padding: 10px 12px; }
        .coa-submit-btn { font-size: 1rem; padding: 12px; }
        .coa-table thead { display: none; }
        .coa-table, .coa-table tbody, .coa-table tr, .coa-table td { display: block; width: 100%; }
        .coa-table tr { margin-bottom: 12px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); background: #fff; }
        .coa-table td { padding: 8px 10px; border: none; border-bottom: 1px solid #f1f5f9; font-size: 0.98rem; }
        .coa-table td:last-child { border-bottom: none; }
        .coa-code, .coa-subtype, .coa-balance, .coa-txn { font-size: 0.95rem; }
    }

    @media (max-width: 480px) {
        .coa-stats { gap: 6px; }
        .coa-stat { font-size: 1.1rem; padding: 10px 6px; }
        .coa-panel, .coa-block { border-radius: 8px; }
        .coa-panel-body, .coa-block-head { padding: 8px 4px; }
        .coa-input, .coa-search-input, .coa-filter-select { font-size: 1.08rem; padding: 12px 8px; }
        .coa-submit-btn { font-size: 1.08rem; padding: 14px; }
    }


    /* ── Stat cards ───────────────────────────────────────── */
    .coa-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .coa-stat {
        border-radius: 14px;
        border: 1px solid #e8edf5;
        background: #fff;
        padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
    }
    .coa-stat-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #94a3b8;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .coa-stat-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .coa-stat-value {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .coa-stat-sub {
        font-size: 0.74rem;
        color: #94a3b8;
        margin-top: 3px;
    }

    /* ── Search / filter bar ──────────────────────────────── */
    .coa-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 14px;
        padding: 10px 14px;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
    }
    .coa-search-wrap {
        flex: 1 1 200px;
        position: relative;
    }
    .coa-search-wrap i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
    }
    .coa-search-input {
        width: 100%;
        padding: 7px 10px 7px 32px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        font-size: 0.82rem;
        outline: none;
        background: #f8fafc;
        color: #0f172a;
        transition: border-color .15s;
    }
    .coa-search-input:focus { border-color: #6366f1; background: #fff; }
    .coa-filter-select {
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        font-size: 0.82rem;
        outline: none;
        background: #f8fafc;
        color: #0f172a;
        cursor: pointer;
        transition: border-color .15s;
    }
    .coa-filter-select:focus { border-color: #6366f1; }

    /* ── Account group block ──────────────────────────────── */
    .coa-block {
        border: 1px solid #e8edf5;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(15,23,42,.04);
        overflow: hidden;
        margin-bottom: 14px;
    }
    .coa-block-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .coa-block-left { display: flex; align-items: center; gap: 10px; }
    .coa-type-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .coa-block-title {
        margin: 0;
        font-size: 0.88rem;
        font-weight: 800;
        color: #0f172a;
    }
    .coa-type-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .coa-count-badge {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
    }

    /* ── Table ────────────────────────────────────────────── */
    .coa-table { font-size: 0.82rem; margin: 0; }
    .coa-table thead tr { background: #f8fafc; }
    .coa-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        padding: 9px 14px;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .coa-table tbody td {
        padding: 10px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
        color: #64748b;
        font-weight: 400;
        font-size: 0.82rem;
    }
    .coa-table tbody tr:last-child td { border-bottom: none; }
    .coa-table tbody tr:hover td { background: #fafbff; }

    .coa-code {
        font-family: 'Courier New', monospace;
        font-size: 0.78rem;
        font-weight: 400;
        color: #64748b;
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .coa-acc-name { font-weight: 500; color: #475569; font-size: 0.82rem; }
    .coa-acc-desc { font-size: 0.73rem; color: #94a3b8; margin-top: 2px; }

    .coa-subtype {
        font-size: 0.73rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .coa-balance { font-weight: 400; font-size: 0.82rem; color: #64748b; white-space: nowrap; }
    .coa-txn {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6366f1;
        background: #eef2ff;
        padding: 2px 8px;
        border-radius: 999px;
        display: inline-block;
    }
    .badge-active {
        background: #dcfce7; color: #15803d;
        font-size: 0.7rem; font-weight: 700;
        padding: 3px 10px; border-radius: 999px;
    }
    .badge-inactive {
        background: #f1f5f9; color: #64748b;
        font-size: 0.7rem; font-weight: 700;
        padding: 3px 10px; border-radius: 999px;
    }

    /* ── Right panel (Add Account form) ───────────────────── */
    .coa-panel {
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 16px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .coa-panel-head {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        padding: 16px 20px;
    }
    .coa-panel-title {
        font-size: 0.92rem;
        font-weight: 800;
        color: #fff;
        margin: 0 0 2px;
    }
    .coa-panel-sub { font-size: 0.75rem; color: rgba(255,255,255,.75); }
    .coa-panel-body { padding: 18px 20px; }

    .coa-field-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin-bottom: 5px;
        display: block;
    }
    .coa-input {
        width: 100%;
        padding: 8px 11px;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: 0.82rem;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: border-color .15s, background .15s;
    }
    .coa-input:focus { border-color: #6366f1; background: #fff; }
    .coa-input.is-invalid { border-color: #ef4444; }

    .coa-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 14px 0;
    }

    .coa-submit-btn {
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .15s;
    }
    .coa-submit-btn:hover { opacity: .9; }

    /* ── Empty state ──────────────────────────────────────── */
    .coa-empty {
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
    }
    .coa-empty i { font-size: 2rem; margin-bottom: 10px; display: block; }
    .coa-empty p { margin: 0; font-size: 0.82rem; }
    /* ── Row action buttons ───────────────────────────────────── */
    .coa-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border: none;
        border-radius: 7px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: background .15s, color .15s;
        background: #f1f5f9;
        color: #64748b;
        margin-right: 4px;
    }
    .coa-edit-btn:hover   { background: #eef2ff; color: #4f46e5; }
    .coa-delete-btn:hover { background: #fef2f2; color: #dc2626; }

    /* ── Modals ───────────────────────────────────────────────── */
    .coa-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.45);
        z-index: 9990;
        align-items: center;
        justify-content: center;
    }
    .coa-modal-overlay.active { display: flex; }
    .coa-modal {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(15,23,42,.18);
        width: min(94vw, 480px);
        max-height: 92vh;
        overflow-y: auto;
        padding: 0;
    }
    .coa-modal-head {
        padding: 18px 22px 14px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .coa-modal-title { font-size: 0.92rem; font-weight: 800; color: #0f172a; margin: 0; }
    .coa-modal-close {
        background: #f1f5f9; border: none; border-radius: 7px;
        width: 28px; height: 28px; cursor: pointer;
        font-size: 0.9rem; color: #64748b;
        display: flex; align-items: center; justify-content: center;
    }
    .coa-modal-close:hover { background: #fee2e2; color: #dc2626; }
    .coa-modal-body { padding: 18px 22px 22px; }</style>

@php
    // Safe fallback: always have account type options available even if controller didn't pass them
    $formAccountTypes = (isset($accountTypes) && is_array($accountTypes) && count($accountTypes) > 0)
        ? $accountTypes
        : [\App\Models\Account::TYPE_ASSET, \App\Models\Account::TYPE_LIABILITY, \App\Models\Account::TYPE_EQUITY, \App\Models\Account::TYPE_REVENUE, \App\Models\Account::TYPE_EXPENSE];
    $formSubtypeMap = (isset($subtypeOptionsByType) && is_array($subtypeOptionsByType) && count($subtypeOptionsByType) > 0)
        ? $subtypeOptionsByType
        : \App\Models\Account::subtypeOptionsByType();
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">

            {{-- Settings sidebar --}}
            <div class="col-xl-3 col-md-4 mb-3">
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

            {{-- Main content --}}
            <div class="col-xl-9 col-md-8">

                {{-- Page header --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-1" style="font-weight:800;color:#0f172a;">Chart of Accounts</h5>
                        <p class="text-muted mb-0" style="font-size:.82rem;">Manage ledger accounts behind your reports and financial statements.</p>
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#4f46e5;border-radius:999px;padding:6px 14px;font-size:.72rem;font-weight:700;">
                        <i class="fe fe-book-open"></i> Core Accounting
                    </span>
                </div>

                {{-- Stats row --}}
                <div class="coa-stats">
                    @foreach($accountSummary as $summary)
                        @php $tc = $typeColors[$summary['type']] ?? ['bg'=>'#f1f5f9','text'=>'#475569','dot'=>'#94a3b8']; @endphp
                        <div class="coa-stat">
                            <div class="coa-stat-label">
                                <span class="coa-stat-dot" style="background:{{ $tc['dot'] }}"></span>
                                {{ $summary['type'] }}
                            </div>
                            <div class="coa-stat-value">{{ number_format($summary['balance'], 2) }}</div>
                            <div class="coa-stat-sub">{{ $summary['count'] }} account{{ $summary['count'] != 1 ? 's' : '' }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Two-column layout --}}
                <div class="coa-wrap">

                    {{-- LEFT: account list --}}
                    <div class="coa-left">

                        {{-- Toolbar --}}
                        <div class="coa-toolbar">
                            <div class="coa-search-wrap">
                                <i class="fe fe-search"></i>
                                <input type="text" id="coaSearch" class="coa-search-input" placeholder="Search accounts…">
                            </div>
                            <select id="coaTypeFilter" class="coa-filter-select">
                                <option value="">All Types</option>
                                @foreach($formAccountTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                            <select id="coaStatusFilter" class="coa-filter-select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        {{-- Account groups --}}
                        @forelse($accountGroups as $type => $group)
                            @php $tc = $typeColors[$type] ?? ['bg'=>'#f1f5f9','text'=>'#475569','dot'=>'#94a3b8']; @endphp
                            <div class="coa-block" data-type="{{ strtolower($type) }}">
                                <div class="coa-block-head" style="background:{{ $tc['bg'] }}1a;">
                                    <div class="coa-block-left">
                                        <span class="coa-type-dot" style="background:{{ $tc['dot'] }}"></span>
                                        <h5 class="coa-block-title">{{ $type }}</h5>
                                        <span class="coa-type-chip" style="background:{{ $tc['bg'] }};color:{{ $tc['text'] }};">
                                            {{ $type }}
                                        </span>
                                    </div>
                                    <span class="coa-count-badge">{{ $group->count() }} acct{{ $group->count() != 1 ? 's' : '' }}</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table coa-table">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Account Name</th>
                                                <th>Sub Type</th>
                                                <th>Opening Bal.</th>
                                                <th>Current Bal.</th>
                                                <th>Txns</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group as $account)
                                                <tr class="coa-row"
                                                    data-name="{{ strtolower($account->name) }}"
                                                    data-code="{{ strtolower($account->code) }}"
                                                    data-status="{{ $account->is_active ? 'active' : 'inactive' }}">
                                                    <td><span class="coa-code">{{ $account->code }}</span></td>
                                                    <td>
                                                        <div class="coa-acc-name">{{ $account->name }}</div>
                                                        @if(!empty($account->description))
                                                            <div class="coa-acc-desc">{{ $account->description }}</div>
                                                        @endif
                                                    </td>
                                                    <td><span class="coa-subtype">{{ $account->sub_type ?: 'General' }}</span></td>
                                                    <td class="coa-balance">{{ number_format((float)($account->opening_balance ?? 0), 2) }}</td>
                                                    <td class="coa-balance">{{ number_format((float)($account->current_balance ?? 0), 2) }}</td>
                                                    <td><span class="coa-txn">{{ $account->transactions_count ?? 0 }}</span></td>
                                                    <td>
                                                        @if($account->is_active)
                                                            <span class="badge-active">Active</span>
                                                        @else
                                                            <span class="badge-inactive">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td style="white-space:nowrap;">
                                                        <button type="button" class="coa-action-btn coa-edit-btn"
                                                            title="Edit account"
                                                            data-id="{{ $account->id }}"
                                                            data-name="{{ $account->name }}"
                                                            data-sub-type="{{ $account->sub_type }}"
                                                            data-opening="{{ number_format((float)($account->opening_balance ?? 0), 2, '.', '') }}"
                                                            data-description="{{ $account->description }}"
                                                            data-active="{{ $account->is_active ? '1' : '0' }}"
                                                            data-type="{{ $account->type }}"
                                                            data-txns="{{ $account->transactions_count ?? 0 }}">
                                                            <i class="fe fe-edit-2"></i>
                                                        </button>
                                                        <button type="button" class="coa-action-btn coa-delete-btn"
                                                            title="Delete account"
                                                            data-id="{{ $account->id }}"
                                                            data-name="{{ $account->name }}"
                                                            data-active="{{ $account->is_active ? '1' : '0' }}"
                                                            data-txns="{{ $account->transactions_count ?? 0 }}">
                                                            <i class="fe fe-trash-2"></i>
                                                        </button>
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
                                    <i class="fe fe-inbox"></i>
                                    <p>No accounts yet. Add your first account using the form →</p>
                                </div>
                            </div>
                        @endforelse

                    </div>{{-- /.coa-left --}}

                    {{-- RIGHT: Add Account panel --}}
                    <div class="coa-right">
                        <div class="coa-panel">
                            <div class="coa-panel-head">
                                <p class="coa-panel-title"><i class="fe fe-plus-circle me-1"></i> New Account</p>
                                <p class="coa-panel-sub">Add a ledger account to your chart</p>
                            </div>
                            <div class="coa-panel-body">

                                @if($errors->any())
                                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:.78rem;color:#b91c1c;">
                                        <strong>Please fix the errors below:</strong>
                                        <ul style="margin:6px 0 0;padding-left:16px;">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

<script>
window._coaSubtypeMap = @json($formSubtypeMap);
function coaBuildSubtypes(typeValue, selectId, preselect) {
    var sel = document.getElementById(selectId || 'accountSubTypeSelect');
    if (!sel) return;
    var opts = (window._coaSubtypeMap || {})[typeValue] || [];
    sel.innerHTML = '';
    var ph = document.createElement('option');
    ph.value = '';
    ph.textContent = opts.length ? 'Select sub type…' : 'Select type first…';
    sel.appendChild(ph);
    sel.disabled = opts.length === 0;
    opts.forEach(function(v) {
        var o = document.createElement('option');
        o.value = v; o.textContent = v;
        if (preselect && v === preselect) o.selected = true;
        sel.appendChild(o);
    });
}
</script>

                                <form method="POST" action="{{ route('settings.chart-of-accounts.store') }}" id="chartAccountForm">
                                    @csrf

                                    {{-- Row: Code + Type --}}
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                        <div>
                                            <label class="coa-field-label">Code <span style="color:#ef4444">*</span></label>
                                            <input type="text" name="code" class="coa-input {{ $errors->has('code') ? 'is-invalid' : '' }}"
                                                   value="{{ old('code') }}" placeholder="e.g. 1000" required>
                                            @error('code')<small style="color:#ef4444;font-size:.72rem;">{{ $message }}</small>@enderror
                                        </div>
                                        <div>
                                            <label class="coa-field-label">Type <span style="color:#ef4444">*</span></label>
                                            <select name="type" id="accountTypeSelect" class="coa-input {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                                                <option value="">Select…</option>
                                                @foreach($formAccountTypes as $t)
                                                    <option value="{{ $t }}"
                                                        {{ old('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                                @endforeach
                                            </select>
                                            @error('type')<small style="color:#ef4444;font-size:.72rem;">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    {{-- Account Name --}}
                                    <div style="margin-bottom:10px;">
                                        <label class="coa-field-label">Account Name <span style="color:#ef4444">*</span></label>
                                        <input type="text" name="name" class="coa-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                               value="{{ old('name') }}" placeholder="e.g. Cash at Bank" required>
                                        @error('name')<small style="color:#ef4444;font-size:.72rem;">{{ $message }}</small>@enderror
                                    </div>

                                    {{-- Sub Type --}}
                                    <div style="margin-bottom:10px;">
                                        <label class="coa-field-label">Sub Type</label>
                                        <select name="sub_type" id="accountSubTypeSelect" class="coa-input">
                                            <option value="">-- Select sub type --</option>
                                            @foreach($formSubtypeMap as $typeName => $subtypes)
                                                <optgroup label="{{ $typeName }}">
                                                    @foreach($subtypes as $sub)
                                                        <option value="{{ $sub }}" {{ old('sub_type') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('sub_type')<small style="color:#ef4444;font-size:.72rem;">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="coa-divider"></div>

                                    {{-- Opening Balance + Status --}}
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                        <div>
                                            <label class="coa-field-label">Opening Bal. ({{ $currency }})</label>
                                            <input type="number" step="0.01" name="opening_balance"
                                                   class="coa-input" value="{{ old('opening_balance', 0) }}">
                                        </div>
                                        <div>
                                            <label class="coa-field-label">Status</label>
                                            <select name="is_active" class="coa-input">
                                                <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Description --}}
                                    <div style="margin-bottom:14px;">
                                        <label class="coa-field-label">Description <span style="color:#94a3b8;font-weight:400;text-transform:none;">(optional)</span></label>
                                        <textarea name="description" rows="3" class="coa-input"
                                                  placeholder="Internal note on this account's purpose…" style="resize:vertical;">{{ old('description') }}</textarea>
                                    </div>

                                    <button type="submit" class="coa-submit-btn">
                                        <i class="fe fe-check me-1"></i> Add Account
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>{{-- /.coa-right --}}

                </div>{{-- /.coa-wrap --}}
            </div>{{-- /.col-xl-9 --}}
        </div>{{-- /.row --}}
    </div>
{{-- ── Edit Account Modal ─────────────────────────────────── --}}
<div class="coa-modal-overlay" id="coaEditOverlay">
    <div class="coa-modal">
        <div class="coa-modal-head">
            <p class="coa-modal-title"><i class="fe fe-edit-2 me-1"></i> Edit Account</p>
            <button type="button" class="coa-modal-close" id="coaEditClose"><i class="fe fe-x"></i></button>
        </div>
        <div class="coa-modal-body">
            <form method="POST" id="coaEditForm">
                @csrf
                @method('PUT')

                {{-- Account name --}}
                <div style="margin-bottom:12px;">
                    <label class="coa-field-label">Account Name <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" id="editName" class="coa-input" required>
                </div>

                {{-- Sub Type --}}
                <div style="margin-bottom:12px;">
                    <label class="coa-field-label">Sub Type</label>
                    <select name="sub_type" id="editSubType" class="coa-input">
                        <option value="">-- Select sub type --</option>
                        @foreach($formSubtypeMap as $typeName => $subtypes)
                            <optgroup label="{{ $typeName }}">
                                @foreach($subtypes as $sub)
                                    <option value="{{ $sub }}">{{ $sub }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Opening Balance + Status --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                    <div>
                        <label class="coa-field-label">Opening Bal. ({{ $currency }}) <span style="color:#ef4444">*</span></label>
                        <input type="number" step="0.01" name="opening_balance" id="editOpeningBalance" class="coa-input" required>
                    </div>
                    <div>
                        <label class="coa-field-label">Status</label>
                        <select name="is_active" id="editIsActive" class="coa-input">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                {{-- Description --}}
                <div style="margin-bottom:16px;">
                    <label class="coa-field-label">Description <span style="color:#94a3b8;font-weight:400;text-transform:none;">(optional)</span></label>
                    <textarea name="description" id="editDescription" rows="3" class="coa-input" style="resize:vertical;"></textarea>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="coa-submit-btn" style="flex:1;">
                        <i class="fe fe-save me-1"></i> Save Changes
                    </button>
                    <button type="button" id="coaEditCancelBtn"
                        style="flex:0 0 auto;padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-size:.85rem;font-weight:700;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Deactivate Account Modal ──────────────────────────────── --}}
<div class="coa-modal-overlay" id="coaDeactivateOverlay">
    <div class="coa-modal" style="width:min(94vw,400px);">
        <div class="coa-modal-head">
            <p class="coa-modal-title" style="color:#d97706;"><i class="fe fe-slash me-1"></i> Deactivate Account</p>
            <button type="button" class="coa-modal-close" id="coaDeactivateClose"><i class="fe fe-x"></i></button>
        </div>
        <div class="coa-modal-body">
            <p style="font-size:.86rem;color:#475569;margin-bottom:6px;">
                <strong id="deactivateAccountName"></strong> has transactions and cannot be deleted.
            </p>
            <p style="font-size:.82rem;color:#64748b;margin-bottom:18px;">
                Would you like to deactivate it instead? It will be hidden from dropdowns but its history is preserved.
            </p>
            <form method="POST" id="coaDeactivateForm">
                @csrf
                <div style="display:flex;gap:10px;">
                    <button type="submit"
                        style="flex:1;padding:10px;background:linear-gradient(135deg,#d97706,#b45309);color:#fff;border:none;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;">
                        <i class="fe fe-slash me-1"></i> Yes, Deactivate
                    </button>
                    <button type="button" id="coaDeactivateCancelBtn"
                        style="flex:0 0 auto;padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-size:.85rem;font-weight:700;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Delete Account Modal ────────────────────────────────── --}}
<div class="coa-modal-overlay" id="coaDeleteOverlay">
    <div class="coa-modal" style="width:min(94vw,400px);">
        <div class="coa-modal-head">
            <p class="coa-modal-title" style="color:#dc2626;"><i class="fe fe-trash-2 me-1"></i> Delete Account</p>
            <button type="button" class="coa-modal-close" id="coaDeleteClose"><i class="fe fe-x"></i></button>
        </div>
        <div class="coa-modal-body">
            <p style="font-size:.86rem;color:#475569;margin-bottom:6px;">
                Are you sure you want to delete <strong id="deleteAccountName"></strong>?
            </p>
            <p style="font-size:.78rem;color:#94a3b8;margin-bottom:18px;">
                This action cannot be undone. Accounts with transactions cannot be deleted.
            </p>
            <form method="POST" id="coaDeleteForm">
                @csrf
                @method('DELETE')
                <div style="display:flex;gap:10px;">
                    <button type="submit"
                        style="flex:1;padding:10px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;">
                        <i class="fe fe-trash-2 me-1"></i> Yes, Delete
                    </button>
                    <button type="button" id="coaDeleteCancelBtn"
                        style="flex:0 0 auto;padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-size:.85rem;font-weight:700;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Subtype map is global (window._coaSubtypeMap) and coaBuildSubtypes() is a global function.
       Both are inlined before the form so they work immediately without DOMContentLoaded. */
    var subtypeMap = window._coaSubtypeMap || {};

    /* Restore sub_type on validation error */
    (function() {
        var oldSubType = '{{ addslashes(old('sub_type', '')) }}';
        var typeEl = document.getElementById('accountTypeSelect');
        if (typeEl && typeEl.value && oldSubType) {
            coaBuildSubtypes(typeEl.value, 'accountSubTypeSelect', oldSubType);
        }
    })();

    /* ── Live search + filter ── */
    var searchInput  = document.getElementById('coaSearch');
    var typeFilter   = document.getElementById('coaTypeFilter');
    var statusFilter = document.getElementById('coaStatusFilter');

    function applyFilters() {
        var q      = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var type   = (typeFilter ? typeFilter.value : '').toLowerCase();
        var status = (statusFilter ? statusFilter.value : '').toLowerCase();

        document.querySelectorAll('.coa-block').forEach(function (block) {
            var blockType = (block.dataset.type || '').toLowerCase();
            var typeMatch = !type || blockType === type;
            var anyVisible = false;
            block.querySelectorAll('.coa-row').forEach(function (row) {
                var nameMatch   = !q || row.dataset.name.indexOf(q) !== -1 || row.dataset.code.indexOf(q) !== -1;
                var statusMatch = !status || row.dataset.status === status;
                var visible     = typeMatch && nameMatch && statusMatch;
                row.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
            block.style.display = (typeMatch && anyVisible) ? '' : 'none';
        });
    }

    if (searchInput)  searchInput.addEventListener('input', applyFilters);
    if (typeFilter)   typeFilter.addEventListener('change', applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);

    /* ── Edit modal ── */
    var editOverlay  = document.getElementById('coaEditOverlay');
    var editForm     = document.getElementById('coaEditForm');
    var editName     = document.getElementById('editName');
    var editSubType  = document.getElementById('editSubType');
    var editOpening  = document.getElementById('editOpeningBalance');
    var editActive   = document.getElementById('editIsActive');
    var editDesc     = document.getElementById('editDescription');
    var editClose    = document.getElementById('coaEditClose');
    var editCancelBtn = document.getElementById('coaEditCancelBtn');

    function buildEditSubtypeOptions(type, current) {
        var sel = document.getElementById('editSubType');
        if (!sel) return;
        sel.value = current || '';
    }

    document.querySelectorAll('.coa-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id          = btn.dataset.id;
            var type        = btn.dataset.type;
            editForm.action = '{{ url("settings/chart-of-accounts") }}/' + id;
            editName.value  = btn.dataset.name;
            editOpening.value = btn.dataset.opening;
            editActive.value  = btn.dataset.active;
            editDesc.value    = btn.dataset.description || '';
            buildEditSubtypeOptions(type, btn.dataset.subType);
            editOverlay.classList.add('active');
            editName.focus();
        });
    });

    function closeEditModal() { editOverlay.classList.remove('active'); }
    if (editClose)    editClose.addEventListener('click', closeEditModal);
    if (editCancelBtn) editCancelBtn.addEventListener('click', closeEditModal);
    editOverlay && editOverlay.addEventListener('click', function(e) { if (e.target === editOverlay) closeEditModal(); });

    /* ── Delete modal ── */
    var deleteOverlay   = document.getElementById('coaDeleteOverlay');
    var deleteForm      = document.getElementById('coaDeleteForm');
    var deleteNameEl    = document.getElementById('deleteAccountName');
    var deleteClose     = document.getElementById('coaDeleteClose');
    var deleteCancelBtn = document.getElementById('coaDeleteCancelBtn');

    var deactivateOverlay   = document.getElementById('coaDeactivateOverlay');
    var deactivateForm      = document.getElementById('coaDeactivateForm');
    var deactivateNameEl    = document.getElementById('deactivateAccountName');
    var deactivateClose     = document.getElementById('coaDeactivateClose');
    var deactivateCancelBtn = document.getElementById('coaDeactivateCancelBtn');

    function closeDeactivateModal() { deactivateOverlay.classList.remove('active'); }
    if (deactivateClose)     deactivateClose.addEventListener('click', closeDeactivateModal);
    if (deactivateCancelBtn) deactivateCancelBtn.addEventListener('click', closeDeactivateModal);
    deactivateOverlay && deactivateOverlay.addEventListener('click', function(e) { if (e.target === deactivateOverlay) closeDeactivateModal(); });

    document.querySelectorAll('.coa-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id     = btn.dataset.id;
            var name   = btn.dataset.name;
            var txns   = parseInt(btn.dataset.txns || '0', 10);
            var active = btn.dataset.active; // '1' = active, '0' = inactive/deactivated

            if (txns > 0 && active === '1') {
                // Active account with transactions → offer to deactivate instead
                deactivateForm.action = '{{ url("settings/chart-of-accounts") }}/' + id + '/deactivate';
                deactivateNameEl.textContent = name;
                deactivateOverlay.classList.add('active');
                return;
            }
            if (txns > 0 && active === '0') {
                // Deactivated account but still has transactions → cannot delete
                alert('"' + name + '" is deactivated but still has ' + txns + ' transaction(s) linked to it.\n\nTo permanently delete this account, first remove its transactions from the ledger/journal, then try again.');
                return;
            }
            // No transactions → confirm delete
            deleteForm.action  = '{{ url("settings/chart-of-accounts") }}/' + id;
            deleteNameEl.textContent = name;
            deleteOverlay.classList.add('active');
        });
    });

    function closeDeleteModal() { deleteOverlay.classList.remove('active'); }
    if (deleteClose)     deleteClose.addEventListener('click', closeDeleteModal);
    if (deleteCancelBtn) deleteCancelBtn.addEventListener('click', closeDeleteModal);
    deleteOverlay && deleteOverlay.addEventListener('click', function(e) { if (e.target === deleteOverlay) closeDeleteModal(); });
});
</script>
@endpush
