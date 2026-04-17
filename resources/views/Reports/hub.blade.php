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
    overflow-x: auto;
    scrollbar-width: none;
}
.rh-tab-bar::-webkit-scrollbar { display: none; }
.rh-tab {
    padding: 13px 20px;
    font-size: 13px;
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
    flex-shrink: 0;
}
.rh-tab:hover { color: #1e3a5f; }
.rh-tab.active { color: #2563eb; border-bottom-color: #2563eb; }
.rh-tab .rh-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 19px;
    height: 19px;
    border-radius: 10px;
    background: #e8eef8;
    color: #2563eb;
    font-size: 10px;
    font-weight: 800;
    padding: 0 5px;
}
.rh-tab.active .rh-tab-count { background: #2563eb; color: #fff; }

/* Search bar */
.rh-search-wrap {
    background: #f8fafd;
    border-bottom: 1px solid #e4e8f0;
    padding: 12px 24px;
}
.rh-search-input {
    width: 100%;
    max-width: 320px;
    padding: 8px 14px 8px 36px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    color: #1e293b;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 10px center;
    transition: border-color .15s, box-shadow .15s;
}
.rh-search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

/* Section headers */
.rh-section {
    margin-top: 28px;
}
.rh-section:first-child { margin-top: 4px; }
.rh-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.rh-section-icon {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.rh-section-name {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: .01em;
}
.rh-section-divider {
    flex: 1;
    height: 1px;
    background: #e4e8f0;
    margin-left: 4px;
}

/* Report cards grid */
.rh-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

/* Individual report card */
.rh-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px 42px 16px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s, transform .1s;
    position: relative;
    overflow: hidden;
}
.rh-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 3px 12px rgba(37,99,235,.10);
    transform: translateY(-1px);
    text-decoration: none;
    color: inherit;
}
.rh-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    margin-top: 1px;
}
.rh-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}
.rh-card-desc {
    font-size: 11.5px;
    color: #94a3b8;
    line-height: 1.45;
    margin-top: 3px;
}
.rh-card-arrow {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #d1d5db;
    font-size: 11px;
    transition: color .15s, right .1s;
}
.rh-card:hover .rh-card-arrow { color: #2563eb; right: 11px; }

/* Plan badges */
.rh-plan-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: .04em;
    position: absolute;
    top: 9px;
    right: 9px;
}
.rh-badge-pro  { background: #eff6ff; color: #1d4ed8; }
.rh-badge-ent  { background: #fdf4ff; color: #7c3aed; }

/* Hidden / empty */
.rh-card.rh-hidden       { display: none !important; }
.rh-section.rh-hidden    { display: none !important; }
.rh-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    font-size: 13px;
    display: none;
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
.pal-indigo { background: #eef2ff; color: #4f46e5; }
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

        <div style="background:#fff; border:1px solid #dee2e9; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.05); overflow:hidden;">

            {{-- Tab bar --}}
            <div class="rh-tab-bar">
                <button class="rh-tab active" data-tab="all">
                    <i class="fas fa-th-large" style="font-size:11px;"></i> All Reports
                    <span class="rh-tab-count" id="count-all">0</span>
                </button>
                <button class="rh-tab" data-tab="overview">
                    <i class="fas fa-tachometer-alt" style="font-size:11px;"></i> Business Overview
                    <span class="rh-tab-count" id="count-overview">0</span>
                </button>
                <button class="rh-tab" data-tab="owes">
                    <i class="fas fa-hand-holding-usd" style="font-size:11px;"></i> Who Owes You
                    <span class="rh-tab-count" id="count-owes">0</span>
                </button>
                <button class="rh-tab" data-tab="sales">
                    <i class="fas fa-shopping-bag" style="font-size:11px;"></i> Sales &amp; Purchases
                    <span class="rh-tab-count" id="count-sales">0</span>
                </button>
                <button class="rh-tab" data-tab="inventory">
                    <i class="fas fa-boxes" style="font-size:11px;"></i> Inventory
                    <span class="rh-tab-count" id="count-inventory">0</span>
                </button>
                <button class="rh-tab" data-tab="financial">
                    <i class="fas fa-university" style="font-size:11px;"></i> Financial
                    <span class="rh-tab-count" id="count-financial">0</span>
                </button>
            </div>

            {{-- Search --}}
            <div class="rh-search-wrap">
                <input type="text" id="rh-search" class="rh-search-input" placeholder="Search reports…" autocomplete="off">
            </div>

            {{-- Report sections --}}
            <div class="p-4">

                {{-- ══ 1. BUSINESS OVERVIEW ═════════════════════════════════════ --}}
                <div class="rh-section" data-section="overview">
                    <div class="rh-section-head">
                        <span class="rh-section-icon pal-blue"><i class="fas fa-tachometer-alt"></i></span>
                        <span class="rh-section-name">Business Overview</span>
                        <span class="rh-section-divider"></span>
                    </div>
                    <div class="rh-grid">

                        <a href="{{ route('reports.profit-loss') }}" class="rh-card" data-section="overview" data-tab="overview" data-keywords="profit loss p&l income statement net earnings">
                            <span class="rh-plan-badge rh-badge-pro"><i class="fas fa-star" style="font-size:8px;"></i> Pro</span>
                            <div class="rh-card-icon pal-blue"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <div class="rh-card-title">Profit &amp; Loss</div>
                                <div class="rh-card-desc">Net profit or loss — income vs expenses over a date range.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('balance-sheet') }}" class="rh-card" data-section="overview" data-tab="overview" data-keywords="balance sheet assets liabilities equity net worth">
                            <div class="rh-card-icon pal-teal"><i class="fas fa-landmark"></i></div>
                            <div>
                                <div class="rh-card-title">Balance Sheet</div>
                                <div class="rh-card-desc">Snapshot of assets, liabilities, and equity at a point in time.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.cash-flow') }}" class="rh-card" data-section="overview" data-tab="overview" data-keywords="cash flow liquidity inflows outflows">
                            <div class="rh-card-icon pal-green"><i class="fas fa-water"></i></div>
                            <div>
                                <div class="rh-card-title">Cash Flow</div>
                                <div class="rh-card-desc">Inflows and outflows of cash across operating activities.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.income') }}" class="rh-card" data-section="overview" data-tab="overview" data-keywords="income report earnings revenue streams">
                            <div class="rh-card-icon pal-green"><i class="fas fa-hand-holding-usd"></i></div>
                            <div>
                                <div class="rh-card-title">Income Report</div>
                                <div class="rh-card-desc">All income streams and total earnings over a selected period.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.expense') }}" class="rh-card" data-section="overview" data-tab="overview" data-keywords="expense report spending costs categories">
                            <div class="rh-card-icon pal-red"><i class="fas fa-receipt"></i></div>
                            <div>
                                <div class="rh-card-title">Expense Report</div>
                                <div class="rh-card-desc">All recorded expenses broken down by category and date.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                    </div>
                </div>

                {{-- ══ 2. WHO OWES YOU ══════════════════════════════════════════ --}}
                <div class="rh-section" data-section="owes">
                    <div class="rh-section-head">
                        <span class="rh-section-icon pal-amber"><i class="fas fa-user-clock"></i></span>
                        <span class="rh-section-name">Who Owes You</span>
                        <span class="rh-section-divider"></span>
                    </div>
                    <div class="rh-grid">

                        <a href="{{ route('reports.accounts-receivable') }}" class="rh-card" data-section="owes" data-tab="owes" data-keywords="accounts receivable debtors outstanding balance overdue aging">
                            <div class="rh-card-icon pal-amber"><i class="fas fa-user-clock"></i></div>
                            <div>
                                <div class="rh-card-title">Accounts Receivable</div>
                                <div class="rh-card-desc">Outstanding customer balances, aging buckets, and overdue invoices.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.payment') }}" class="rh-card" data-section="owes" data-tab="owes" data-keywords="payment report received collection status">
                            <div class="rh-card-icon pal-amber"><i class="fas fa-money-check-alt"></i></div>
                            <div>
                                <div class="rh-card-title">Payment Report</div>
                                <div class="rh-card-desc">All incoming payments received and outstanding collection status.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.payment-summary') }}" class="rh-card" data-section="owes" data-tab="owes" data-keywords="payment summary overview total revenue collected">
                            <div class="rh-card-icon pal-blue"><i class="fas fa-credit-card"></i></div>
                            <div>
                                <div class="rh-card-title">Payment Summary</div>
                                <div class="rh-card-desc">High-level view of all payments collected and totals by method.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.sales') }}" class="rh-card" data-section="owes" data-tab="owes" data-keywords="sales report unpaid partial invoices due balance">
                            <div class="rh-card-icon pal-indigo"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="rh-card-title">Sales Report</div>
                                <div class="rh-card-desc">Review invoices including paid, unpaid, and partial balances.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                    </div>
                </div>

                {{-- ══ 3. SALES & PURCHASES ═════════════════════════════════════ --}}
                <div class="rh-section" data-section="sales">
                    <div class="rh-section-head">
                        <span class="rh-section-icon pal-teal"><i class="fas fa-shopping-bag"></i></span>
                        <span class="rh-section-name">Sales &amp; Purchases</span>
                        <span class="rh-section-divider"></span>
                    </div>
                    <div class="rh-grid">

                        <a href="{{ route('reports.sales') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="sales report revenue transactions customers">
                            <div class="rh-card-icon pal-blue"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="rh-card-title">Sales Report</div>
                                <div class="rh-card-desc">All sales transactions, revenue totals, and customer activity.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.purchase') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="purchase report suppliers buying orders">
                            <div class="rh-card-icon pal-teal"><i class="fas fa-shopping-cart"></i></div>
                            <div>
                                <div class="rh-card-title">Purchase Report</div>
                                <div class="rh-card-desc">Purchases from suppliers — amounts spent and order history.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.quotation') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="quotation estimates quotes conversion">
                            <div class="rh-card-icon pal-slate"><i class="fas fa-file-contract"></i></div>
                            <div>
                                <div class="rh-card-title">Quotation Report</div>
                                <div class="rh-card-desc">Issued quotes, conversion rates, and pending estimates.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.sales-return') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="sales return credit notes refund returned items">
                            <div class="rh-card-icon pal-orange"><i class="fas fa-undo-alt"></i></div>
                            <div>
                                <div class="rh-card-title">Sales Return Report</div>
                                <div class="rh-card-desc">Returned items, credit notes issued, and refunded amounts.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('pos.reports') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="pos point of sale sold units gross value">
                            <div class="rh-card-icon pal-teal"><i class="fas fa-cash-register"></i></div>
                            <div>
                                <div class="rh-card-title">POS Sales Report</div>
                                <div class="rh-card-desc">Sold units, stock position and gross value from POS activity.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.tax-sales') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="tax sales vat gst collected compliance">
                            <span class="rh-plan-badge rh-badge-ent"><i class="fas fa-lock" style="font-size:8px;"></i> Ent</span>
                            <div class="rh-card-icon pal-amber"><i class="fas fa-percent"></i></div>
                            <div>
                                <div class="rh-card-title">Tax on Sales</div>
                                <div class="rh-card-desc">Taxes collected on all sales transactions for compliance filing.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.tax-purchase') }}" class="rh-card" data-section="sales" data-tab="sales" data-keywords="tax purchase vat gst paid input compliance">
                            <span class="rh-plan-badge rh-badge-ent"><i class="fas fa-lock" style="font-size:8px;"></i> Ent</span>
                            <div class="rh-card-icon pal-amber"><i class="fas fa-percentage"></i></div>
                            <div>
                                <div class="rh-card-title">Tax on Purchases</div>
                                <div class="rh-card-desc">Taxes paid on all purchase transactions for input tax claims.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                    </div>
                </div>

                {{-- ══ 4. INVENTORY ═════════════════════════════════════════════ --}}
                <div class="rh-section" data-section="inventory">
                    <div class="rh-section-head">
                        <span class="rh-section-icon pal-green"><i class="fas fa-boxes"></i></span>
                        <span class="rh-section-name">Inventory</span>
                        <span class="rh-section-divider"></span>
                    </div>
                    <div class="rh-grid">

                        <a href="{{ route('reports.stock') }}" class="rh-card" data-section="inventory" data-tab="inventory" data-keywords="stock report inventory levels quantities value">
                            <div class="rh-card-icon pal-green"><i class="fas fa-boxes"></i></div>
                            <div>
                                <div class="rh-card-title">Stock Report</div>
                                <div class="rh-card-desc">Current stock quantities, values, and product movement overview.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.low-stock') }}" class="rh-card" data-section="inventory" data-tab="inventory" data-keywords="low stock alert reorder threshold products">
                            <div class="rh-card-icon pal-red"><i class="fas fa-exclamation-triangle"></i></div>
                            <div>
                                <div class="rh-card-title">Low Stock Report</div>
                                <div class="rh-card-desc">Products running below reorder level that need restocking.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('pos.reports') }}" class="rh-card" data-section="inventory" data-tab="inventory" data-keywords="pos stock position sold units remaining">
                            <div class="rh-card-icon pal-teal"><i class="fas fa-cash-register"></i></div>
                            <div>
                                <div class="rh-card-title">POS Stock Movement</div>
                                <div class="rh-card-desc">Sold quantities and remaining stock per product from POS.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                    </div>
                </div>

                {{-- ══ 5. FINANCIAL ═════════════════════════════════════════════ --}}
                <div class="rh-section" data-section="financial">
                    <div class="rh-section-head">
                        <span class="rh-section-icon pal-purple"><i class="fas fa-university"></i></span>
                        <span class="rh-section-name">Financial Statements</span>
                        <span class="rh-section-divider"></span>
                    </div>
                    <div class="rh-grid">

                        <a href="{{ route('reports.profit-loss') }}" class="rh-card" data-section="financial" data-tab="financial" data-keywords="profit loss p&l statement net income">
                            <span class="rh-plan-badge rh-badge-pro"><i class="fas fa-star" style="font-size:8px;"></i> Pro</span>
                            <div class="rh-card-icon pal-blue"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <div class="rh-card-title">Profit &amp; Loss</div>
                                <div class="rh-card-desc">Net profit/loss across a date range — income vs expenses.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('balance-sheet') }}" class="rh-card" data-section="financial" data-tab="financial" data-keywords="balance sheet assets liabilities equity">
                            <div class="rh-card-icon pal-teal"><i class="fas fa-landmark"></i></div>
                            <div>
                                <div class="rh-card-title">Balance Sheet</div>
                                <div class="rh-card-desc">Assets, liabilities, and owner's equity at a point in time.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('reports.cash-flow') }}" class="rh-card" data-section="financial" data-tab="financial" data-keywords="cash flow statement liquidity operating">
                            <div class="rh-card-icon pal-green"><i class="fas fa-water"></i></div>
                            <div>
                                <div class="rh-card-title">Cash Flow</div>
                                <div class="rh-card-desc">Track cash inflows and outflows across operating activities.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        <a href="{{ route('trial-balance') }}" class="rh-card" data-section="financial" data-tab="financial" data-keywords="trial balance debits credits ledger accounts">
                            <div class="rh-card-icon pal-slate"><i class="fas fa-balance-scale"></i></div>
                            <div>
                                <div class="rh-card-title">Trial Balance</div>
                                <div class="rh-card-desc">Debit and credit totals for all accounts — verify accuracy.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>

                        @if(Route::has('general-ledger'))
                        <a href="{{ route('general-ledger') }}" class="rh-card" data-section="financial" data-tab="financial" data-keywords="general ledger accounts double entry transactions history">
                            <span class="rh-plan-badge rh-badge-ent"><i class="fas fa-lock" style="font-size:8px;"></i> Ent</span>
                            <div class="rh-card-icon pal-purple"><i class="fas fa-book-open"></i></div>
                            <div>
                                <div class="rh-card-title">General Ledger</div>
                                <div class="rh-card-desc">Full double-entry ledger with complete account transaction history.</div>
                            </div>
                            <i class="fas fa-chevron-right rh-card-arrow"></i>
                        </a>
                        @endif

                    </div>
                </div>

                {{-- Empty state --}}
                <div id="rh-empty-state" class="rh-empty">
                    <i class="fas fa-search fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                    No reports match your search.
                </div>

            </div>{{-- /.p-4 --}}
        </div>

    </div>{{-- /.content --}}
</div>

<script>
(function () {
    const tabs     = document.querySelectorAll('.rh-tab');
    const cards    = document.querySelectorAll('.rh-card');
    const sections = document.querySelectorAll('.rh-section');
    const search   = document.getElementById('rh-search');
    const empty    = document.getElementById('rh-empty-state');

    // Count by tab
    const tabCounts = {};
    cards.forEach(c => {
        const t = c.dataset.tab;
        tabCounts[t] = (tabCounts[t] || 0) + 1;
    });
    document.getElementById('count-all').textContent = cards.length;
    Object.entries(tabCounts).forEach(([t, n]) => {
        const el = document.getElementById('count-' + t);
        if (el) el.textContent = n;
    });

    let activeTab = 'all';

    function applyFilters() {
        const q = (search.value || '').toLowerCase().trim();
        let totalVisible = 0;

        cards.forEach(card => {
            const tabMatch = activeTab === 'all' || card.dataset.tab === activeTab;
            const haystack = ((card.dataset.keywords || '') + ' ' + (card.querySelector('.rh-card-title')?.textContent || '')).toLowerCase();
            const kwMatch  = !q || haystack.includes(q);
            const show     = tabMatch && kwMatch;
            card.classList.toggle('rh-hidden', !show);
            if (show) totalVisible++;
        });

        // Show/hide sections based on whether they have any visible cards
        sections.forEach(sec => {
            const secName  = sec.dataset.section;
            const secTab   = activeTab === 'all' ? null : activeTab;
            const visible  = sec.querySelectorAll(
                secTab
                    ? `.rh-card[data-section="${secName}"][data-tab="${secTab}"]:not(.rh-hidden)`
                    : `.rh-card[data-section="${secName}"]:not(.rh-hidden)`
            ).length;
            sec.classList.toggle('rh-hidden', visible === 0);
        });

        empty.style.display = totalVisible === 0 ? 'block' : 'none';
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
    applyFilters();
})();
</script>
@endsection
