@extends('layout.mainlayout')

@section('content')
<style>
    .starter-dashboard {
        margin-left: var(--sb-sidebar-w, 270px);
        width: calc(100% - var(--sb-sidebar-w, 270px));
        min-height: 100vh;
        padding: 28px 24px 36px;
        background:
            radial-gradient(1100px 280px at 10% 0%, rgba(15, 58, 138, 0.10) 0%, rgba(15, 58, 138, 0) 55%),
            linear-gradient(180deg, #f7faff 0%, #ffffff 68%);
        margin-top: 34px;
    }

    body.mini-sidebar .starter-dashboard,
    body.sidebar-icon-only .starter-dashboard {
        margin-left: var(--sb-sidebar-collapsed, 80px);
        width: calc(100% - var(--sb-sidebar-collapsed, 80px));
    }

    @media (max-width: 991.98px) {
        .starter-dashboard {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 18px 14px 28px;
            margin-top: 18px;
        }
    }

    .starter-shell {
        display: grid;
        gap: 18px;
    }

    .starter-hero {
        border: 1px solid #d8e3f5;
        border-radius: 22px;
        padding: 24px;
        background: linear-gradient(135deg, #061a44 0%, #0f3a8a 100%);
        color: #fff;
        box-shadow: 0 24px 40px -34px rgba(6, 26, 68, 0.72);
    }

    .starter-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .starter-title {
        margin: 14px 0 8px;
        font-size: clamp(1.5rem, 3vw, 2.1rem);
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .starter-copy {
        margin: 0;
        max-width: 760px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.96rem;
    }

    .starter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .starter-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 15px;
        border-radius: 14px;
        background: #ffffff;
        color: #0f3a8a;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.88rem;
        border: 1px solid rgba(255,255,255,0.16);
    }

    .starter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .starter-card {
        border: 1px solid #d8e3f5;
        border-radius: 18px;
        background: #fff;
        padding: 18px;
        box-shadow: 0 18px 28px -30px rgba(6, 26, 68, 0.65);
    }

    .starter-label {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 8px;
    }

    .starter-value {
        color: #061a44;
        font-size: 1.8rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 4px;
    }

    .starter-meta {
        color: #64748b;
        font-size: 0.85rem;
        margin: 0;
    }

    .starter-split {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 18px;
    }

    .starter-list {
        display: grid;
        gap: 12px;
    }

    .starter-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px solid #eef4ff;
    }

    .starter-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .starter-row-title {
        color: #0f172a;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .starter-row-sub {
        color: #64748b;
        font-size: 0.82rem;
    }

    .starter-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eff6ff;
        color: #0f3a8a;
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .starter-empty {
        color: #94a3b8;
        font-size: 0.9rem;
        padding: 6px 0 2px;
    }

    @media (max-width: 1200px) {
        .starter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .starter-split {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .starter-grid {
            grid-template-columns: 1fr;
        }

        .starter-actions {
            flex-direction: column;
        }
    }
</style>

@php
    $recentSales = collect($latestInvoices ?? collect())->take(6);
    $topSelling = collect($topProducts ?? [])->take(6);
    $lowStockItems = collect($lowStockProducts ?? collect())->take(6);
@endphp

<div class="starter-dashboard">
    <div class="starter-shell">
        @include('SuperAdmin.partials._subscription_status_banner')

        <section class="starter-hero">
            <span class="starter-badge"><i class="fas fa-cash-register"></i> Starter POS Dashboard</span>
            <h1 class="starter-title">Sales and inventory, without the accounting noise.</h1>
            <p class="starter-copy">This workspace is optimized for cashiers and inventory operators. You can process sales, manage products, track stock, monitor movement, and review the reports included in your STARTER plan.</p>
            <div class="starter-actions">
                <a href="{{ route('sales.showPos') }}" class="starter-action"><i class="fas fa-plus-circle"></i> New Sale</a>
                <a href="{{ route('product-list') }}" class="starter-action"><i class="fas fa-box"></i> Products</a>
                <a href="{{ route('inventory.Products') }}" class="starter-action"><i class="fas fa-warehouse"></i> Inventory</a>
                <a href="{{ route('customers.index') }}" class="starter-action"><i class="fas fa-users"></i> Customers</a>
                <a href="{{ route('reports.hub') }}" class="starter-action"><i class="fas fa-chart-line"></i> Reports</a>
            </div>
        </section>

        <section class="starter-grid">
            <article class="starter-card">
                <div class="starter-label">Today's Sales</div>
                <div class="starter-value">₦{{ number_format((float) ($metrics['todayRevenue'] ?? 0), 0) }}</div>
                <p class="starter-meta">Live sales captured today in the current tenant scope.</p>
            </article>
            <article class="starter-card">
                <div class="starter-label">Total Transactions</div>
                <div class="starter-value">{{ number_format((int) ($metrics['totalOrders'] ?? 0)) }}</div>
                <p class="starter-meta">Completed POS transactions for today.</p>
            </article>
            <article class="starter-card">
                <div class="starter-label">Low Stock Products</div>
                <div class="starter-value">{{ number_format((int) ($metrics['lowStockCount'] ?? 0)) }}</div>
                <p class="starter-meta">Items close to depletion and needing attention.</p>
            </article>
            <article class="starter-card">
                <div class="starter-label">Inventory Summary</div>
                <div class="starter-value">{{ number_format((float) ($metrics['activeStock'] ?? 0), 0) }}</div>
                <p class="starter-meta">Units on hand. Value: ₦{{ number_format((float) ($metrics['inventoryValue'] ?? 0), 0) }}</p>
            </article>
        </section>

        <section class="starter-split">
            <article class="starter-card">
                <div class="starter-label">Recent Sales</div>
                <div class="starter-list">
                    @forelse($recentSales as $sale)
                        <div class="starter-row">
                            <div>
                                <div class="starter-row-title">{{ $sale->invoice_no ?? $sale->order_no ?? ('Sale #' . $sale->id) }}</div>
                                <div class="starter-row-sub">{{ $sale->customer->customer_name ?? $sale->customer->name ?? 'Walk-in Customer' }}</div>
                            </div>
                            <span class="starter-pill">₦{{ number_format((float) ($sale->total ?? 0), 0) }}</span>
                        </div>
                    @empty
                        <div class="starter-empty">No recent sales yet.</div>
                    @endforelse
                </div>
            </article>

            <article class="starter-card">
                <div class="starter-label">Top Selling Products</div>
                <div class="starter-list">
                    @forelse($topSelling as $product)
                        <div class="starter-row">
                            <div>
                                <div class="starter-row-title">{{ $product['name'] ?? $product->name ?? 'Product' }}</div>
                                <div class="starter-row-sub">Best seller in current scope</div>
                            </div>
                            <span class="starter-pill">{{ number_format((float) ($product['total_qty'] ?? $product->total_qty ?? 0), 0) }} sold</span>
                        </div>
                    @empty
                        <div class="starter-empty">No product sales data yet.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="starter-split">
            <article class="starter-card">
                <div class="starter-label">Low Stock Products</div>
                <div class="starter-list">
                    @forelse($lowStockItems as $product)
                        <div class="starter-row">
                            <div>
                                <div class="starter-row-title">{{ $product->name ?? 'Product' }}</div>
                                <div class="starter-row-sub">{{ $product->category->name ?? 'Uncategorized' }}</div>
                            </div>
                            <span class="starter-pill">{{ number_format((float) ($product->available_stock ?? $product->stock ?? 0), 0) }} left</span>
                        </div>
                    @empty
                        <div class="starter-empty">Inventory levels look healthy right now.</div>
                    @endforelse
                </div>
            </article>

            <article class="starter-card">
                <div class="starter-label">Inventory At A Glance</div>
                <div class="starter-list">
                    <div class="starter-row">
                        <div>
                            <div class="starter-row-title">Products in catalog</div>
                            <div class="starter-row-sub">All products available to this tenant or branch.</div>
                        </div>
                        <span class="starter-pill">{{ number_format(collect($topProducts ?? [])->count()) }}</span>
                    </div>
                    <div class="starter-row">
                        <div>
                            <div class="starter-row-title">Items sold today</div>
                            <div class="starter-row-sub">Units moved through POS today.</div>
                        </div>
                        <span class="starter-pill">{{ number_format((float) ($metrics['itemsSoldToday'] ?? 0), 0) }}</span>
                    </div>
                    <div class="starter-row">
                        <div>
                            <div class="starter-row-title">Customers</div>
                            <div class="starter-row-sub">Active customer records in this workspace.</div>
                        </div>
                        <span class="starter-pill">{{ number_format((int) ($metrics['activeCustomers'] ?? 0)) }}</span>
                    </div>
                    <div class="starter-row">
                        <div>
                            <div class="starter-row-title">Current plan</div>
                            <div class="starter-row-sub">{{ $currentSubscription?->planLabel() ?? 'STARTER' }} · {{ ucfirst(strtolower((string) ($currentSubscription?->billing_cycle ?? 'monthly'))) }}</div>
                        </div>
                        <a href="{{ route('membership-plans') }}" class="starter-pill text-decoration-none">Manage Billing</a>
                    </div>
                </div>
            </article>
        </section>
    </div>
</div>
@endsection
