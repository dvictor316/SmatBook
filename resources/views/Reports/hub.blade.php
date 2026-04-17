@extends('layout.mainlayout')

@section('content')
<style>
/* ── Reports Hub — QuickBooks-style ─────────────────────── */
.rh-page { background: #f7f8fc; min-height: 100vh; }

/* Tab bar */
.rh-tab-bar {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #dee2e9;
    background: #fff;
    padding: 0 24px;
}
.rh-tab {
    padding: 14px 22px;
    font-size: 13.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 7px;
}
.rh-tab:hover { color: #1e3a5f; }
.rh-tab.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}
.rh-tab .rh-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    background: #e8eef8;
    color: #2563eb;
    font-size: 10px;
    font-weight: 800;
    padding: 0 5px;
}
.rh-tab.active .rh-tab-count { background: #2563eb; color: #fff; }

/* Search bar above cards */
.rh-search-wrap {
    background: #fff;
    border-bottom: 1px solid #dee2e9;
    padding: 12px 24px;
}
.rh-search-input {
    width: 100%;
    max-width: 340px;
    padding: 8px 14px 8px 36px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    color: #1e293b;
    background: #f8fafd url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 10px center;
    transition: border-color .15s, box-shadow .15s;
}
.rh-search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

/* Section headers */
.rh-section-label {
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #94a3b8;
    padding: 22px 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rh-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e4e8f0;
    margin-left: 4px;
}

/* Report cards grid */
.rh-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 14px;
}

/* Individual report card */
.rh-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s, transform .1s;
    position: relative;
    overflow: hidden;
}
.rh-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 16px rgba(37,99,235,.10);
    transform: translateY(-1px);
    text-decoration: none;
    color: inherit;
}
.rh-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
.rh-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.rh-card-desc {
    font-size: 11.5px;
    color: #94a3b8;
    line-height: 1.45;
}
.rh-card-arrow {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #cbd5e1;
    font-size: 12px;
    transition: color .15s, right .15s;
}
.rh-card:hover .rh-card-arrow { color: #2563eb; right: 13px; }

/* Plan badges */
.rh-plan-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .04em;
}
.rh-badge-pro  { background: #eff6ff; color: #1d4ed8; }
.rh-badge-ent  { background: #fdf4ff; color: #7c3aed; }

/* Hidden card (filtered out) */
.rh-card.rh-hidden { display: none !important; }

/* Empty state */
.rh-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    font-size: 13px;
}

/* Colour palettes */
.pal-blue   { background: #eff6ff; color: #2563eb; }
.pal-green  { background: #f0fdf4; color: #16a34a; }
.pal-orange { background: #fff7ed; color: #ea580c; }
.pal-purple { background: #fdf4ff; color: #7c3aed; }
.pal-red    { background: #fef2f2; color: #dc2626; }
.pal-teal   { background: #f0fdfa; color: #0d9488; }
.pal-amber  { background: #fffbeb; color: #d97706; }
.pal-slate  { background: #f8fafc; color: #475569; }
</style>

<div class="page-wrapper rh-page">
    <div class="content container-fluid">

        {{-- Page heading --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-0" style="gap:10px; padding-bottom:16px;">
            <div>
                <h4 class="fw-bold mb-1" style="color:#0f172a; font-size:20px;">Reports</h4>
                <div style="font-size:12.5px; color:#64748b;">Select a report to view detailed data for your business.</div>
            </div>
        </div>

        {{-- Tab bar + search + cards all inside one white document block --}}
        <div style="background:#fff; border:1px solid #dee2e9; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.05); overflow:hidden;">

            {{-- Tabs --}}
            <div class="rh-tab-bar">
                <button class="rh-tab active" data-tab="all">
                    <i class="fas fa-th-large" style="font-size:12px;"></i> All Reports
                    <span class="rh-tab-count" id="count-all">19</span>
                </button>
                <button class="rh-tab" data-tab="standard">
                    <i class="fas fa-file-alt" style="font-size:12px;"></i> Standard
                    <span class="rh-tab-count" id="count-standard">0</span>
                </button>
                <button class="rh-tab" data-tab="custom">
                    <i class="fas fa-layer-group" style="font-size:12px;"></i> Inventory &amp; Custom
                    <span class="rh-tab-count" id="count-custom">0</span>
                </button>
                <button class="rh-tab" data-tab="management">
                    <i class="fas fa-chart-bar" style="font-size:12px;"></i> Management
                    <span class="rh-tab-count" id="count-management">0</span>
                </button>
            </div>

            {{-- Search --}}
            <div class="rh-search-wrap">
                <input type="text" id="rh-search" class="rh-search-input" placeholder="Search reports…" autocomplete="off">
            </div>

            {{-- Card area --}}
            <div class="p-4">

                {{-- ── STANDARD section label ──────────────── --}}
                <div class="rh-section-label" data-section="standard">Sales &amp; Operations</div>
                <div class="rh-grid" id="grid-standard">

                    <a href="{{ route('reports.sales') }}" class="rh-card" data-tab="standard" data-keywords="sales report revenue invoices">
                        <div class="rh-card-icon pal-blue"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div>
                            <div class="rh-card-title">Sales Report</div>
                            <div class="rh-card-desc">Review all sales transactions, revenue totals and customer activity.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.purchase') }}" class="rh-card" data-tab="standard" data-keywords="purchase report suppliers buying">
                        <div class="rh-card-icon pal-teal"><i class="fas fa-shopping-cart"></i></div>
                        <div>
                            <div class="rh-card-title">Purchase Report</div>
                            <div class="rh-card-desc">Track purchases from suppliers, amounts spent, and order history.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.expense') }}" class="rh-card" data-tab="standard" data-keywords="expense report spending costs">
                        <div class="rh-card-icon pal-red"><i class="fas fa-receipt"></i></div>
                        <div>
                            <div class="rh-card-title">Expense Report</div>
                            <div class="rh-card-desc">View all recorded expenses by category, date, and amount.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.income') }}" class="rh-card" data-tab="standard" data-keywords="income report earnings revenue">
                        <div class="rh-card-icon pal-green"><i class="fas fa-hand-holding-usd"></i></div>
                        <div>
                            <div class="rh-card-title">Income Report</div>
                            <div class="rh-card-desc">Summarise all income streams and earnings over a period.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.payment') }}" class="rh-card" data-tab="standard" data-keywords="payment report received collection">
                        <div class="rh-card-icon pal-amber"><i class="fas fa-money-check-alt"></i></div>
                        <div>
                            <div class="rh-card-title">Payment Report</div>
                            <div class="rh-card-desc">Monitor all incoming payments and collection status.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.payment-summary') }}" class="rh-card" data-tab="standard" data-keywords="payment summary overview total">
                        <div class="rh-card-icon pal-blue"><i class="fas fa-credit-card"></i></div>
                        <div>
                            <div class="rh-card-title">Payment Summary</div>
                            <div class="rh-card-desc">High-level summary of all payment transactions and totals.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.quotation') }}" class="rh-card" data-tab="standard" data-keywords="quotation report estimates quotes">
                        <div class="rh-card-icon pal-slate"><i class="fas fa-file-contract"></i></div>
                        <div>
                            <div class="rh-card-title">Quotation Report</div>
                            <div class="rh-card-desc">Review issued quotes, conversion rates, and pending estimates.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.sales-return') }}" class="rh-card" data-tab="standard" data-keywords="sales return credit notes refund">
                        <div class="rh-card-icon pal-orange"><i class="fas fa-undo-alt"></i></div>
                        <div>
                            <div class="rh-card-title">Sales Return Report</div>
                            <div class="rh-card-desc">Track returned items, credit notes, and refunded amounts.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('pos.reports') }}" class="rh-card" data-tab="standard" data-keywords="pos report point of sale">
                        <div class="rh-card-icon pal-teal"><i class="fas fa-cash-register"></i></div>
                        <div>
                            <div class="rh-card-title">POS Sales Report</div>
                            <div class="rh-card-desc">Sold units, stock position, and gross value from POS activity.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                </div>

                {{-- ── CUSTOM / INVENTORY section ──────────── --}}
                <div class="rh-section-label" data-section="custom">Inventory &amp; Custom</div>
                <div class="rh-grid" id="grid-custom">

                    <a href="{{ route('reports.stock') }}" class="rh-card" data-tab="custom" data-keywords="stock report inventory levels">
                        <div class="rh-card-icon pal-green"><i class="fas fa-boxes"></i></div>
                        <div>
                            <div class="rh-card-title">Stock Report</div>
                            <div class="rh-card-desc">Current stock quantities, values, and product movement overview.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.low-stock') }}" class="rh-card" data-tab="custom" data-keywords="low stock alert reorder">
                        <div class="rh-card-icon pal-red"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div class="rh-card-title">Low Stock Report</div>
                            <div class="rh-card-desc">Products running below reorder threshold that need restocking.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.accounts-receivable') }}" class="rh-card" data-tab="custom" data-keywords="accounts receivable outstanding debtors owed">
                        <div class="rh-card-icon pal-amber"><i class="fas fa-user-clock"></i></div>
                        <div>
                            <div class="rh-card-title">Accounts Receivable</div>
                            <div class="rh-card-desc">Outstanding balances owed by customers, aging, and overdue invoices.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.purchase') }}" class="rh-card" data-tab="custom" data-keywords="purchase return supplier returns">
                        <div class="rh-card-icon pal-orange"><i class="fas fa-truck-loading"></i></div>
                        <div>
                            <div class="rh-card-title">Purchase Return</div>
                            <div class="rh-card-desc">Items returned to suppliers with debit notes and refund status.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                </div>

                {{-- ── MANAGEMENT section ──────────────────── --}}
                <div class="rh-section-label" data-section="management">Financial &amp; Management</div>
                <div class="rh-grid" id="grid-management">

                    <a href="{{ route('reports.profit-loss') }}" class="rh-card" data-tab="management" data-keywords="profit loss p&l income statement">
                        <span class="rh-plan-badge rh-badge-pro position-absolute" style="top:10px;right:10px;">
                            <i class="fas fa-star" style="font-size:8px;"></i> Pro
                        </span>
                        <div class="rh-card-icon pal-blue"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="rh-card-title">Profit &amp; Loss</div>
                            <div class="rh-card-desc">Net profit/loss across a date range, showing income vs expenses.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('trial-balance') }}" class="rh-card" data-tab="management" data-keywords="trial balance debits credits accounts">
                        <div class="rh-card-icon pal-slate"><i class="fas fa-balance-scale"></i></div>
                        <div>
                            <div class="rh-card-title">Trial Balance</div>
                            <div class="rh-card-desc">Debit and credit account totals to verify ledger accuracy.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('balance-sheet') }}" class="rh-card" data-tab="management" data-keywords="balance sheet assets liabilities equity">
                        <div class="rh-card-icon pal-teal"><i class="fas fa-landmark"></i></div>
                        <div>
                            <div class="rh-card-title">Balance Sheet</div>
                            <div class="rh-card-desc">Snapshot of assets, liabilities, and equity at a point in time.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.cash-flow') }}" class="rh-card" data-tab="management" data-keywords="cash flow statement liquidity">
                        <div class="rh-card-icon pal-green"><i class="fas fa-water"></i></div>
                        <div>
                            <div class="rh-card-title">Cash Flow</div>
                            <div class="rh-card-desc">Track inflows and outflows of cash across operating activities.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    @if(Route::has('general-ledger'))
                    <a href="{{ route('general-ledger') }}" class="rh-card" data-tab="management" data-keywords="general ledger accounts transactions">
                        <span class="rh-plan-badge rh-badge-ent position-absolute" style="top:10px;right:10px;">
                            <i class="fas fa-lock" style="font-size:8px;"></i> Ent
                        </span>
                        <div class="rh-card-icon pal-purple"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="rh-card-title">General Ledger</div>
                            <div class="rh-card-desc">Full double-entry ledger with all account transaction history.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>
                    @endif

                    <a href="{{ route('reports.tax-purchase') }}" class="rh-card" data-tab="management" data-keywords="tax purchase vat gst compliance">
                        <span class="rh-plan-badge rh-badge-ent position-absolute" style="top:10px;right:10px;">
                            <i class="fas fa-lock" style="font-size:8px;"></i> Ent
                        </span>
                        <div class="rh-card-icon pal-amber"><i class="fas fa-percentage"></i></div>
                        <div>
                            <div class="rh-card-title">Tax on Purchases</div>
                            <div class="rh-card-desc">Breakdown of taxes paid on all purchase transactions.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                    <a href="{{ route('reports.tax-sales') }}" class="rh-card" data-tab="management" data-keywords="tax sales vat gst collected compliance">
                        <span class="rh-plan-badge rh-badge-ent position-absolute" style="top:10px;right:10px;">
                            <i class="fas fa-lock" style="font-size:8px;"></i> Ent
                        </span>
                        <div class="rh-card-icon pal-amber"><i class="fas fa-percent"></i></div>
                        <div>
                            <div class="rh-card-title">Tax on Sales</div>
                            <div class="rh-card-desc">Breakdown of taxes collected on all sales transactions.</div>
                        </div>
                        <i class="fas fa-chevron-right rh-card-arrow"></i>
                    </a>

                </div>

                {{-- Empty state shown when search returns nothing --}}
                <div id="rh-empty-state" class="rh-empty" style="display:none;">
                    <i class="fas fa-search fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                    No reports match your search.
                </div>

            </div>{{-- /.p-4 --}}
        </div>

    </div>{{-- /.content --}}
</div>

<script>
(function () {
    const tabs    = document.querySelectorAll('.rh-tab');
    const cards   = document.querySelectorAll('.rh-card');
    const search  = document.getElementById('rh-search');
    const empty   = document.getElementById('rh-empty-state');
    const grids   = { standard: [], custom: [], management: [] };

    // Count cards per tab
    cards.forEach(c => {
        const t = c.dataset.tab;
        if (grids[t]) grids[t].push(c);
    });
    document.getElementById('count-standard').textContent   = grids.standard.length;
    document.getElementById('count-custom').textContent     = grids.custom.length;
    document.getElementById('count-management').textContent = grids.management.length;
    document.getElementById('count-all').textContent        = cards.length;

    let activeTab = 'all';

    function applyFilters() {
        const q = (search.value || '').toLowerCase().trim();
        let visible = 0;

        cards.forEach(card => {
            const tabMatch = activeTab === 'all' || card.dataset.tab === activeTab;
            const kw       = (card.dataset.keywords || '') + ' ' + (card.querySelector('.rh-card-title')?.textContent || '');
            const kwMatch  = !q || kw.toLowerCase().includes(q);
            const show     = tabMatch && kwMatch;
            card.classList.toggle('rh-hidden', !show);
            if (show) visible++;
        });

        // Show/hide section labels
        document.querySelectorAll('.rh-section-label').forEach(label => {
            const section = label.dataset.section;
            const sectionHasVisible = document.querySelectorAll(`.rh-card[data-tab="${section}"]:not(.rh-hidden)`).length > 0;
            label.style.display = sectionHasVisible ? '' : 'none';
        });

        empty.style.display = visible === 0 ? 'block' : 'none';
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeTab = tab.dataset.tab;
            applyFilters();
        });
    });

    search.addEventListener('input', applyFilters);

    applyFilters(); // Initial run
})();
</script>
@endsection
