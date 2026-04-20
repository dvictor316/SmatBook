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
.rh-section.collapsed .rh-pl-grid{display:none;}

/* Two-column grid for rows */
.rh-col-grid{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid #f0f3f8;}
@media(max-width:700px){.rh-col-grid{grid-template-columns:1fr;}}

/* Profit & Loss card grid */
.rh-pl-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:14px;
    padding:16px;
    background:linear-gradient(180deg,#fbfdff 0%,#f7faff 100%);
}
.rh-pl-card{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    gap:10px;
    min-height:196px;
    padding:18px 18px 16px;
    border:1px solid #dbe7ff;
    border-radius:14px;
    background:#fff;
    box-shadow:0 10px 26px rgba(37,99,235,.08);
    position:relative;
    overflow:hidden;
}
.rh-pl-card::before{
    content:"";
    position:absolute;
    inset:0 auto auto 0;
    width:100%;
    height:4px;
    background:linear-gradient(90deg,#2563eb 0%,#60a5fa 100%);
}
.rh-pl-card .rl-star{
    position:absolute;
    top:12px;
    right:12px;
    width:30px;
    height:30px;
    border-radius:999px;
    background:#f8fbff;
    border:1px solid #e2e8f0;
}
.rh-pl-icon{
    width:42px;
    height:42px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
}
.rh-pl-kicker{
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#64748b;
}
.rh-pl-title{
    display:block;
    padding-right:30px;
    font-size:16px;
    font-weight:800;
    color:#0f172a;
    line-height:1.25;
    text-decoration:none;
}
.rh-pl-title:hover{color:#1d4ed8;text-decoration:none;}
.rh-pl-copy{
    font-size:12.5px;
    line-height:1.55;
    color:#64748b;
    margin:0;
}
.rh-pl-meta{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-top:auto;
}
.rh-pl-action{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 12px;
    border-radius:8px;
    background:#2563eb;
    color:#fff;
    text-decoration:none;
    font-size:12px;
    font-weight:700;
    box-shadow:0 8px 18px rgba(37,99,235,.2);
}
.rh-pl-action:hover{background:#1d4ed8;color:#fff;text-decoration:none;}
.rh-pl-card.is-comparison::before{background:linear-gradient(90deg,#7c3aed 0%,#a78bfa 100%);}
.rh-pl-card.is-monthly::before{background:linear-gradient(90deg,#0d9488 0%,#2dd4bf 100%);}
.rh-pl-card.is-detail::before{background:linear-gradient(90deg,#ea580c 0%,#fb923c 100%);}
.rh-pl-card.is-comparison{border-color:#e9d5ff;box-shadow:0 10px 26px rgba(124,58,237,.08);}
.rh-pl-card.is-monthly{border-color:#ccfbf1;box-shadow:0 10px 26px rgba(13,148,136,.08);}
.rh-pl-card.is-detail{border-color:#fed7aa;box-shadow:0 10px 26px rgba(234,88,12,.08);}
@media(max-width:700px){
    .rh-pl-grid{grid-template-columns:1fr;padding:12px;}
    .rh-pl-card{min-height:auto;}
}

/* Custom reports builder */
.rh-custom-builder{
    border-top:1px solid #eef2f7;
    padding:16px;
    background:#fbfcfe;
}
.rh-custom-shell{
    border:1px solid #dbe7ff;
    border-radius:12px;
    background:#fff;
    padding:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.05);
}
.rh-custom-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.rh-custom-title{font-size:14px;font-weight:800;color:#0f172a;margin:0;}
.rh-custom-copy{font-size:12px;color:#64748b;margin:4px 0 0;}
.rh-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.rh-form-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:6px;}
.rh-form-input,.rh-form-select{
    width:100%;
    border:1px solid #dbe2ea;
    border-radius:8px;
    padding:9px 11px;
    font-size:13px;
    color:#0f172a;
    background:#fff;
}
.rh-form-input:focus,.rh-form-select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.rh-custom-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;}
.rh-custom-note{font-size:11.5px;color:#64748b;}
.rh-custom-submit{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border:none;border-radius:8px;background:#2563eb;color:#fff;font-size:12px;font-weight:700;cursor:pointer;}
.rh-custom-submit:hover{background:#1d4ed8;}
.rl-inline-form{margin:0;}
.rl-run.rl-delete{background:#fff;color:#dc2626;border:1px solid #fecaca;box-shadow:none;}
.rl-run.rl-delete:hover{background:#fef2f2;color:#b91c1c;}
.rl-run.rl-secondary{background:#fff;color:#1d4ed8;border:1px solid #bfdbfe;box-shadow:none;}
.rl-run.rl-secondary:hover{background:#eff6ff;color:#1d4ed8;}
@media(max-width:900px){.rh-form-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:700px){.rh-form-grid{grid-template-columns:1fr;}.rh-custom-builder{padding:12px;}}

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

            {{-- ══ 1. PROFIT & LOSS ════════════════════════════════════ --}}
            <div class="rh-section" data-section="profit-loss">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-indigo"><i class="fas fa-chart-line"></i></span>
                    <span class="rh-sec-title">Profit &amp; Loss</span>
                    <span class="rh-sec-count">4 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-pl-grid">

                    <div class="rl-row rh-pl-card" data-section="profit-loss" data-tab="overview" data-id="profit-loss" data-url="{{ route('reports.profit-loss') }}" data-keywords="profit loss p&l income statement net earnings main statement">
                        <button class="rl-star" data-id="profit-loss" title="Favourite"><i class="far fa-star"></i></button>
                        <span class="rh-pl-icon pal-blue"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="rh-pl-kicker">Core Statement</span>
                        <a href="{{ route('reports.profit-loss') }}" class="rl-name rh-pl-title">Profit &amp; Loss</a>
                        <p class="rh-pl-copy">See revenue, expenses, and net profit in one clean statement for the selected period.</p>
                        <div class="rh-pl-meta">
                            <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                            <a href="{{ route('reports.profit-loss') }}" class="rh-pl-action"><i class="fas fa-play"></i> Run Report</a>
                        </div>
                    </div>

                    <div class="rl-row rh-pl-card is-comparison" data-section="profit-loss" data-tab="overview" data-id="pl-comparison" data-url="{{ route('reports.profit-loss-comparison') }}" data-keywords="profit loss comparison two periods compare p&l variance change">
                        <button class="rl-star" data-id="pl-comparison" title="Favourite"><i class="far fa-star"></i></button>
                        <span class="rh-pl-icon pal-purple"><i class="fas fa-code-compare"></i></span>
                        <span class="rh-pl-kicker">Period Analysis</span>
                        <a href="{{ route('reports.profit-loss-comparison') }}" class="rl-name rh-pl-title">P&amp;L Comparison</a>
                        <p class="rh-pl-copy">Compare two periods side by side to spot profit swings, margin pressure, and trend changes fast.</p>
                        <div class="rh-pl-meta">
                            <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                            <a href="{{ route('reports.profit-loss-comparison') }}" class="rh-pl-action"><i class="fas fa-play"></i> Run Report</a>
                        </div>
                    </div>

                    <div class="rl-row rh-pl-card is-monthly" data-section="profit-loss" data-tab="overview" data-id="pl-by-month" data-url="{{ route('reports.profit-loss-by-month') }}" data-keywords="profit loss monthly breakdown month by month 12 months p&l trend movement">
                        <button class="rl-star" data-id="pl-by-month" title="Favourite"><i class="far fa-star"></i></button>
                        <span class="rh-pl-icon pal-teal"><i class="fas fa-calendar-alt"></i></span>
                        <span class="rh-pl-kicker">Trend View</span>
                        <a href="{{ route('reports.profit-loss-by-month') }}" class="rl-name rh-pl-title">P&amp;L by Month</a>
                        <p class="rh-pl-copy">Track profit performance month by month and quickly read seasonal patterns across the year.</p>
                        <div class="rh-pl-meta">
                            <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                            <a href="{{ route('reports.profit-loss-by-month') }}" class="rh-pl-action"><i class="fas fa-play"></i> Run Report</a>
                        </div>
                    </div>

                    <div class="rl-row rh-pl-card is-detail" data-section="profit-loss" data-tab="overview" data-id="pl-detail" data-url="{{ route('reports.profit-loss-detail') }}" data-keywords="profit loss detail line items transactions income expense detailed breakdown">
                        <button class="rl-star" data-id="pl-detail" title="Favourite"><i class="far fa-star"></i></button>
                        <span class="rh-pl-icon pal-orange"><i class="fas fa-list-ul"></i></span>
                        <span class="rh-pl-kicker">Line Items</span>
                        <a href="{{ route('reports.profit-loss-detail') }}" class="rl-name rh-pl-title">P&amp;L Detail</a>
                        <p class="rh-pl-copy">Drill into the underlying income and expense lines when you need the story behind the totals.</p>
                        <div class="rh-pl-meta">
                            <span class="rl-badge rl-badge-pro"><i class="fas fa-star" style="font-size:7px;"></i> Pro</span>
                            <a href="{{ route('reports.profit-loss-detail') }}" class="rh-pl-action"><i class="fas fa-play"></i> Run Report</a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ══ 2. BUSINESS OVERVIEW ════════════════════════════════════ --}}
            <div class="rh-section" data-section="overview">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-blue"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="rh-sec-title">Business Overview</span>
                    <span class="rh-sec-count">6 reports</span>
                    <i class="fas fa-chevron-down rh-sec-chevron"></i>
                </div>
                <div class="rh-col-grid">

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

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="expense-by-category" data-url="{{ route('reports.expense-by-category') }}" data-keywords="expense category breakdown spending type chart">
                        <button class="rl-star" data-id="expense-by-category" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.expense-by-category') }}" class="rl-name">Expenses by Category</a>
                        <a href="{{ route('reports.expense-by-category') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="overview" data-tab="overview" data-id="expense-trend" data-url="{{ route('reports.expense-trend') }}" data-keywords="expense trend monthly annual bar chart year">
                        <button class="rl-star" data-id="expense-trend" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.expense-trend') }}" class="rl-name">Expense Trend</a>
                        <a href="{{ route('reports.expense-trend') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 2. WHO OWES YOU ════════════════════════════════════════ --}}
            <div class="rh-section" data-section="owes">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-amber"><i class="fas fa-user-clock"></i></span>
                    <span class="rh-sec-title">Who Owes You</span>
                    <span class="rh-sec-count">6 reports</span>
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

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="ar-ageing-detail" data-url="{{ route('reports.ar-ageing-detail') }}" data-keywords="accounts receivable ageing detail bucket 0-30 31-60 61-90 overdue">
                        <button class="rl-star" data-id="ar-ageing-detail" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.ar-ageing-detail') }}" class="rl-name">AR Ageing Detail</a>
                        <a href="{{ route('reports.ar-ageing-detail') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="owes" data-tab="owes" data-id="open-invoices" data-url="{{ route('reports.open-invoices') }}" data-keywords="open invoices unpaid partial outstanding due customers">
                        <button class="rl-star" data-id="open-invoices" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.open-invoices') }}" class="rl-name">Open Invoices</a>
                        <a href="{{ route('reports.open-invoices') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 3. SALES & PURCHASES ══════════════════════════════════ --}}
            <div class="rh-section" data-section="sales">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-teal"><i class="fas fa-shopping-bag"></i></span>
                    <span class="rh-sec-title">Sales &amp; Purchases</span>
                    <span class="rh-sec-count">12 reports</span>
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

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="sales-by-customer" data-url="{{ route('reports.sales-by-customer') }}" data-keywords="sales by customer revenue per customer breakdown">
                        <button class="rl-star" data-id="sales-by-customer" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales-by-customer') }}" class="rl-name">Sales by Customer</a>
                        <a href="{{ route('reports.sales-by-customer') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="sales-by-product" data-url="{{ route('reports.sales-by-product') }}" data-keywords="sales by product item revenue qty sold breakdown">
                        <button class="rl-star" data-id="sales-by-product" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales-by-product') }}" class="rl-name">Sales by Product</a>
                        <a href="{{ route('reports.sales-by-product') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="sales-summary" data-url="{{ route('reports.sales-summary') }}" data-keywords="sales summary totals kpi overview period status">
                        <button class="rl-star" data-id="sales-summary" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.sales-summary') }}" class="rl-name">Sales Summary</a>
                        <a href="{{ route('reports.sales-summary') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="purchase-by-supplier" data-url="{{ route('reports.purchase-by-supplier') }}" data-keywords="purchases by supplier vendor breakdown orders">
                        <button class="rl-star" data-id="purchase-by-supplier" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.purchase-by-supplier') }}" class="rl-name">Purchases by Supplier</a>
                        <a href="{{ route('reports.purchase-by-supplier') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="sales" data-tab="sales" data-id="purchase-summary" data-url="{{ route('reports.purchase-summary') }}" data-keywords="purchase summary total orders average cost kpi">
                        <button class="rl-star" data-id="purchase-summary" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.purchase-summary') }}" class="rl-name">Purchase Summary</a>
                        <a href="{{ route('reports.purchase-summary') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 4. INVENTORY ══════════════════════════════════════════ --}}
            <div class="rh-section" data-section="inventory">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-green"><i class="fas fa-boxes"></i></span>
                    <span class="rh-sec-title">Inventory</span>
                    <span class="rh-sec-count">5 reports</span>
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

                    <div class="rl-row" data-section="inventory" data-tab="inventory" data-id="stock-valuation" data-url="{{ route('reports.stock-valuation') }}" data-keywords="stock valuation inventory value cost price total worth">
                        <button class="rl-star" data-id="stock-valuation" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.stock-valuation') }}" class="rl-name">Stock Valuation</a>
                        <a href="{{ route('reports.stock-valuation') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="inventory" data-tab="inventory" data-id="stock-by-category" data-url="{{ route('reports.stock-by-category') }}" data-keywords="stock by category inventory group product type">
                        <button class="rl-star" data-id="stock-by-category" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.stock-by-category') }}" class="rl-name">Stock by Category</a>
                        <a href="{{ route('reports.stock-by-category') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                </div>
            </div>

            {{-- ══ 5. FINANCIAL STATEMENTS ════════════════════════════════ --}}
            <div class="rh-section" data-section="financial">
                <div class="rh-sec-head" onclick="toggleSection(this)">
                    <span class="rh-sec-icon pal-purple"><i class="fas fa-university"></i></span>
                    <span class="rh-sec-title">Financial Statements</span>
                    <span class="rh-sec-count">8 reports</span>
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

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="bs-summary" data-url="{{ route('balance-sheet-summary') }}" data-keywords="balance sheet summary kpi totals net worth snapshot">
                        <button class="rl-star" data-id="bs-summary" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('balance-sheet-summary') }}" class="rl-name">Balance Sheet Summary</a>
                        <a href="{{ route('balance-sheet-summary') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="bs-comparison" data-url="{{ route('balance-sheet-comparison') }}" data-keywords="balance sheet comparison two dates period change variance">
                        <button class="rl-star" data-id="bs-comparison" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('balance-sheet-comparison') }}" class="rl-name">Balance Sheet Comparison</a>
                        <a href="{{ route('balance-sheet-comparison') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                    </div>

                    <div class="rl-row" data-section="financial" data-tab="financial" data-id="tax-summary" data-url="{{ route('reports.tax-summary') }}" data-keywords="tax summary output input net liability vat gst">
                        <button class="rl-star" data-id="tax-summary" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.tax-summary') }}" class="rl-name">Tax Summary</a>
                        <a href="{{ route('reports.tax-summary') }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
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
                    @foreach(($customReportTemplates ?? collect()) as $template)
                    <div class="rl-row" data-section="custom" data-id="custom-template-{{ $template['id'] }}" data-keywords="custom report template {{ strtolower($template['name']) }} {{ strtolower($template['report_label']) }} {{ strtolower($template['branch_scope']) }}">
                        <button class="rl-star" data-id="custom-template-{{ $template['id'] }}" title="Favourite"><i class="far fa-star"></i></button>
                        <a href="{{ route('reports.custom.run', $template['id']) }}" class="rl-name">{{ $template['name'] }} <span style="color:#94a3b8;font-weight:500;">• {{ $template['report_label'] }}</span></a>
                        <a href="{{ route('reports.custom.run', $template['id']) }}" class="rl-run"><i class="fas fa-play"></i> Run</a>
                        <a href="{{ route('reports.hub', ['tab' => 'custom', 'edit_template' => $template['id']]) }}" class="rl-run rl-secondary"><i class="fas fa-pen"></i> Edit</a>
                        <form method="POST" action="{{ route('reports.custom.duplicate', $template['id']) }}" class="rl-inline-form">
                            @csrf
                            <button type="submit" class="rl-run rl-secondary"><i class="fas fa-copy"></i> Duplicate</button>
                        </form>
                        <form method="POST" action="{{ route('reports.custom.destroy', $template['id']) }}" class="rl-inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rl-run rl-delete"><i class="fas fa-trash-alt"></i> Delete</button>
                        </form>
                    </div>
                    @endforeach
                </div>
                <div class="rh-custom-builder">
                    <div class="rh-custom-shell">
                        <div class="rh-custom-head">
                            <div>
                                <p class="rh-custom-title">{{ !empty($editingTemplate) ? 'Edit saved report template' : 'Save a reusable report template' }}</p>
                                <p class="rh-custom-copy">{{ !empty($editingTemplate) ? 'Update this template and keep using it from the custom reports list.' : 'Pick a report, set the date logic, choose branch scope, and launch it any time from this tab.' }}</p>
                            </div>
                            @if(!empty($editingTemplate))
                                <a href="{{ route('reports.hub', ['tab' => 'custom']) }}" class="rl-run rl-secondary"><i class="fas fa-times"></i> Cancel Edit</a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('reports.custom.store') }}">
                            @csrf
                            <input type="hidden" name="edit_id" value="{{ $editingTemplate['id'] ?? '' }}">
                            <div class="rh-form-grid">
                                <div class="rh-form-field">
                                    <label>Template Name</label>
                                    <input type="text" name="name" class="rh-form-input" value="{{ old('name', $editingTemplate['name'] ?? '') }}" placeholder="Monthly performance pack" required>
                                </div>
                                <div class="rh-form-field">
                                    <label>Report Type</label>
                                    <select name="report_key" class="rh-form-select" required>
                                        <option value="">Select report...</option>
                                        @foreach(($customReportCatalog ?? []) as $reportKey => $definition)
                                            <option value="{{ $reportKey }}" {{ old('report_key', $editingTemplate['report_key'] ?? '') === $reportKey ? 'selected' : '' }}>{{ $definition['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rh-form-field">
                                    <label>Branch Scope</label>
                                    <select name="branch_scope" class="rh-form-select" required>
                                        <option value="current" {{ old('branch_scope', $editingTemplate['branch_scope'] ?? 'current') === 'current' ? 'selected' : '' }}>Current branch only</option>
                                        <option value="all" {{ old('branch_scope', $editingTemplate['branch_scope'] ?? 'current') === 'all' ? 'selected' : '' }}>All branches</option>
                                    </select>
                                </div>
                                <div class="rh-form-field">
                                    <label>Date Preset</label>
                                    <select name="date_preset" class="rh-form-select" required>
                                        <option value="current_month" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'current_month' ? 'selected' : '' }}>Current month</option>
                                        <option value="today" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'today' ? 'selected' : '' }}>Today</option>
                                        <option value="last_7_days" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'last_7_days' ? 'selected' : '' }}>Last 7 days</option>
                                        <option value="last_30_days" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'last_30_days' ? 'selected' : '' }}>Last 30 days</option>
                                        <option value="last_month" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'last_month' ? 'selected' : '' }}>Last month</option>
                                        <option value="current_year" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'current_year' ? 'selected' : '' }}>Current year</option>
                                        <option value="custom" {{ old('date_preset', $editingTemplate['date_preset'] ?? 'current_month') === 'custom' ? 'selected' : '' }}>Custom range</option>
                                    </select>
                                </div>
                                <div class="rh-form-field">
                                    <label>Custom Start</label>
                                    <input type="date" name="custom_from_date" class="rh-form-input" value="{{ old('custom_from_date', $editingTemplate['custom_from_date'] ?? '') }}">
                                </div>
                                <div class="rh-form-field">
                                    <label>Custom End</label>
                                    <input type="date" name="custom_to_date" class="rh-form-input" value="{{ old('custom_to_date', $editingTemplate['custom_to_date'] ?? '') }}">
                                </div>
                            </div>
                            <div class="rh-custom-actions">
                                <div class="rh-custom-note">Use custom dates only when the preset is set to custom. Snapshot reports will use the end date as the “as of” date.</div>
                                <button type="submit" class="rh-custom-submit"><i class="fas fa-save"></i> {{ !empty($editingTemplate) ? 'Update Template' : 'Save Template' }}</button>
                            </div>
                        </form>
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
        standard:   ['profit-loss', 'overview', 'owes', 'sales', 'inventory'],
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
