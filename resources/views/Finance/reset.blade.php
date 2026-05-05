<?php $page = 'finance-reset'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    .reset-danger-card {
        border: 2px solid #dc2626;
        border-radius: 12px;
        background: #fff;
    }
    .reset-table th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
    }
    .reset-table td { font-size: 0.88rem; }
    .badge-zero { background:#f0fdf4;color:#15803d; }
    .badge-rows { background:#fef2f2;color:#b91c1c; }
    #reset-log-output {
        font-family: monospace;
        font-size: 0.83rem;
        white-space: pre-wrap;
        background: #0f172a;
        color: #86efac;
        border-radius: 8px;
        padding: 1rem;
        max-height: 380px;
        overflow-y: auto;
    }
</style>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">
                <span class="text-danger">⚠</span> Financial Data Reset
            </h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Super Admin</a></li>
                <li class="breadcrumb-item active">Financial Reset</li>
            </ul>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">

        {{-- ── WARNING BANNER ── --}}
        <div class="alert alert-danger d-flex align-items-start gap-3 mb-4" role="alert">
            <span style="font-size:2rem;line-height:1;">🔴</span>
            <div>
                <h5 class="alert-heading mb-1">Irreversible Operation</h5>
                <p class="mb-0">
                    This will permanently delete <strong>all transactional and accounting data</strong> for your company
                    and zero out all running balances. Master data (chart of accounts, customers, suppliers, products,
                    users, settings) is <strong>preserved</strong>. There is <strong>no undo</strong>.
                </p>
            </div>
        </div>

        {{-- ── DATA PREVIEW ── --}}
        <div class="card reset-danger-card mb-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">Data That Will Be Deleted — Preview</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 reset-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Table</th>
                                <th class="text-end pe-3">Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview as $table => $count)
                            <tr>
                                <td class="ps-3 font-monospace" style="font-size:0.82rem;">{{ $table }}</td>
                                <td class="text-end pe-3">
                                    <span class="badge {{ $count > 0 ? 'badge-rows' : 'badge-zero' }}"
                                          style="font-size:0.8rem;padding:3px 10px;">
                                        {{ number_format($count) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#fef2f2;font-weight:700;">
                                <td class="ps-3">GRAND TOTAL</td>
                                <td class="text-end pe-3">
                                    <span class="text-danger">{{ number_format($grandTotal) }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── WHAT IS PRESERVED ── --}}
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">✓ Preserved (Not Deleted)</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ([
                        'Chart of Accounts', 'Company profile', 'Users & roles',
                        'Customers (records)', 'Suppliers (records)', 'Products & categories',
                        'Banks (records)', 'Settings', 'Tax codes & jurisdictions',
                        'Accounting periods', 'Subscription & plan data',
                    ] as $item)
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2" style="font-size:0.87rem;">
                            <span class="text-success fw-bold">✓</span> {{ $item }}
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="mt-3 mb-0 text-muted" style="font-size:0.84rem;">
                    Customer, supplier and product <em>balances/quantities</em> will be zeroed.
                    Soft-deleted accounts in the chart of accounts will be restored.
                </p>
            </div>
        </div>

        {{-- ── CONFIRMATION FORM ── --}}
        <div class="card reset-danger-card mb-5">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">Double Confirmation Required</h6>
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Step 1 — Type exactly: <code>RESET FINANCIAL DATA</code>
                    </label>
                    <input type="text" id="confirm_phrase" class="form-control"
                           placeholder="RESET FINANCIAL DATA"
                           autocomplete="off" spellcheck="false">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Step 2 — Enter your current password
                    </label>
                    <input type="password" id="confirm_password" class="form-control"
                           placeholder="Your password" autocomplete="current-password">
                </div>

                <div id="reset-alert" class="alert d-none mb-3" role="alert"></div>

                <div class="d-flex gap-3">
                    <button type="button" id="btn-execute-reset"
                            class="btn btn-danger px-4 fw-bold"
                            onclick="executeReset()">
                        🗑 Execute Financial Reset
                    </button>
                    <a href="{{ route('super_admin.dashboard') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>

        {{-- ── RESULT LOG ── --}}
        <div id="reset-log-section" class="d-none mb-5">
            <h6 class="text-white bg-dark rounded-top px-3 py-2 mb-0">
                Reset Execution Log
            </h6>
            <div id="reset-log-output"></div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function executeReset() {
    const phrase   = document.getElementById('confirm_phrase').value.trim();
    const password = document.getElementById('confirm_password').value;
    const alertEl  = document.getElementById('reset-alert');
    const btn      = document.getElementById('btn-execute-reset');
    const logSec   = document.getElementById('reset-log-section');
    const logOut   = document.getElementById('reset-log-output');

    // Client-side phrase check
    if (phrase !== 'RESET FINANCIAL DATA') {
        showAlert('danger', 'Confirmation phrase does not match. Type exactly: RESET FINANCIAL DATA');
        return;
    }
    if (!password) {
        showAlert('danger', 'Please enter your current password.');
        return;
    }

    if (!confirm('Last chance — are you absolutely sure? This cannot be undone.')) {
        return;
    }

    btn.disabled    = true;
    btn.textContent = '⏳ Resetting…';
    alertEl.className = 'alert d-none';

    fetch('{{ route("super_admin.financial.reset.execute") }}', {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
            'Accept':           'application/json',
        },
        body: JSON.stringify({
            confirmation_phrase: phrase,
            password:            password,
        }),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled    = false;
        btn.textContent = '🗑 Execute Financial Reset';

        if (data.success) {
            showAlert('success', data.message);
            logSec.classList.remove('d-none');

            let logText = `✔ Reset complete at {{ now()->format('Y-m-d H:i:s') }}\n`;
            logText += `Total rows deleted/zeroed: ${data.total_rows}\n\n`;
            logText += `─── Per-table breakdown ───\n`;

            for (const [table, count] of Object.entries(data.log || {})) {
                logText += `  ${String(table).padEnd(50, '.')} ${count}\n`;
            }

            if (data.errors && Object.keys(data.errors).length > 0) {
                logText += `\n─── ERRORS (tables skipped) ───\n`;
                for (const [table, msg] of Object.entries(data.errors)) {
                    logText += `  ${table}: ${msg}\n`;
                }
            }

            logOut.textContent = logText;

            // Disable the form to prevent double-submit
            document.getElementById('confirm_phrase').disabled   = true;
            document.getElementById('confirm_password').disabled = true;
            btn.disabled = true;

        } else {
            showAlert('danger', data.message ?? 'Reset failed.');
        }
    })
    .catch(err => {
        btn.disabled    = false;
        btn.textContent = '🗑 Execute Financial Reset';
        showAlert('danger', 'Network error: ' + err.message);
    });
}

function showAlert(type, msg) {
    const el = document.getElementById('reset-alert');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
}
</script>
@endpush
@endsection
