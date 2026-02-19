// session domain logic
// domain => env('SESSION_DOMAIN', null)

@extends('layout.mainlayout')

@section('content')
@php $page = 'packages'; @endphp
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --inst-navy: #0f172a; 
        --inst-blue: #2563eb;
        --inst-bg: #f8fafc; 
        --inst-border: #e2e8f0;
        --inst-text-main: #1e293b;
        --inst-text-muted: #64748b;
    }

    /* Page Setup */
    .page-wrapper { 
        margin-left: 250px; 
        background-color: var(--inst-bg) !important; 
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }
    
    .content-container { padding: 30px; }

    /* Custom Institutional Header */
    .inst-header-bar {
        background: #ffffff;
        padding: 20px 25px;
        border-radius: 12px;
        border: 1px solid var(--inst-border);
        border-bottom: 3px solid var(--inst-navy);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .inst-header-bar h1 { 
        font-size: 1.25rem; 
        font-weight: 800; 
        color: var(--inst-navy); 
        margin: 0; 
        letter-spacing: -0.5px;
    }

    /* Stats Nodes */
    .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .metric-node {
        background: #ffffff;
        border: 1px solid var(--inst-border);
        border-left: 4px solid var(--inst-navy);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .metric-label { font-size: 10px; font-weight: 800; color: var(--inst-text-muted); text-transform: uppercase; letter-spacing: 1px; }
    .metric-value { font-size: 1.5rem; font-weight: 800; color: var(--inst-navy); margin-top: 4px; }

    /* Plan Cards Grid - Forced 3 Columns for Desktop */
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    @media (max-width: 1200px) {
        .pricing-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .pricing-grid { grid-template-columns: 1fr; }
    }

    .plan-card {
        background: #ffffff;
        border: 1px solid var(--inst-border);
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .plan-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); border-color: var(--inst-blue); }

    .plan-header { padding: 30px; border-bottom: 1px solid var(--inst-bg); text-align: center; }
    .plan-badge { 
        font-size: 10px; font-weight: 800; text-transform: uppercase; 
        padding: 4px 12px; border-radius: 20px; margin-bottom: 15px; display: inline-block;
    }
    .badge-monthly { background: #eff6ff; color: var(--inst-blue); border: 1px solid #dbeafe; }
    .badge-yearly { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }

    .plan-name { font-size: 1.5rem; font-weight: 700; color: var(--inst-navy); }
    .plan-price { font-size: 2rem; font-weight: 800; color: var(--inst-navy); letter-spacing: -1px; margin-top: 10px; }
    .plan-price small { font-size: 0.9rem; color: var(--inst-text-muted); font-weight: 500; letter-spacing: 0; }

    .feature-list { padding: 30px; flex-grow: 1; list-style: none; margin: 0; background: #fff; }
    .feature-list li { font-size: 0.95rem; color: var(--inst-text-main); margin-bottom: 15px; display: flex; align-items: flex-start; gap: 12px; }
    .feature-list i { color: var(--inst-blue); font-size: 1rem; margin-top: 3px; }

    /* Admin Actions */
    .card-actions { background: #f8fafc; padding: 20px 30px; border-top: 1px solid var(--inst-border); display: flex; gap: 10px; }
    .btn-inst {
        padding: 10px 15px; border-radius: 8px; font-size: 12px; font-weight: 700; 
        text-transform: uppercase; text-decoration: none; display: flex; align-items: center; justify-content: center; flex: 1;
        transition: 0.2s;
    }
    .btn-edit { background: var(--inst-navy); color: #fff; border: none; }
    .btn-edit:hover { background: var(--inst-blue); color: #fff; }
    .btn-delete { background: #fff; color: #ef4444; border: 1px solid #fee2e2; }
    .btn-delete:hover { background: #fef2f2; }

    @media (max-width: 991.98px) { .page-wrapper { margin-left: 0 !important; } }
</style>

<div class="page-wrapper">
    <div class="content-container">

        {{-- Navigation Tabs --}}
        <div class="d-flex gap-4 mb-4 no-print">
            <a href="{{ route('super_admin.packages.index') }}" class="text-decoration-none fw-bold small text-primary border-bottom border-2 border-primary pb-2">SERVICE PACKAGES</a>
            <a href="{{ route('super_admin.subscriptions.index') }}" class="text-decoration-none fw-bold small text-muted hover-primary pb-2">SUBSCRIBER REGISTRY</a>
        </div>

        {{-- Header --}}
        <div class="inst-header-bar no-print">
            <h1>SERVICE INFRASTRUCTURE PACKAGES</h1>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-light border btn-sm fw-bold px-3">
                    <i class="fas fa-print me-2"></i> PRINT
                </button>
                <button data-bs-toggle="modal" data-bs-target="#add_plan" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm" style="background: var(--inst-blue);">
                    <i class="fas fa-plus-circle me-2"></i> NEW NODE PLAN
                </button>
            </div>
        </div>

        {{-- Statistics Dashboard --}}
        <div class="metric-grid">
            <div class="metric-node">
                <div class="metric-label">Total Node Packages</div>
                <div class="metric-value">{{ $totalPlans }}</div>
            </div>
            <div class="metric-node" style="border-left-color: #10b981;">
                <div class="metric-label">Active Deployments</div>
                <div class="metric-value" style="color: #059669;">{{ $activePlans }}</div>
            </div>
            <div class="metric-node" style="border-left-color: #f59e0b;">
                <div class="metric-label">Deactivated Nodes</div>
                <div class="metric-value" style="color: #d97706;">{{ $pendingPlans }}</div>
            </div>
            <div class="metric-node" style="border-left-color: #8b5cf6;">
                <div class="metric-label">Billing Cycles</div>
                <div class="metric-value" style="color: #7c3aed;">{{ $planTypesCount }}</div>
            </div>
        </div>

        {{-- Forced 3-Column Grid --}}
        <div class="pricing-grid">
            @forelse ($plans as $plan)
                <div class="plan-item">
                    <div class="plan-card">
                        <div class="plan-header">
                            <span class="plan-badge {{ $plan->billing_cycle == 'yearly' ? 'badge-yearly' : 'badge-monthly' }}">
                                {{ strtoupper($plan->billing_cycle ?? 'Monthly') }}
                            </span>
                            <h4 class="plan-name mb-1">{{ $plan->name }}</h4>
                            <div class="plan-price">
                                ₦{{ number_format($plan->price ?? 0, 0) }}<small>/{{ substr($plan->billing_cycle, 0, 2) }}</small>
                            </div>
                        </div>

                        <ul class="feature-list">
                            @php 
                                $rawString = is_array($plan->features) ? implode(',', $plan->features) : (string)$plan->features;
                                $featuresList = array_filter(array_map('trim', explode(',', str_replace(['[', ']', '"', '\\'], '', $rawString))));
                            @endphp

                            @forelse ($featuresList as $feature)
                                <li><i class="fas fa-check-circle"></i> {{ $feature }}</li>
                            @empty
                                <li class="text-muted italic">Standard infrastructure access.</li>
                            @endforelse
                        </ul>

                        <div class="card-actions no-print">
                            <a href="{{ route('super_admin.packages.edit', $plan->id) }}" class="btn-inst btn-edit">
                                <i class="fas fa-edit me-2"></i> Configure
                            </a>
                            <form action="{{ route('super_admin.packages.delete', $plan->id) }}" method="POST" class="flex-grow-1" onsubmit="return confirm('Terminate this package node?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-inst btn-delete w-100">
                                    <i class="fas fa-trash-alt me-2"></i> Purge
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 py-5 text-center bg-white rounded-4 border-2 border-dashed border-light" style="grid-column: span 3;">
                    <i class="fas fa-box-open fa-3x text-light mb-3"></i>
                    <h5 class="text-muted fw-bold">NO PACKAGE INFRASTRUCTURE DETECTED</h5>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Printing Script --}}
<script>
    window.onbeforeprint = function() {
        console.log("Generating Package Registry context for domain: {{ env('SESSION_DOMAIN', 'null') }}");
    };
</script>

{{-- INSTITUTIONAL MODAL --}}
<div class="modal fade no-print" id="add_plan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            <form action="{{ route('super_admin.packages.store') }}" method="POST">
                @csrf
                <div class="p-5">
                    <h4 class="fw-800 text-dark mb-4" style="letter-spacing: -1px;">Construct New Package Node</h4>
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Node Name</label>
                        <input type="text" name="name" required class="form-control border-2 bg-light py-2 px-3 fw-bold" placeholder="e.g. Pro Engine">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Node Price (₦)</label>
                            <input type="number" name="price" required class="form-control border-2 bg-light py-2 px-3 fw-bold" placeholder="7000">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Billing Cycle</label>
                            <select name="duration" class="form-select border-2 bg-light py-2 px-3 fw-bold">
                                <option value="monthly">Monthly Cycle</option>
                                <option value="yearly">Yearly Cycle</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Feature Set (Comma Separated)</label>
                        <textarea name="features" rows="3" class="form-control border-2 bg-light py-2 px-3 fw-bold" placeholder="5 Executive Seats, Neural Forecasting, etc."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Activation Status</label>
                        <select name="status" class="form-select border-2 bg-light py-2 px-3 fw-bold">
                            <option value="1">Enabled / Active</option>
                            <option value="0">Disabled / Offline</option>
                        </select>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-light fw-bold py-3 flex-grow-1 border">Abort</button>
                        <button type="submit" class="btn btn-primary fw-bold py-3 flex-grow-1 shadow-sm" style="background: var(--inst-blue);">Commit Node</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection