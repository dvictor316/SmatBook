@php
    $plan = strtolower(
        optional($company->subscription)->plan_name
        ?? optional($company->subscription)->plan
        ?? ($company->plan ?? 'basic')
    );
    $isBasic = ($plan === 'basic');
@endphp

<style>
    .metric-glass {
        border: none;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 35, 71, 0.05);
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
        <div class="card metric-glass p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Today's Revenue</small>
                    <h4 class="fw-bold mb-0" style="color: var(--deep-sapphire);">₦{{ number_format($metrics['todayRevenue'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Total Profit (Pro/Enterprise Only) --}}
    <div class="col-md-3">
        <div class="card metric-glass p-3">
            @if($isBasic)
                <div class="lock-overlay">
                    <a href="{{ route('membership-plans') }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.6rem;">UPGRADE</a>
                </div>
            @endif
            <div class="d-flex align-items-center gap-3 {{ $isBasic ? 'locked-blur' : '' }}">
                <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Profit</small>
                    <h4 class="fw-bold mb-0 text-success">₦{{ number_format($metrics['totalProfit'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Total Expenses (Pro/Enterprise Only) --}}
    <div class="col-md-3">
        <div class="card metric-glass p-3">
            @if($isBasic)
                <div class="lock-overlay">
                    <i class="fas fa-lock text-muted opacity-50"></i>
                </div>
            @endif
            <div class="d-flex align-items-center gap-3 {{ $isBasic ? 'locked-blur' : '' }}">
                <div class="metric-icon-box bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Expenses</small>
                    <h4 class="fw-bold mb-0 text-danger">₦{{ number_format($metrics['totalExpenses'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Inventory Value --}}
    <div class="col-md-3">
        <div class="card metric-glass p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon-box bg-dark bg-opacity-10 text-dark">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Inventory Items</small>
                    <h4 class="fw-bold mb-0" style="color: var(--deep-sapphire);">{{ number_format($metrics['activeStock'] ?? $metrics['activeCompanies']) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
