@extends('layout.mainlayout')

@section('content')
<style>
/* ══════════════════════════════════════════════════════════
   Reports Hub  —  QuickBooks row-list style
   ══════════════════════════════════════════════════════════ */
.rh-page { background:#f1f4f9; min-height:100vh; }

/* ── Tab bar ──────────────────────────────────────────────── */
.rh-tab-bar{display:flex;gap:0;border-bottom:2px solid #dee2e9;background:#fff;padding:0 24px;overflow-x:auto;scrollbar-width:none;}
.rh-tab-bar::-webkit-scrollbar{display:none;}
.rh-tab{padding:13px 18px;font-size:12.5px;font-weight:600;color:#64748b;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;background:none;border-top:none;border-left:none;border-right:none;white-space:nowrap;display:flex;align-items:center;gap:6px;flex-shrink:0;transition:color .15s,border-color .15s;}
.rh-tab:hover{color:#1e3a5f;}
.rh-tab.active{color:#2563eb;border-bottom-color:#2563eb;}
.rh-cnt{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9px;background:#e8eef8;color:#2563eb;font-size:10px;font-weight:800;padding:0 4px;}
.rh-tab.active .rh-cnt{background:#2563eb;color:#fff;}

/* ── Search / toolbar ─────────────────────────────────────── */
.rh-toolbar{background:#fff;border-bottom:1px solid #e4e8f0;padding:10px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.rh-search-wrap{position:relative;flex:1;max-width:340px;}
.rh-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;}
.rh-search{width:100%;padding:7px 12px 7px 32px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;color:#1e293b;background:#fff;transition:border-color .15s,box-shadow .15s;}
.rh-search:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.rh-fav-toggle{padding:7px 14px;font-size:12px;font-weight:600;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap;}
.rh-fav-toggle.active,.rh-fav-toggle:hover{background:#fffbeb;border-color:#fbbf24;color:#d97706;}
.rh-fav-toggle.active .fav-star-icon{color:#f59e0b;}

/* ── Two-column list layout ───────────────────────────────── */
.rh-body{padding:20px 24px;}
.rh-section{margin-bottom:8px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;overflow:hidden;}
.rh-section.rh-hidden{display:none!important;}

/* Section header (collapsible) */
.rh-sec-head{display:flex;align-items:center;gap:10px;padding:11px 16px;cursor:pointer;user-select:none;border-bottom:1px solid #f1f4f9;transition:background .12s;}
.rh-sec-head:hover{background:#f8fafd;}
.rh-sec-icon{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
.rh-sec-title{font-size:13px;font-weight:800;color:#0f172a;flex:1;}
.rh-sec-count{font-size:11px;color:#94a3b8;font-weight:600;margin-right:6px;}
.rh-sec-chevron{color:#94a3b8;font-size:11px;transition:transform .2s;}
.rh-section.collapsed .rh-sec-chevron{transform:rotate(-90deg);}
.rh-section.collapsed .rh-col-grid{display:none;}

/* Two-column grid for rows */
.rh-col-grid{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid #f0f3f8;}
@media(max-width:700px){.rh-col-grid{grid-template-columns:1fr;}}

/* Individual report row */
.rl-row{display:flex;align-items:center;gap:0;padding:0;border-bottom:1px solid #f0f3f8;min-height:44px;position:relative;}
.rl-row:last-child{border-bottom:none;}
.rl-row.rh-hidden{display:none!important;}
/* Odd/even col separator */
.rh-col-grid .rl-row:nth-child(odd){border-right:1px solid #f0f3f8;}

/* Star favorite */
.rl-star{width:36px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:13px;transition:color .15s,transform .15s;padding:0;}
.rl-star:hover{color:#f59e0b;transform:scale(1.2);}
.rl-star.starred{color:#f59e0b;}
.rl-star.starred i::before{content:"\f005";font-weight:900;}/* solid star */

/* Report name link */
.rl-name{flex:1;font-size:12.5px;font-weight:600;color:#1e40af;text-decoration:none;padding:0 4px 0 0;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;transition:color .12s;}
.rl-name:hover{color:#1d4ed8;text-decoration:underline;}

/* Plan badge inline */
.rl-badge{display:inline-flex;align-items:center;gap:2px;padding:1px 5px;border-radius:3px;font-size:9px;font-weight:800;letter-spacing:.04em;margin-right:4px;flex-shrink:0;}
.rl-badge-pro{background:#eff6ff;color:#1d4ed8;}
.rl-badge-ent{background:#fdf4ff;color:#7c3aed;}

/* Run button */
.rl-run{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;font-size:11.5px;font-weight:700;color:#fff;background:#2563eb;border:none;border-radius:5px;cursor:pointer;text-decoration:none;transition:background .15s,box-shadow .15s;flex-shrink:0;margin-right:10px;white-space:nowrap;}
.rl-run:hover{background:#1d4ed8;box-shadow:0 2px 8px rgba(37,99,235,.3);color:#fff;text-decoration:none;}
.rl-run i{font-size:9px;}

/* Favourites tab — "Favourites" section  */
.rh-fav-section{margin-bottom:8px;border:1px solid #fde68a;border-radius:8px;background:#fffdf5;overflow:hidden;display:none;}
.rh-fav-section.has-favs{display:block;}
.rh-fav-section .rh-sec-head{border-bottom-color:#fef3c7;background:#fffbeb;}

/* Empty state */
.rh-empty{text-align:center;padding:40px 20px;color:#94a3b8;font-size:13px;display:none;}

/* Palette helpers */
.pal-blue{background:#eff6ff;color:#2563eb;}
.pal-green{background:#f0fdf4;color:#16a34a;}
.pal-orange{background:#fff7ed;color:#ea580c;}
.pal-purple{background:#fdf4ff;color:#7c3aed;}
.pal-red{background:#fef2f2;color:#dc2626;}
.pal-teal{background:#f0fdfa;color:#0d9488;}
.pal-amber{background:#fffbeb;color:#d97706;}
.pal-slate{background:#f8fafc;color:#475569;}
.pal-indigo{background:#eef2ff;color:#4f46e5;}
</style>

<div class="page-wrapper rh-page">
<div class="content container-fluid">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-0" style="gap:10px;padding-bottom:14px;">
        <div>
            <h4 class="fw-bold mb-1" style="color:#0f172a;font-size:20px;">Reports</h4>
            <div style="font-size:12.5px;color:#64748b;">Select a report to view or run. Star your favourites for quick access.</div>
        </div>
    </div>

    <div style="border:1px solid #dee2e9;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);overflow:hidden;">

        {{-- Tab bar --}}
        {{-- QB-style: 3 top-level tabs matching QuickBooks exactly --}}
        <div class="rh-tab-bar">
            <button class="rh-tab" data-tab="standard">Standard reports <span class="rh-cnt" id="cnt-standard">0</span></button>
            <button class="rh-tab" data-tab="management">Management reports <span class="rh-cnt" id="cnt-management">0</span></button>
            <button class="rh-tab" data-tab="custom">Custom reports <span class="rh-cnt" id="cnt-custom">0</span></button>
        </div>

        {{-- Toolbar: search + favourites toggle --}}
        <div class="rh-toolbar">
            <div class="rh-search-wrap">
                <i class="fas fa-search rh-search-icon"></i>
                <input type="text" id="rh-search" class="rh-search" placeholder="Type report name here…" autocomplete="off">
            </div>
            <button class="rh-fav-toggle" id="rh-fav-toggle" title="Show favourites only">
                <i class="far fa-star fav-star-icon"></i> Favourites
            </button>
        </div>

        {{-- Report list body --}}
        <div class="rh-body">

            {{-- ★ FAVOURITES (dynamic — shown when any starred) --}}
            <div class="rh-fav-section rh-section" id="favSection" data-section="favs">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-amber"><i class="fas fa-star"></i></span>
                    <span class="rh-sec-title">Favourites</span>
                    <span class="rh-sec-count" id="favCount">0 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid" id="favGrid">
                    {{-- populated by JS --}}
                </div>
            </div>

            {{-- ══ 1. BUSINESS OVERVIEW ════════════════════════════════════ --}}
            <div class="rh-section" data-section="overview">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-blue"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="rh-sec-title">Business Overview</span>
                    <span class="rh-sec-count">5 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="profit-loss" data-url="{{ route('reports.profit-loss') }}" data-keywords="profit loss p&l income statement net earnings">
                        <button class="rl-star" data-id="profit-loss" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.profit-loss') }}" class="rl-name">Profit &amp; Loss</a>
                        <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                        <a href="{{ route('reports.profit-loss') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="balance-sheet" data-url="{{ route('balance-sheet') }}" data-keywords="balance sheet assets liabilities equity net worth">
                        <button class="rl-star" data-id="balance-sheet" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('balance-sheet') }}" class="rl-name">Balance Sheet</a>
                        <a href="{{ route('balance-sheet') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="cash-flow" data-url="{{ route('reports.cash-flow') }}" data-keywords="cash flow liquidity inflows outflows">
                        <button class="rl-star" data-id="cash-flow" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.cash-flow') }}" class="rl-name">Cash Flow</a>
                        <a href="{{ route('reports.cash-flow') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="income-report" data-url="{{ route('reports.income') }}" data-keywords="income report earnings revenue streams">
                        <button class="rl-star" data-id="income-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.income') }}" class="rl-name">Income Report</a>
                        <a href="{{ route('reports.income') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="expense-report" data-url="{{ route('reports.expense') }}" data-keywords="expense report spending costs categories">
                        <button class="rl-star" data-id="expense-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.expense') }}" class="rl-name">Expense Report</a>
                        <a href="{{ route('reports.expense') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 2. WHO OWES YOU ════════════════════════════════════════ --}}
            <div class="rh-section" data-section="owes">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-amber"><i class="fas fa-user-clock"></i></span>
                    <span class="rh-sec-title">Who Owes You</span>
                    <span class="rh-sec-count">4 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="accounts-receivable" data-url="{{ route('reports.accounts-receivable') }}" data-keywords="accounts receivable debtors outstanding balance overdue aging">
                        <button class="rl-star" data-id="accounts-receivable" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.accounts-receivable') }}" class="rl-name">Accounts Receivable</a>
                        <a href="{{ route('reports.accounts-receivable') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="payment-report" data-url="{{ route('reports.payment') }}" data-keywords="payment report received collection status">
                        <button class="rl-star" data-id="payment-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.payment') }}" class="rl-name">Payment Report</a>
                        <a href="{{ route('reports.payment') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="payment-summary" data-url="{{ route('reports.payment-summary') }}" data-keywords="payment summary overview total revenue collected">
                        <button class="rl-star" data-id="payment-summary" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.payment-summary') }}" class="rl-name">Payment Summary</a>
                        <a href="{{ route('reports.payment-summary') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="sales-report-owes" data-url="{{ route('reports.sales') }}" data-keywords="sales report unpaid partial invoices due balance">
                        <button class="rl-star" data-id="sales-report-owes" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales') }}" class="rl-name">Sales Report</a>
                        <a href="{{ route('reports.sales') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 3. SALES & PURCHASES ══════════════════════════════════ --}}
            <div class="rh-section" data-section="sales">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-teal"><i class="fas fa-shopping-bag"></i></span>
                    <span class="rh-sec-title">Sales ;&amp; Purchases</span>
                    <span class="rh-sec-count">7 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="sales-report" data-url="{{ route('reports.sales') }}" data-keywords="sales report revenue transactions customers">
                        <button class="rl-star" data-id="sales-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales') }}" class="rl-name">Sales Report</a>
                        <a href="{{ route('reports.sales') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="purchase-report" data-url="{{ route('reports.purchase') }}" data-keywords="purchase report suppliers buying orders">
                        <button class="rl-star" data-id="purchase-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.purchase') }}" class="rl-name">Purchase Report</a>
                        <a href="{{ route('reports.purchase') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="quotation-report" data-url="{{ route('reports.quotation') }}" data-keywords="quotation estimates quotes conversion">
                        <button class="rl-star" data-id="quotation-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.quotation') }}" class="rl-name">Quotation Report</a>
                        <a href="{{ route('reports.quotation') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="sales-return" data-url="{{ route('reports.sales-return') }}" data-keywords="sales return credit notes refund returned items">
                        <button class="rl-star" data-id="sales-return" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales-return') }}" class="rl-name">Sales Return Report</a>
                        <a href="{{ route('reports.sales-return') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    @if(Route::has('reports.purchase-return'))
                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="purchase-return" data-url="{{ route('reports.purchase-return') }}" data-keywords="purchase return debit notes supplier refund returned goods">
                        <button class="rl-star" data-id="purchase-return" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.purchase-return') }}" class="rl-name">Purchase Return Report</a>
                        <a href="{{ route('reports.purchase-return') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>
                    @endif

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="pos-sales" data-url="{{ route('pos.reports') }}" data-keywords="pos point of sale sold units gross value">
                        <button class="rl-star" data-id="pos-sales" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('pos.reports') }}" class="rl-name">POS Sales Report</a>
                        <a href="{{ route('pos.reports') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="tax-sales" data-url="{{ route('reports.tax-sales') }}" data-keywords="tax sales vat gst collected compliance">
                        <button class="rl-star" data-id="tax-sales" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.tax-sales') }}" class="rl-name">Tax on Sales</a>
                        <span class="rl-badge rl-badge-ent"><i class="fas fa-lock" style="font-size:7px;"></i> Ent</span>
                        <a href="{{ route('reports.tax-sales') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="tax-purchase" data-url="{{ route('reports.tax-purchase') }}" data-keywords="tax purchase vat gst paid input compliance">
                        <button class="rl-star" data-id="tax-purchase" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.tax-purchase') }}" class="rl-name">Tax on Purchases</a>
                        <span class="rl-badge rl-badge-ent"><i class="fas fa-lock" style="font-size:7px;"></i> Ent</span>
                        <a href="{{ route('reports.tax-purchase') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 4. INVENTORY ══════════════════════════════════════════ --}}
            <div class="rh-section" data-section="inventory">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-green"><i class="fas fa-boxes"></i></span>
                    <span class="rh-sec-title">Inventory</span>
                    <span class="rh-sec-count">3 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

                    <div class="rl-row" data-section="inventory" data-tab="inventory" data-id="stock-report" data-url="{{ route('reports.stock') }}" data-keywords="stock report inventory levels quantities value">
                        <button class="rl-star" data-id="stock-report" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.stock') }}" class="rl-name">Stock Report</a>
                        <a href="{{ route('reports.stock') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="inventory" data-tab="inventory" data-id="low-stock" data-url="{{ route('reports.low-stock') }}" data-keywords="low stock alert reorder threshold products">
                        <button class="rl-star" data-id="low-stock" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.low-stock') }}" class="rl-name">Low Stock Report</a>
                        <a href="{{ route('reports.low-stock') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="inventory" data-tab="inventory" data-id="pos-stock" data-url="{{ route('pos.reports') }}" data-keywords="pos stock position sold units remaining movement">
                        <button class="rl-star" data-id="pos-stock" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('pos.reports') }}" class="rl-name">POS Stock Movement</a>
                        <a href="{{ route('pos.reports') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 5. FINANCIAL STATEMENTS ════════════════════════════════ --}}
            <div class="rh-section" data-section="financial">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-purple"><i class="fas fa-university"></i></span>
                    <span class="rh-sec-title">Financial Statements</span>
                    <span class="rh-sec-count">5 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="pl-financial" data-url="{{ route('reports.profit-loss') }}" data-keywords="profit loss p&l statement net income">
                        <button class="rl-star" data-id="pl-financial" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.profit-loss') }}" class="rl-name">Profit &amp; Loss</a>
                        <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                        <a href="{{ route('reports.profit-loss') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="bs-financial" data-url="{{ route('balance-sheet') }}" data-keywords="balance sheet assets liabilities equity">
                        <button class="rl-star" data-id="bs-financial" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('balance-sheet') }}" class="rl-name">Balance Sheet</a>
                        <a href="{{ route('balance-sheet') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="cf-financial" data-url="{{ route('reports.cash-flow') }}" data-keywords="cash flow statement liquidity operating">
                        <button class="rl-star" data-id="cf-financial" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.cash-flow') }}" class="rl-name">Cash Flow</a>
                        <a href="{{ route('reports.cash-flow') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="trial-balance" data-url="{{ route('trial-balance') }}" data-keywords="trial balance debits credits ledger accounts">
                        <button class="rl-star" data-id="trial-balance" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('trial-balance') }}" class="rl-name">Trial Balance</a>
                        <a href="{{ route('trial-balance') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    @if(Route::has('general-ledger'))
                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="general-ledger" data-url="{{ route('general-ledger') }}" data-keywords="general ledger accounts double entry transactions history">
                        <button class="rl-star" data-id="general-ledger" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('general-ledger') }}" class="rl-name">General Ledger</a>
                        <span class="rl-badge rl-badge-ent"><i class="fas fa-lock" style="font-size:7px;"></i> Ent</span>
                        <a href="{{ route('general-ledger') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>
                    @endif

                </div>
            </div>

            {{-- ══ 6. CUSTOM REPORTS ════════════════════════════════════ --}}
            <div class="rh-section" data-section="custom">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-indigo"><i class="fas fa-magic"></i></span>
                    <span class="rh-sec-title">Custom Reports</span>
                    <span class="rh-sec-count">Build your own</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">
                    <div class="rl-row" data-section="custom" data-id="my-favourites" data-keywords="starred favourites saved reports bookmarks">
                        <button class="rl-star" data-id="my-favourites" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="#" onclick="document.getElementById('rh-fav-toggle').click(); return false;" class="rl-name">My Favourites</a>
                        <a href="#" onclick="document.getElementById('rh-fav-toggle').click(); return false;" class="rl-run"><i class="fas fa-play"></i> View</a>
                    </div>
                </div>
            </div>

            {{-- Empty state --}}
            <div id="rh-empty" class="rh-empty">
                <i class="fas fa-search fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                No reports match your search.
            </div>

        </div>{{-- /.rh-body --}}
    </div>

</div>{{-- /.content --}}
</div>

<script>
(function () {
    /* ── Tab → section mapping ── */
    const TAB_SECTIONS = {
        standard:   ['overview', 'owes', 'sales', 'inventory'],
        management: ['financial'],
        custom:     ['custom'],
    };

    /* ── State ── */
    const FAV_KEY = 'rh_favourites_v2';
    let favs      = JSON.parse(localStorage.getItem(FAV_KEY) || '[]');

    const urlTab  = new URLSearchParams(location.search).get('tab') || 'standard';
    let activeTab = Object.keys(TAB_SECTIONS).includes(urlTab) ? urlTab : 'standard';
    let favsOnly  = false;

    const rows      = document.querySelectorAll('.rl-row');
    const sections  = document.querySelectorAll('.rh-section:not(#favSection)');
    const favSec    = document.getElementById('favSection');
    const favGrid   = document.getElementById('favGrid');
    const favCount  = document.getElementById('favCount');
    const search    = document.getElementById('rh-search');
    const emptyEl   = document.getElementById('rh-empty');
    const favToggle = document.getElementById('rh-fav-toggle');

    /* ── Activate correct tab on load ── */
    document.querySelectorAll('.rh-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === activeTab);
    });

    /* ── Tab counts ── */
    function updateCounts() {
        Object.entries(TAB_SECTIONS).forEach(([tab, secs]) => {
            let count = 0;
            rows.forEach(r => { if (secs.includes(r.dataset.section)) count++; });
            const el = document.getElementById('cnt-' + tab);
            if (el) el.textContent = count;
        });
    }
    updateCounts();

    /* ── Collapse / expand section ── */
    window.toggleSection = function (head) {
        head.closest('.rh-section').classList.toggle('collapsed');
    };

    /* ── Stars / favourites ── */
    function applyStars() {
        document.querySelectorAll('.rl-star').forEach(btn => {
            const starred = favs.includes(btn.dataset.id);
            btn.classList.toggle('starred', starred);
            btn.querySelector('i').className = starred ? 'fas fa-star' : 'far fa-star';
        });
    }

    function rebuildFavGrid() {
        favGrid.innerHTML = '';
        let count = 0;
        favs.forEach(id => {
            const orig = document.querySelector(`.rl-row[data-id="${id}"]`);
            if (!orig) return;
            const clone = orig.cloneNode(true);
            clone.dataset.section = 'favs';
            clone.querySelector('.rl-star').addEventListener('click', e => { e.preventDefault(); toggleFav(id); });
            favGrid.appendChild(clone);
            count++;
        });
        favCount.textContent = count + (count === 1 ? ' report' : ' reports');
        favSec.classList.toggle('has-favs', count > 0);
    }

    function toggleFav(id) {
        const idx = favs.indexOf(id);
        if (idx === -1) favs.push(id); else favs.splice(idx, 1);
        localStorage.setItem(FAV_KEY, JSON.stringify(favs));
        applyStars();
        rebuildFavGrid();
        applyFilters();
    }

    document.querySelectorAll('.rl-star').forEach(btn => {
        btn.addEventListener('click', e => { e.preventDefault(); toggleFav(btn.dataset.id); });
    });

    /* ── Favourites-only toggle ── */
    favToggle.addEventListener('click', () => {
        favsOnly = !favsOnly;
        favToggle.classList.toggle('active', favsOnly);
        favToggle.querySelector('i').className = favsOnly ? 'fas fa-star fav-star-icon' : 'far fa-star fav-star-icon';
        applyFilters();
    });

    /* ── Tab clicks ── */
    document.querySelectorAll('.rh-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.rh-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeTab = tab.dataset.tab;
            history.replaceState(null, '', '?tab=' + activeTab);
            applyFilters();
        });
    });

    /* ── Search ── */
    search.addEventListener('input', applyFilters);

    /* ── Master filter function ── */
    function applyFilters() {
        const q           = (search.value || '').toLowerCase().trim();
        const allowedSecs = TAB_SECTIONS[activeTab] || [];
        let totalVisible  = 0;

        rows.forEach(row => {
            const secOk = allowedSecs.includes(row.dataset.section);
            const hay   = ((row.dataset.keywords || '') + ' ' + (row.querySelector('.rl-name')?.textContent || '')).toLowerCase();
            const kwOk  = !q || hay.includes(q);
            const favOk = !favsOnly || favs.includes(row.dataset.id);
            const show  = secOk && kwOk && favOk;
            row.classList.toggle('rh-hidden', !show);
            if (show) totalVisible++;
        });

        sections.forEach(sec => {
            const secName  = sec.dataset.section;
            const secTabOk = allowedSecs.includes(secName);
            const vis      = sec.querySelectorAll(`.rl-row[data-section="${secName}"]:not(.rh-hidden)`).length;
            sec.classList.toggle('rh-hidden', !secTabOk || vis === 0);
        });

        if (favSec.classList.contains('has-favs')) {
            favGrid.querySelectorAll('.rl-row').forEach(r => {
                const origSec  = document.querySelector(`.rl-row[data-id="${r.dataset.id}"]:not([data-section="favs"])`)?.dataset.section;
                const favSecOk = !origSec || allowedSecs.includes(origSec);
                const hay      = ((r.dataset.keywords || '') + ' ' + (r.querySelector('.rl-name')?.textContent || '')).toLowerCase();
                const favKwOk  = !q || hay.includes(q);
                r.classList.toggle('rh-hidden', !(favSecOk && favKwOk));
            });
            const favVis = favGrid.querySelectorAll('.rl-row:not(.rh-hidden)').length;
            favSec.classList.toggle('rh-hidden', favVis === 0 && !favsOnly);
        } else {
            favSec.classList.add('rh-hidden');
        }

        emptyEl.style.display = totalVisible === 0 ? 'block' : 'none';
    }

    /* ── Init ── */
    applyStars();
    rebuildFavGrid();
    applyFilters();
})();
</script>
@endsection
