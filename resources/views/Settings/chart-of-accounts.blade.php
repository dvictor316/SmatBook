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
    .coa-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .coa-content-stack {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .coa-settings-card {
        border: 1px solid #e8edf5;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 4px 16px rgba(15,23,42,.05);
        padding: 18px 20px;
    }
    .coa-settings-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 14px;
    }
    .coa-settings-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .coa-settings-copy {
        margin: 4px 0 0;
        font-size: 0.8rem;
        color: #94a3b8;
    }
    .coa-settings-shell .settings-menu ul {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }
    .coa-settings-shell .settings-menu .nav-link {
        min-height: 56px;
        border-radius: 12px;
        padding: 10px 12px;
    }
    .coa-main-card {
        border: 1px solid #e8edf5;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 4px 16px rgba(15,23,42,.05);
        padding: 18px;
    }
    .coa-form-shell {
        width: 100%;
        max-width: 860px;
    }

    @media (max-width: 991px) {
        .coa-settings-card,
        .coa-main-card {
            padding: 16px;
        }

        .coa-settings-shell .settings-menu ul {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
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
</style>

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
        <div class="coa-page">
            <div class="coa-settings-card">
                <div class="coa-settings-head">
                    <div>
                        <h5 class="coa-settings-title">Settings</h5>
                        <p class="coa-settings-copy">Move between accounting and company setup without crowding the ledger workspace.</p>
                    </div>
                </div>
                <div class="coa-settings-shell">
                    @component('components.settings-menu')
                    @endcomponent
                </div>
            </div>

            <div class="coa-content-stack">
                <div class="coa-main-card">

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
                            <p>No accounts yet. Add your first account using the form below.</p>
                        </div>
                    </div>
                @endforelse
                </div>

                <div class="coa-form-shell">
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

                                <form method="POST" action="{{ route('settings.chart-of-accounts.store') }}" id="chartAccountForm">
                                    @csrf

                                    {{-- Row: Code + Type --}}
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                        <div>
                                            <label class="coa-field-label">Code</label>
                                            <input type="text" name="code" id="accountCodeInput" class="coa-input {{ $errors->has('code') ? 'is-invalid' : '' }}"
                                                   value="{{ old('code') }}" placeholder="Auto generated">
                                            <small style="display:block;margin-top:4px;color:#94a3b8;font-size:.7rem;">Auto-fills from account type. You can still edit it.</small>
                                            @error('code')<small style="color:#ef4444;font-size:.72rem;">{{ $message }}</small>@enderror
                                        </div>
                                        <div>
                                            <label class="coa-field-label">Type <span style="color:#ef4444">*</span></label>
                                            <select name="type" id="accountTypeSelect" class="coa-input {{ $errors->has('type') ? 'is-invalid' : '' }}" onchange="window.updateAccountSubtypeOptions && window.updateAccountSubtypeOptions(this.value, ''); window.syncGeneratedAccountCode && window.syncGeneratedAccountCode(this.value);" required>
                                                <option value="">Select…</option>
                                                @foreach($formAccountTypes as $t)
                                                    <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
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
                                            <option value="">Select sub type…</option>
                                            @foreach($formSubtypeMap as $parentType => $subtypes)
                                                <optgroup label="{{ $parentType }}">
                                                    @foreach($subtypes as $subtype)
                                                        <option value="{{ $subtype }}" data-parent-type="{{ $parentType }}" {{ old('sub_type') === $subtype ? 'selected' : '' }}>
                                                            {{ $subtype }}
                                                        </option>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var subtypeMap    = @json($formSubtypeMap);
    var typeSelect    = document.getElementById('accountTypeSelect');
    var subTypeSelect = document.getElementById('accountSubTypeSelect');
    var codeInput     = document.getElementById('accountCodeInput');
    var oldSubType    = @json(old('sub_type'));
    var allSubtypeMarkup = subTypeSelect ? subTypeSelect.innerHTML : '';
    var codePrefixes = {
        Asset: 'AST',
        Liability: 'LIB',
        Equity: 'EQT',
        Revenue: 'REV',
        Expense: 'EXP'
    };
    var codeTouchedManually = !!(codeInput && codeInput.value.trim() !== '');

    function buildGeneratedAccountCode(selectedType) {
        var prefix = codePrefixes[selectedType] || 'ACC';
        var suffix = String(Math.floor(Math.random() * 90000) + 10000);
        return prefix + '-' + suffix;
    }

    window.updateAccountSubtypeOptions = function (selectedType, preselect) {
        if (!subTypeSelect) {
            return;
        }

        subTypeSelect.innerHTML = allSubtypeMarkup;

        var options = subtypeMap[selectedType] || [];
        var placeholder = subTypeSelect.querySelector('option[value=""]');
        var groups = Array.from(subTypeSelect.querySelectorAll('optgroup'));

        groups.forEach(function (group) {
            var groupType = group.getAttribute('label');
            var showGroup = !selectedType || groupType === selectedType;
            group.disabled = !showGroup;
            group.hidden = !showGroup;
        });

        if (placeholder) {
            placeholder.textContent = selectedType ? (options.length ? 'Select sub type…' : 'No sub types available') : 'Select sub type…';
        }

        if (preselect) {
            subTypeSelect.value = preselect;
        } else if (selectedType && options.length === 1) {
            subTypeSelect.value = options[0];
        } else {
            subTypeSelect.value = '';
        }
    };

    window.syncGeneratedAccountCode = function (selectedType) {
        if (!codeInput) {
            return;
        }

        if (!selectedType) {
            if (!codeTouchedManually) {
                codeInput.value = '';
            }
            return;
        }

        if (!codeTouchedManually) {
            codeInput.value = buildGeneratedAccountCode(selectedType);
        }
    };

    if (typeSelect && subTypeSelect) {
        window.updateAccountSubtypeOptions(typeSelect.value, oldSubType);
        window.syncGeneratedAccountCode(typeSelect.value);
        typeSelect.addEventListener('change', function () {
            window.updateAccountSubtypeOptions(this.value, '');
            window.syncGeneratedAccountCode(this.value);
        });
    }

    if (codeInput) {
        codeInput.addEventListener('input', function () {
            codeTouchedManually = this.value.trim() !== '';
        });
    }

    window.setTimeout(function () {
        if (typeSelect && subTypeSelect) {
            window.updateAccountSubtypeOptions(typeSelect.value, subTypeSelect.value || oldSubType);
        }
    }, 0);

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
});
</script>
@endsection
