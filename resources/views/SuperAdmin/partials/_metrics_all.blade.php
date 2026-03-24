@php
    $companySubscription = optional($company)->subscription;
    $plan = strtolower(
        optional($companySubscription)->plan_name
        ?? optional($companySubscription)->plan
        ?? (optional($company)->plan ?? 'basic')
    );
    $isBasic = ($plan === 'basic');
@endphp

<style>
    :root {
        --metric-blue-start: #2563eb;
        --metric-blue-end: #1d4ed8;
        --metric-green-start: #10b981;
        --metric-green-end: #059669;
        --metric-orange-start: #f97316;
        --metric-orange-end: #ea580c;
        --metric-slate-start: #0f172a;
        --metric-slate-end: #1e293b;
    }

    .metric-glass {
        border: none;
        border-radius: 22px;
        background: linear-gradient(160deg, rgba(255,255,255,0.98) 0%, rgba(248,251,255,0.96) 100%);
        box-shadow: 0 18px 40px rgba(0, 35, 71, 0.08);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .metric-glass:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 35, 71, 0.1);
    }

    .metric-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .metric-glass::after {
        content: '';
        position: absolute;
        inset: auto -30px -42px auto;
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.24);
        filter: blur(6px);
        pointer-events: none;
    }

    .metric-glass.metric-theme-primary {
        background:
            radial-gradient(circle at top right, rgba(37,99,235,0.10), transparent 42%),
            linear-gradient(180deg, #ffffff 0%, #eef5ff 100%) !important;
        color: #0f172a !important;
    }

    .metric-glass.metric-theme-success {
        background:
            radial-gradient(circle at top right, rgba(16,185,129,0.10), transparent 42%),
            linear-gradient(180deg, #ffffff 0%, #eefcf6 100%) !important;
        color: #0f172a !important;
    }

    .metric-glass.metric-theme-danger {
        background:
            radial-gradient(circle at top right, rgba(249,115,22,0.10), transparent 42%),
            linear-gradient(180deg, #ffffff 0%, #fff5ed 100%) !important;
        color: #0f172a !important;
    }

    .metric-glass.metric-theme-dark {
        background:
            radial-gradient(circle at top right, rgba(15,23,42,0.08), transparent 42%),
            linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%) !important;
        color: #0f172a !important;
    }

    .metric-glass.metric-theme-primary small,
    .metric-glass.metric-theme-success small,
    .metric-glass.metric-theme-danger small,
    .metric-glass.metric-theme-dark small,
    .metric-glass.metric-theme-primary h4,
    .metric-glass.metric-theme-success h4,
    .metric-glass.metric-theme-danger h4,
    .metric-glass.metric-theme-dark h4 {
        color: #0f172a !important;
    }

    .metric-glass.metric-theme-primary .text-muted,
    .metric-glass.metric-theme-success .text-muted,
    .metric-glass.metric-theme-danger .text-muted,
    .metric-glass.metric-theme-dark .text-muted {
        color: #64748b !important;
    }

    .metric-glass.metric-theme-primary .metric-icon-box,
    .metric-glass.metric-theme-success .metric-icon-box,
    .metric-glass.metric-theme-danger .metric-icon-box,
    .metric-glass.metric-theme-dark .metric-icon-box {
        border: none;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }

    .metric-glass.metric-theme-primary .metric-icon-box {
        background: linear-gradient(135deg, var(--metric-blue-start) 0%, var(--metric-blue-end) 100%) !important;
        color: #fff !important;
    }

    .metric-glass.metric-theme-success .metric-icon-box {
        background: linear-gradient(135deg, var(--metric-green-start) 0%, var(--metric-green-end) 100%) !important;
        color: #fff !important;
    }

    .metric-glass.metric-theme-danger .metric-icon-box {
        background: linear-gradient(135deg, var(--metric-orange-start) 0%, var(--metric-orange-end) 100%) !important;
        color: #fff !important;
    }

    .metric-glass.metric-theme-dark .metric-icon-box {
        background: linear-gradient(135deg, var(--metric-slate-start) 0%, var(--metric-slate-end) 100%) !important;
        color: #fff !important;
    }

    .locked-blur {
        filter: blur(4px);
        user-select: none;
    }

    .lock-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        border-radius: 20px;
    }
</style>

<div class="row g-4">
    {{-- 1. Today's Revenue --}}
    <div class="col-md-3">
        <div class="card metric-glass metric-theme-primary p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon-box">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Today's Revenue</small>
                    <h4 class="fw-bold mb-0">₦{{ number_format($metrics['todayRevenue'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Total Profit (Pro/Enterprise Only) --}}
    <div class="col-md-3">
        <div class="card metric-glass metric-theme-success p-3">
            @if($isBasic)
                <div class="lock-overlay">
                    <a href="{{ route('membership-plans') }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.6rem;">UPGRADE</a>
                </div>
            @endif
            <div class="d-flex align-items-center gap-3 {{ $isBasic ? 'locked-blur' : '' }}">
                <div class="metric-icon-box">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Profit</small>
                    <h4 class="fw-bold mb-0">₦{{ number_format($metrics['totalProfit'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Total Expenses (Pro/Enterprise Only) --}}
    <div class="col-md-3">
        <div class="card metric-glass metric-theme-danger p-3">
            @if($isBasic)
                <div class="lock-overlay">
                    <i class="fas fa-lock text-muted opacity-50"></i>
                </div>
            @endif
            <div class="d-flex align-items-center gap-3 {{ $isBasic ? 'locked-blur' : '' }}">
                <div class="metric-icon-box">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Expenses</small>
                    <h4 class="fw-bold mb-0">₦{{ number_format($metrics['totalExpenses'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Inventory Value --}}
    <div class="col-md-3">
        <div class="card metric-glass metric-theme-dark p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon-box">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Inventory Items</small>
                    <h4 class="fw-bold mb-0">{{ number_format($metrics['activeStock'] ?? $metrics['activeCompanies']) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
