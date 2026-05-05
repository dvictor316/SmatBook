<?php $page = 'database-reset'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    .dbreset-danger-card {
        border: 2px solid #991b1b;
        border-radius: 12px;
        background: #fff;
    }
    .dbreset-table th {
        font-size: 0.77rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
    }
    .dbreset-table td { font-size: 0.85rem; }
    .badge-zero  { background: #f0fdf4; color: #15803d; }
    .badge-rows  { background: #fef2f2; color: #b91c1c; }
    #db-reset-log {
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        white-space: pre-wrap;
        background: #0f172a;
        color: #86efac;
        border-radius: 8px;
        padding: 1rem;
        max-height: 400px;
        overflow-y: auto;
    }
    .step-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #dc2626;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .preserve-chip {
        display: inline-block;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.78rem;
        margin: 2px;
    }
    .wipe-chip {
        display: inline-block;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.78rem;
        margin: 2px;
    }
    #db-reset-btn:disabled { cursor: not-allowed; opacity: 0.6; }
</style>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">
                <span class="text-danger">🔴</span> Full Database Reset
            </h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Super Admin</a></li>
                <li class="breadcrumb-item active">Database Reset</li>
            </ul>
        </div>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-xl-10">

    {{-- ── CRITICAL WARNING ─────────────────────────────────────────────── --}}
    <div class="alert d-flex align-items-start gap-3 mb-4"
         style="background:#450a0a;border:2px solid #dc2626;color:#fca5a5;border-radius:12px;">
        <span style="font-size:2.4rem;line-height:1;">⚠</span>
        <div>
            <h5 class="mb-1" style="color:#fca5a5;font-weight:700;">CRITICAL — THIS ACTION IS IRREVERSIBLE</h5>
            <p class="mb-1" style="color:#fecaca;">
                All <strong style="color:#fff;">business data, company records, tenants, transactions, customers,
                invoices, products, employees, logs</strong> and more will be <strong style="color:#fff;">permanently deleted</strong>.
            </p>
            <p class="mb-0" style="color:#fca5a5;">
                Only the <strong style="color:#fff;">super admin account</strong>, subscription plans, and core system tables will be preserved.
                Make sure you have a backup before continuing.
            </p>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── LEFT COLUMN: PREVIEW ─────────────────────────────────────── --}}
        <div class="col-lg-6">

            {{-- Impact summary --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0"><i class="fa fa-database me-1"></i> Impact Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fs-3 fw-bold text-danger">{{ number_format($grandTotal) }}</div>
                                <small class="text-muted">Total Rows to Wipe</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fs-3 fw-bold text-warning">{{ number_format($userWipeCount) }}</div>
                                <small class="text-muted">Non-Admin Users Removed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preserved tables --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="fa fa-shield-alt me-1"></i> What Will Be Preserved</h6>
                </div>
                <div class="card-body">
                    <div>
                        <span class="preserve-chip">Super admin user</span>
                        <span class="preserve-chip">roles</span>
                        <span class="preserve-chip">permissions</span>
                        <span class="preserve-chip">role_has_permissions</span>
                        <span class="preserve-chip">plans</span>
                        <span class="preserve-chip">packages</span>
                        <span class="preserve-chip">migrations</span>
                        <span class="preserve-chip">password_reset_tokens</span>
                        <span class="preserve-chip">personal_access_tokens</span>
                        <span class="preserve-chip">failed_jobs</span>
                        <span class="preserve-chip">languages</span>
                        <span class="preserve-chip">landing_pages</span>
                        <span class="preserve-chip">Global settings</span>
                    </div>
                    <hr class="my-2">
                    <p class="small text-muted mb-0">
                        <i class="fa fa-info-circle me-1 text-success"></i>
                        Roles and permissions will be wiped and immediately <strong>reseeded</strong> with defaults.
                    </p>
                </div>
            </div>

            {{-- Row counts preview --}}
            <div class="card dbreset-danger-card">
                <div class="card-header bg-danger text-white py-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0"><i class="fa fa-trash me-1"></i> Tables to be Wiped</h6>
                    <small class="opacity-75">{{ count($preview) }} tables</small>
                </div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm mb-0 dbreset-table">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="ps-3">Table</th>
                                <th class="text-end pe-3">Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview as $table => $count)
                            <tr>
                                <td class="ps-3 font-monospace" style="font-size:0.8rem;">{{ $table }}</td>
                                <td class="text-end pe-3">
                                    @if ($count === 0)
                                        <span class="badge badge-zero">0</span>
                                    @else
                                        <span class="badge badge-rows">{{ number_format($count) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            <tr class="table-warning">
                                <td class="ps-3 fw-bold">users (non-super-admin)</td>
                                <td class="text-end pe-3">
                                    <span class="badge badge-rows">{{ number_format($userWipeCount) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /col-lg-6 left --}}

        {{-- ── RIGHT COLUMN: CONFIRMATION FORM ─────────────────────────── --}}
        <div class="col-lg-6">

            <div class="card shadow-sm dbreset-danger-card">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0"><i class="fa fa-exclamation-triangle me-1"></i> Confirm Reset</h6>
                </div>
                <div class="card-body">

                    {{-- Step 1 --}}
                    <div class="d-flex gap-3 mb-4">
                        <div class="step-badge">1</div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">Acknowledge all 3 consequences</label>
                            <div class="form-check mb-1">
                                <input class="form-check-input consent-check" type="checkbox" id="ack1">
                                <label class="form-check-label small" for="ack1">
                                    I understand all business data will be permanently deleted.
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input consent-check" type="checkbox" id="ack2">
                                <label class="form-check-label small" for="ack2">
                                    I understand this action cannot be undone and there is no rollback.
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input consent-check" type="checkbox" id="ack3">
                                <label class="form-check-label small" for="ack3">
                                    I have taken a full database backup before proceeding.
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Step 2 --}}
                    <div class="d-flex gap-3 mb-4">
                        <div class="step-badge">2</div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                Type exactly: <code class="text-danger">FULL DATABASE RESET</code>
                            </label>
                            <input type="text" id="confirmation_phrase" class="form-control"
                                   placeholder="FULL DATABASE RESET" autocomplete="off" spellcheck="false">
                            <div id="phrase-feedback" class="form-text text-danger d-none">Phrase does not match.</div>
                        </div>
                    </div>

                    <hr>

                    {{-- Step 3 --}}
                    <div class="d-flex gap-3 mb-4">
                        <div class="step-badge">3</div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">Enter your super admin password</label>
                            <input type="password" id="admin_password" class="form-control"
                                   placeholder="Password" autocomplete="current-password">
                        </div>
                    </div>

                    <button id="db-reset-btn" type="button" class="btn btn-danger w-100 py-2 fw-bold" disabled
                            onclick="executeFullReset()">
                        <i class="fa fa-bomb me-2"></i> EXECUTE FULL DATABASE RESET
                    </button>

                    <p class="text-center text-muted small mt-2 mb-0">
                        This will wipe <strong>{{ number_format($grandTotal) }} rows</strong> across
                        {{ count($preview) }} tables, then reseed defaults.
                    </p>
                </div>
            </div>

            {{-- Post-reset checklist --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="fa fa-tasks me-1 text-info"></i> Post-Reset Validation Checklist</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small" id="post-reset-checklist" style="opacity:0.4;">
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> Super admin can log in</li>
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> Dashboard loads without errors</li>
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> All financial reports show zero</li>
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> No business records remain</li>
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> Roles & permissions reseeded</li>
                        <li><i class="fa fa-circle-o me-2 text-secondary"></i> Subscription plans available</li>
                    </ul>
                </div>
            </div>

            {{-- Live log output (hidden until reset runs) --}}
            <div id="db-reset-log-panel" class="mt-4 d-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="fa fa-terminal me-1"></i> Reset Log</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyResetLog()">
                        <i class="fa fa-copy me-1"></i> Copy
                    </button>
                </div>
                <div id="db-reset-log"></div>
            </div>

        </div>{{-- /col-lg-6 right --}}
    </div>{{-- /row --}}

</div>
</div>

<script>
// ── Unlock button only when all 3 checkboxes are ticked ───────────────────────
document.querySelectorAll('.consent-check').forEach(function(cb) {
    cb.addEventListener('change', checkUnlock);
});
document.getElementById('confirmation_phrase').addEventListener('input', function() {
    var match = this.value === 'FULL DATABASE RESET';
    document.getElementById('phrase-feedback').classList.toggle('d-none', match);
    checkUnlock();
});
document.getElementById('admin_password').addEventListener('input', checkUnlock);

function checkUnlock() {
    var allChecked = document.querySelectorAll('.consent-check:not(:checked)').length === 0;
    var phraseOk   = document.getElementById('confirmation_phrase').value === 'FULL DATABASE RESET';
    var passOk     = document.getElementById('admin_password').value.length > 0;
    document.getElementById('db-reset-btn').disabled = !(allChecked && phraseOk && passOk);
}

// ── Execute Reset ─────────────────────────────────────────────────────────────
function executeFullReset() {
    if (! confirm('⚠  FINAL WARNING ⚠\n\nYou are about to permanently destroy all business data.\n\nAre you 100% sure?')) {
        return;
    }

    var btn = document.getElementById('db-reset-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Resetting…';

    var logPanel = document.getElementById('db-reset-log-panel');
    var logEl    = document.getElementById('db-reset-log');
    logPanel.classList.remove('d-none');
    logEl.textContent = 'Starting full database reset…\n';

    fetch('{{ route("super_admin.database.reset.execute") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            confirmation_phrase: document.getElementById('confirmation_phrase').value,
            password: document.getElementById('admin_password').value,
        }),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.log) {
            logEl.textContent += data.log.join('\n') + '\n';
        }

        if (data.success) {
            logEl.textContent += '\n✓ RESET COMPLETE.\n';
            logEl.style.color = '#86efac';
            btn.innerHTML = '<i class="fa fa-check me-2"></i> Reset Complete';
            btn.classList.replace('btn-danger', 'btn-success');

            // Activate checklist
            document.getElementById('post-reset-checklist').style.opacity = '1';
            document.querySelectorAll('#post-reset-checklist .fa-circle-o').forEach(function(icon) {
                icon.classList.replace('fa-circle-o', 'fa-check-circle');
                icon.classList.replace('text-secondary', 'text-success');
            });

            setTimeout(function() {
                alert('Reset complete! You will be redirected to the login page to re-authenticate.');
                window.location.href = '/login';
            }, 2500);
        } else {
            logEl.textContent += '\n✗ RESET FAILED: ' + (data.message || 'Unknown error') + '\n';
            logEl.style.color = '#fca5a5';
            btn.innerHTML = '<i class="fa fa-bomb me-2"></i> EXECUTE FULL DATABASE RESET';
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        logEl.textContent += '\n✗ Network error: ' + err + '\n';
        logEl.style.color = '#fca5a5';
        btn.innerHTML = '<i class="fa fa-bomb me-2"></i> EXECUTE FULL DATABASE RESET';
        btn.disabled = false;
    });
}

function copyResetLog() {
    var text = document.getElementById('db-reset-log').textContent;
    navigator.clipboard.writeText(text).then(function() {
        alert('Log copied to clipboard.');
    });
}
</script>
@endsection
