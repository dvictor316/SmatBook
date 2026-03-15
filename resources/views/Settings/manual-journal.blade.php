<?php $page = 'manual-journal'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    .journal-shell {
        display: grid;
        gap: 20px;
    }

    .journal-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }

    .journal-card .card-body {
        padding: 22px;
    }

    .journal-line-grid {
        display: grid;
        grid-template-columns: minmax(240px, 2fr) minmax(120px, 0.8fr) minmax(120px, 0.8fr) minmax(200px, 1.4fr) auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }

    .journal-line-head {
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 8px;
    }

    .journal-line {
        padding: 12px;
        border: 1px solid #e6eefc;
        border-radius: 14px;
        background: #fff;
    }

    .journal-totals {
        display: flex;
        justify-content: flex-end;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .journal-total-chip {
        min-width: 160px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #f8fbff;
        border: 1px solid #dbe7ff;
    }

    .journal-total-chip small {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }

    .journal-total-chip strong {
        font-size: 1rem;
        color: #0f172a;
    }

    .journal-status-ok {
        color: #166534;
    }

    .journal-status-bad {
        color: #b91c1c;
    }

    @media (max-width: 991px) {
        .journal-line-head {
            display: none;
        }

        .journal-line-grid {
            grid-template-columns: 1fr;
        }
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
                        <h4 class="mb-1">Manual Journal Entry</h4>
                        <p class="text-muted mb-0">Post balanced debit and credit entries directly into the general ledger.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">Core Accounting Control</span>
                </div>

                <div class="journal-shell">
                    <div class="card journal-card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('settings.manual-journal.store') }}" id="manualJournalForm">
                                @csrf
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Reference</label>
                                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="JRNL-20260315-01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Month-end reclassification">
                                    </div>
                                </div>

                                <div class="journal-line-head journal-line-grid">
                                    <div>Account</div>
                                    <div>Debit</div>
                                    <div>Credit</div>
                                    <div>Memo</div>
                                    <div></div>
                                </div>

                                <div id="journalLines">
                                    @php
                                        $oldLines = old('lines', [
                                            ['account_id' => '', 'debit' => '', 'credit' => '', 'memo' => ''],
                                            ['account_id' => '', 'debit' => '', 'credit' => '', 'memo' => ''],
                                        ]);
                                    @endphp

                                    @foreach($oldLines as $index => $line)
                                        <div class="journal-line journal-line-grid">
                                            <div>
                                                <select name="lines[{{ $index }}][account_id]" class="form-select">
                                                    <option value="">Select account</option>
                                                    @foreach($accounts as $account)
                                                        <option value="{{ $account->id }}" {{ (string) ($line['account_id'] ?? '') === (string) $account->id ? 'selected' : '' }}>
                                                            {{ $account->code }} - {{ $account->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <input type="number" step="0.01" min="0" name="lines[{{ $index }}][debit]" class="form-control journal-debit" value="{{ $line['debit'] ?? '' }}" placeholder="0.00">
                                            </div>
                                            <div>
                                                <input type="number" step="0.01" min="0" name="lines[{{ $index }}][credit]" class="form-control journal-credit" value="{{ $line['credit'] ?? '' }}" placeholder="0.00">
                                            </div>
                                            <div>
                                                <input type="text" name="lines[{{ $index }}][memo]" class="form-control" value="{{ $line['memo'] ?? '' }}" placeholder="Optional line note">
                                            </div>
                                            <div class="text-end">
                                                <button type="button" class="btn btn-light border journal-remove-line">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-primary" id="addJournalLine">Add Line</button>
                                    <div class="journal-totals">
                                        <div class="journal-total-chip">
                                            <small>Total Debit</small>
                                            <strong id="journalDebitTotal">0.00</strong>
                                        </div>
                                        <div class="journal-total-chip">
                                            <small>Total Credit</small>
                                            <strong id="journalCreditTotal">0.00</strong>
                                        </div>
                                        <div class="journal-total-chip">
                                            <small>Status</small>
                                            <strong id="journalBalanceStatus" class="journal-status-bad">Not Balanced</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4">Post Journal Entry</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card journal-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">Recent Journal Entries</h5>
                                    <p class="text-muted mb-0">Latest manual journals grouped by reference.</p>
                                </div>
                            </div>

                            @forelse($recentJournalGroups as $reference => $entries)
                                <div class="border rounded-4 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <div>
                                            <strong>{{ $reference }}</strong>
                                            <div class="text-muted small">{{ optional($entries->first()->transaction_date)->format('d M Y') }}</div>
                                        </div>
                                        <span class="badge bg-light text-dark">{{ $entries->count() }} line(s)</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th>Description</th>
                                                    <th class="text-end">Debit</th>
                                                    <th class="text-end">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($entries as $entry)
                                                    <tr>
                                                        <td>{{ optional($entry->account)->code }} - {{ optional($entry->account)->name ?: 'Account' }}</td>
                                                        <td>{{ $entry->description ?: '-' }}</td>
                                                        <td class="text-end">{{ number_format((float) $entry->debit, 2) }}</td>
                                                        <td class="text-end">{{ number_format((float) $entry->credit, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    No manual journal entries yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const journalLines = document.getElementById('journalLines');
    const addLineBtn = document.getElementById('addJournalLine');
    const debitTotal = document.getElementById('journalDebitTotal');
    const creditTotal = document.getElementById('journalCreditTotal');
    const balanceStatus = document.getElementById('journalBalanceStatus');

    const accountOptions = @json(
        $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->code . ' - ' . $account->name,
        ])->values()
    );

    function buildAccountOptions() {
        const options = ['<option value="">Select account</option>'];
        accountOptions.forEach((account) => {
            options.push(`<option value="${account.id}">${account.label}</option>`);
        });

        return options.join('');
    }

    function recalcTotals() {
        const debit = Array.from(document.querySelectorAll('.journal-debit')).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
        const credit = Array.from(document.querySelectorAll('.journal-credit')).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);

        debitTotal.textContent = debit.toFixed(2);
        creditTotal.textContent = credit.toFixed(2);

        if (Math.abs(debit - credit) < 0.01 && debit > 0 && credit > 0) {
            balanceStatus.textContent = 'Balanced';
            balanceStatus.classList.remove('journal-status-bad');
            balanceStatus.classList.add('journal-status-ok');
        } else {
            balanceStatus.textContent = 'Not Balanced';
            balanceStatus.classList.remove('journal-status-ok');
            balanceStatus.classList.add('journal-status-bad');
        }
    }

    function bindLineEvents(container) {
        container.querySelectorAll('.journal-debit, .journal-credit').forEach((input) => {
            input.addEventListener('input', function () {
                const row = this.closest('.journal-line');
                if (!row) return;

                if (this.classList.contains('journal-debit') && parseFloat(this.value || 0) > 0) {
                    const creditInput = row.querySelector('.journal-credit');
                    if (creditInput) creditInput.value = '';
                }

                if (this.classList.contains('journal-credit') && parseFloat(this.value || 0) > 0) {
                    const debitInput = row.querySelector('.journal-debit');
                    if (debitInput) debitInput.value = '';
                }

                recalcTotals();
            });
        });

        container.querySelectorAll('.journal-remove-line').forEach((button) => {
            button.addEventListener('click', function () {
                const rows = journalLines.querySelectorAll('.journal-line');
                if (rows.length <= 2) return;
                this.closest('.journal-line')?.remove();
                reindexLines();
                recalcTotals();
            });
        });
    }

    function reindexLines() {
        Array.from(journalLines.querySelectorAll('.journal-line')).forEach((line, index) => {
            line.querySelectorAll('input, select').forEach((field) => {
                field.name = field.name.replace(/lines\[\d+\]/, `lines[${index}]`);
            });
        });
    }

    addLineBtn?.addEventListener('click', function () {
        const index = journalLines.querySelectorAll('.journal-line').length;
        const wrapper = document.createElement('div');
        wrapper.className = 'journal-line journal-line-grid';
        wrapper.innerHTML = `
            <div>
                <select name="lines[${index}][account_id]" class="form-select">
                    ${buildAccountOptions()}
                </select>
            </div>
            <div>
                <input type="number" step="0.01" min="0" name="lines[${index}][debit]" class="form-control journal-debit" placeholder="0.00">
            </div>
            <div>
                <input type="number" step="0.01" min="0" name="lines[${index}][credit]" class="form-control journal-credit" placeholder="0.00">
            </div>
            <div>
                <input type="text" name="lines[${index}][memo]" class="form-control" placeholder="Optional line note">
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-light border journal-remove-line">Remove</button>
            </div>
        `;
        journalLines.appendChild(wrapper);
        bindLineEvents(wrapper);
        recalcTotals();
    });

    bindLineEvents(document);
    recalcTotals();
});
</script>
@endsection
