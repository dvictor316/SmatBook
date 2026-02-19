@extends('layout.mainlayout')

@section('page-title', 'Register New Customer')

@section('content')
<style>
    :root {
        --blue: var(--sb-primary);
        --blue-light: var(--sb-primary-2);
        --green: var(--sb-success);
        --amber: var(--sb-warning);
        --surface: var(--sb-bg);
        --card: var(--sb-surface);
        --border: var(--sb-border);
        --text: var(--sb-text);
        --muted: var(--sb-muted);
    }

    /* ── Cards ── */
    .dm-card {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .dm-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        background: var(--card);
    }

    /* ── Form controls ── */
    .form-control, .form-select {
        border-radius: 8px;
        border-color: var(--border);
        padding: 0.6rem 0.9rem;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(30,64,175,0.08);
    }
    .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* ── Step Wizard ── */
    .wizard-track {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        position: relative;
        max-width: 560px;
        margin: 0 auto 2.5rem;
    }
    .wizard-track::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 12%;
        right: 12%;
        height: 2px;
        background: var(--border);
        z-index: 0;
    }
    .wizard-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    .wizard-bubble {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--card);
        border: 2px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        color: #9ca3af;
        margin-bottom: 8px;
        transition: all 0.3s;
    }
    .wizard-step.active   .wizard-bubble { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(30,64,175,0.3); }
    .wizard-step.done     .wizard-bubble { background: var(--green); border-color: var(--green); color: #fff; }
    .wizard-step.done     .wizard-bubble::after { content: '✓'; }
    .wizard-step.done     .wizard-bubble span { display: none; }
    .wizard-label {
        font-size: 11.5px;
        font-weight: 600;
        color: #9ca3af;
        text-align: center;
        white-space: nowrap;
    }
    .wizard-step.active .wizard-label { color: var(--blue); }
    .wizard-step.done   .wizard-label { color: var(--green); }

    /* ── Plan Cards ── */
    .plan-card {
        border: 2px solid var(--border);
        border-radius: 14px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.25s;
        background: var(--card);
        position: relative;
        height: 100%;
    }
    .plan-card:hover {
        border-color: var(--blue-light);
        box-shadow: 0 6px 20px rgba(59,130,246,0.12);
        transform: translateY(-3px);
    }
    .plan-card.selected {
        border-color: var(--blue);
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        box-shadow: 0 8px 24px rgba(30,64,175,0.18);
        transform: translateY(-3px);
    }
    .plan-tick {
        position: absolute;
        top: 12px; right: 12px;
        width: 26px; height: 26px;
        background: var(--green);
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
    }
    .plan-card.selected .plan-tick { display: flex; }
    .plan-tier    { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 4px; }
    .plan-amount  { font-size: 32px; font-weight: 800; color: var(--blue); line-height: 1; }
    .plan-amount small { font-size: 13px; color: var(--muted); font-weight: 500; }
    .plan-cycle   { font-size: 12px; color: var(--muted); margin: 4px 0 14px; }
    .plan-features { list-style: none; padding: 0; margin: 0; border-top: 1px solid var(--border); padding-top: 14px; }
    .plan-features li {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 5px 0; font-size: 13px; color: #374151;
    }
    .plan-features li i { color: var(--green); font-size: 12px; margin-top: 3px; flex-shrink: 0; }
    .plan-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }
    .pill-popular     { background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; }
    .pill-recommended { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }

    /* ── Billing Toggle ── */
    .billing-toggle {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
    }
    .billing-toggle button {
        padding: 8px 26px;
        border: none;
        background: transparent;
        color: var(--muted);
        font-weight: 600;
        font-size: 13.5px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.25s;
    }
    .billing-toggle button.active {
        background: #fff;
        color: var(--blue);
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .save-pill {
        display: inline-block;
        background: var(--green);
        color: #fff;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 700;
        margin-left: 6px;
    }
    .plans-wrap { display: none; }
    .plans-wrap.active { display: block; }

    /* ── Info Boxes ── */
    .tip-box {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-left: 4px solid var(--blue-light);
        padding: 13px 16px;
        border-radius: 0 8px 8px 0;
        font-size: 13px;
        margin-bottom: 1.25rem;
    }
    .tip-box i { color: var(--blue-light); }
    .warn-box {
        background: linear-gradient(135deg, #fefce8, #fef9c3);
        border-left: 4px solid var(--amber);
        padding: 13px 16px;
        border-radius: 0 8px 8px 0;
        font-size: 13px;
    }
    .warn-box i { color: var(--amber); }

    /* ── Commission Banner ── */
    .commission-banner {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid #a7f3d0;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 13px;
    }
    .commission-banner .big-num {
        font-size: 22px;
        font-weight: 800;
        color: var(--green);
        line-height: 1;
    }

    /* ── Password ── */
    .pw-bar { height: 4px; border-radius: 2px; margin-top: 7px; transition: all 0.3s; }
    .pw-bar.weak   { background: #ef4444; width: 33%; }
    .pw-bar.medium { background: #f59e0b; width: 66%; }
    .pw-bar.strong { background: var(--green); width: 100%; }

    /* ── Summary ── */
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row .label { color: var(--muted); }
    .summary-row .value { font-weight: 700; color: var(--text); }
    .summary-total {
        display: flex;
        justify-content: space-between;
        padding: 14px 0 0;
        margin-top: 6px;
        border-top: 2px solid var(--border);
    }

    /* ── Buttons ── */
    .btn-next {
        background: var(--blue);
        color: #fff;
        border: none;
        padding: 10px 28px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-next:hover { background: #1d3a9f; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30,64,175,0.3); }
    .btn-next:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-prev {
        background: #fff;
        color: var(--muted);
        border: 1.5px solid var(--border);
        padding: 10px 22px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-prev:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-submit {
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff;
        border: none;
        padding: 12px 36px;
        border-radius: 9px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 14px rgba(16,185,129,0.3);
    }
    .btn-submit:hover { background: linear-gradient(135deg, #047857, #059669); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16,185,129,0.4); }
    .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
</style>

<div class="sb-shell" id="register-wrapper">

    {{-- ── Flash messages ── --}}
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-2 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3">
        <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size:12.5px;">
                    <li class="breadcrumb-item">
                        <a href="{{ route('deployment.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('deployment.users.index') }}" class="text-decoration-none text-muted">Customers</a>
                    </li>
                    <li class="breadcrumb-item active text-dark fw-semibold">Register New</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-1" style="color:var(--text)">Register New Customer</h4>
            <p class="text-muted small mb-0">Create account → Select plan → Set credentials → SaaS checkout → SaaS success</p>
        </div>
        <a href="{{ route('deployment.users.index') }}" class="btn btn-sm btn-white border shadow-sm text-muted">
            <i class="fas fa-arrow-left me-1"></i> Back to Customers
        </a>
    </div>

    {{-- ── Step Wizard ── --}}
    <div class="wizard-track">
        <div class="wizard-step active" id="step1">
            <div class="wizard-bubble"><span>1</span></div>
            <div class="wizard-label">Company Info</div>
        </div>
        <div class="wizard-step" id="step2">
            <div class="wizard-bubble"><span>2</span></div>
            <div class="wizard-label">Select Plan</div>
        </div>
        <div class="wizard-step" id="step3">
            <div class="wizard-bubble"><span>3</span></div>
            <div class="wizard-label">Credentials</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         FORM — posts to deployment.customers.store
         store() → creates User + Company + Subscription
         → redirects to /saas/checkout/{id} (SaaS checkout)
         → successful payment redirects to /saas/success/{id} and deploys workspace/subdomain
         ════════════════════════════════════════════ --}}
    <form action="{{ route('deployment.customers.store') }}" method="POST" id="regForm" novalidate>
        @csrf

        {{-- ════════════════
             STEP 1: Company
             ════════════════ --}}
        <div id="pane1">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="dm-card">
                        <div class="dm-card-header">
                            <h5 class="fw-bold mb-0" style="color:var(--blue)">
                                <i class="fas fa-building me-2"></i>Company Information
                            </h5>
                            <p class="text-muted small mb-0 mt-1">Basic details about the customer's organisation</p>
                        </div>
                        <div class="p-4">

                            <div class="tip-box mb-4">
                                <i class="fas fa-bolt me-2"></i>
                                <strong>Quick setup:</strong> Customer gets an instant subdomain workspace after payment.
                                You automatically earn <strong>35% commission</strong>.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" id="companyName"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    placeholder="e.g. Acme Corporation"
                                    value="{{ old('company_name') }}" required>
                                @error('company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Workspace Subdomain <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="subdomain" id="subdomainPrefix"
                                        class="form-control @error('subdomain') is-invalid @enderror"
                                        placeholder="e.g. acme"
                                        value="{{ old('subdomain') }}"
                                        pattern="[a-z0-9\-]+" required>
                                    <span class="input-group-text bg-light fw-semibold text-muted small">
                                        .{{ config('session.domain', 'smatbook.com') }}
                                    </span>
                                </div>
                                <small class="text-muted">Lowercase letters, numbers and hyphens only.</small>
                                @error('subdomain')
                                    <div class="text-danger small mt-1 fw-semibold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Company Phone</label>
                                    <input type="tel" name="phone" class="form-control"
                                        placeholder="+234 800 123 4567"
                                        value="{{ old('phone') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Industry</label>
                                    <select name="industry" class="form-select">
                                        <option value="">Select industry…</option>
                                        @foreach([
                                            'retail'        => 'Retail & E-commerce',
                                            'manufacturing' => 'Manufacturing',
                                            'services'      => 'Professional Services',
                                            'technology'    => 'Technology & IT',
                                            'healthcare'    => 'Healthcare',
                                            'education'     => 'Education',
                                            'other'         => 'Other',
                                        ] as $val => $label)
                                        <option value="{{ $val }}" {{ old('industry') == $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="button" class="btn-next" onclick="toStep(2)">
                                    Next: Select Plan <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════
             STEP 2: Plan
             ══════════════════ --}}
        <div id="pane2" style="display:none">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="dm-card">
                        <div class="dm-card-header">
                            <h5 class="fw-bold mb-0" style="color:var(--blue)">
                                <i class="fas fa-layer-group me-2"></i>Choose Subscription Plan
                            </h5>
                            <p class="text-muted small mb-0 mt-1">Select the right plan for your customer</p>
                        </div>
                        <div class="p-4">

                            {{-- Hidden plan inputs --}}
                            <input type="hidden" name="plan_id"       id="planId"    value="{{ old('plan_id') }}">
                            <input type="hidden" name="plan_name"     id="planName"  value="{{ old('plan_name') }}">
                            <input type="hidden" name="plan_price"    id="planPrice" value="{{ old('plan_price') }}">
                            <input type="hidden" name="billing_cycle" id="planCycle" value="{{ old('billing_cycle', 'monthly') }}">

                            {{-- Billing Toggle --}}
                            <div class="text-center mb-4">
                                <div class="billing-toggle">
                                    <button type="button" id="btnMonthly" class="active" onclick="setCycle('monthly')">Monthly</button>
                                    <button type="button" id="btnYearly"  onclick="setCycle('yearly')">
                                        Yearly <span class="save-pill">Save 17%</span>
                                    </button>
                                </div>
                            </div>

                            {{-- MONTHLY --}}
                            <div id="wrapMonthly" class="plans-wrap active">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="basic-monthly"
                                             onclick="pickPlan('basic-monthly','Basic',3000,'monthly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <div class="plan-tier">Basic</div>
                                            <div class="plan-amount">₦3,000 <small>/mo</small></div>
                                            <div class="plan-cycle">Billed monthly · Earn ₦1,050</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Up to 10 users</li>
                                                <li><i class="fas fa-check-circle"></i> 5 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Invoicing & POS</li>
                                                <li><i class="fas fa-check-circle"></i> Basic reports</li>
                                                <li><i class="fas fa-check-circle"></i> Email support</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="professional-monthly"
                                             onclick="pickPlan('professional-monthly','Professional',7000,'monthly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <span class="plan-pill pill-popular">Most Popular</span>
                                            <div class="plan-tier">Professional</div>
                                            <div class="plan-amount">₦7,000 <small>/mo</small></div>
                                            <div class="plan-cycle">Billed monthly · Earn ₦2,450</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Up to 50 users</li>
                                                <li><i class="fas fa-check-circle"></i> 50 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Full inventory</li>
                                                <li><i class="fas fa-check-circle"></i> Purchases & orders</li>
                                                <li><i class="fas fa-check-circle"></i> Priority support</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="enterprise-monthly"
                                             onclick="pickPlan('enterprise-monthly','Enterprise',15000,'monthly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <span class="plan-pill pill-recommended">Best Value</span>
                                            <div class="plan-tier">Enterprise</div>
                                            <div class="plan-amount">₦15,000 <small>/mo</small></div>
                                            <div class="plan-cycle">Billed monthly · Earn ₦5,250</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Unlimited users</li>
                                                <li><i class="fas fa-check-circle"></i> 500 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Full ERP suite</li>
                                                <li><i class="fas fa-check-circle"></i> P&L & balance sheet</li>
                                                <li><i class="fas fa-check-circle"></i> Dedicated support</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- YEARLY --}}
                            <div id="wrapYearly" class="plans-wrap">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="basic-yearly"
                                             onclick="pickPlan('basic-yearly','Basic',30000,'yearly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <div class="plan-tier">Basic</div>
                                            <div class="plan-amount">₦30,000 <small>/yr</small></div>
                                            <div class="plan-cycle">Save ₦6,000 · Earn ₦10,500</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Up to 10 users</li>
                                                <li><i class="fas fa-check-circle"></i> 5 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Invoicing & POS</li>
                                                <li><i class="fas fa-check-circle"></i> Basic reports</li>
                                                <li><i class="fas fa-check-circle"></i> Email support</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="professional-yearly"
                                             onclick="pickPlan('professional-yearly','Professional',70000,'yearly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <span class="plan-pill pill-popular">Most Popular</span>
                                            <div class="plan-tier">Professional</div>
                                            <div class="plan-amount">₦70,000 <small>/yr</small></div>
                                            <div class="plan-cycle">Save ₦14,000 · Earn ₦24,500</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Up to 50 users</li>
                                                <li><i class="fas fa-check-circle"></i> 50 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Full inventory</li>
                                                <li><i class="fas fa-check-circle"></i> Purchases & orders</li>
                                                <li><i class="fas fa-check-circle"></i> Priority support</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="plan-card" data-pid="enterprise-yearly"
                                             onclick="pickPlan('enterprise-yearly','Enterprise',150000,'yearly')">
                                            <div class="plan-tick"><i class="fas fa-check"></i></div>
                                            <span class="plan-pill pill-recommended">Best Value</span>
                                            <div class="plan-tier">Enterprise</div>
                                            <div class="plan-amount">₦150,000 <small>/yr</small></div>
                                            <div class="plan-cycle">Save ₦30,000 · Earn ₦52,500</div>
                                            <ul class="plan-features">
                                                <li><i class="fas fa-check-circle"></i> Unlimited users</li>
                                                <li><i class="fas fa-check-circle"></i> 500 GB storage</li>
                                                <li><i class="fas fa-check-circle"></i> Full ERP suite</li>
                                                <li><i class="fas fa-check-circle"></i> P&L & balance sheet</li>
                                                <li><i class="fas fa-check-circle"></i> Dedicated support</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Commission preview --}}
                            <div class="commission-banner mt-4">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <div class="fw-bold text-dark small mb-1">Your commission (35%)</div>
                                        <div class="big-num">₦<span id="commPreview">0</span></div>
                                    </div>
                                    <div class="text-muted small">
                                        Credited automatically<br>after successful payment
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn-prev" onclick="toStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                <button type="button" class="btn-next" id="btnToCreds" disabled onclick="toStep(3)">
                                    Next: Credentials <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════
             STEP 3: Credentials
             ════════════════════ --}}
        <div id="pane3" style="display:none">
            <div class="row justify-content-center g-4">

                {{-- Left: credentials form --}}
                <div class="col-lg-7">
                    <div class="dm-card">
                        <div class="dm-card-header">
                            <h5 class="fw-bold mb-0" style="color:var(--blue)">
                                <i class="fas fa-key me-2"></i>Customer Login Credentials
                            </h5>
                            <p class="text-muted small mb-0 mt-1">Set the customer's login details</p>
                        </div>
                        <div class="p-4">

                            <div class="tip-box mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>What happens next:</strong>
                                Clicking <strong>Proceed to Payment</strong> creates the account, takes you
                                to the <strong>SaaS checkout page</strong>, then to the
                                <strong>SaaS success page</strong> after payment where the workspace is deployed.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="custName"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. John Doe"
                                    value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address (Login) <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="custEmail"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="customer@company.com"
                                    value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Used for login and payment notifications</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="custPw"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Min. 8 characters" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePw('custPw', this)">
                                        <i class="fas fa-eye fa-sm"></i>
                                    </button>
                                </div>
                                <div class="pw-bar" id="pwStrength"></div>
                                @error('password')
                                    <div class="text-danger small mt-1 fw-semibold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="custPwConf"
                                        class="form-control"
                                        placeholder="Re-enter password" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePw('custPwConf', this)">
                                        <i class="fas fa-eye fa-sm"></i>
                                    </button>
                                </div>
                                <small id="pwMatch" class="mt-1 d-block"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="customer_phone" class="form-control"
                                    placeholder="+234 800 123 4567"
                                    value="{{ old('customer_phone') }}">
                            </div>

                            <div class="warn-box mt-4">
                                <i class="fas fa-credit-card me-2"></i>
                                <strong>Payment step follows:</strong>
                                After this, you'll be taken to the <strong>SaaS checkout page</strong>.
                                On successful payment, you'll land on <strong>SaaS success</strong> and the workspace is deployed.
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn-prev" onclick="toStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                {{-- type="submit" — no JS preventDefault, no double-fire --}}
                                <button type="submit" class="btn-submit" id="btnSubmit">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: registration summary --}}
                <div class="col-lg-4">
                    <div class="dm-card">
                        <div class="dm-card-header">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-receipt me-2 text-primary"></i>Registration Summary
                            </h6>
                        </div>
                        <div class="p-4">
                            <div class="summary-row">
                                <span class="label">Company</span>
                                <span class="value" id="sumCompany">—</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Workspace</span>
                                <span class="value text-primary small" id="sumSubdomain">—</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Plan</span>
                                <span class="value" id="sumPlan">—</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Billing</span>
                                <span class="value text-capitalize" id="sumCycle">—</span>
                            </div>
                            <div class="summary-total">
                                <div>
                                    <div class="text-muted small">Subscription</div>
                                    <div class="fw-bold" id="sumAmount">₦0</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Your Commission</div>
                                    <div class="fw-bold" style="color:var(--green)" id="sumComm">₦0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dm-card mt-3">
                        <div class="p-4 text-center">
                            <div class="text-muted small mb-2">Flow after clicking <strong>Proceed to Payment</strong></div>
                            <div class="d-flex flex-column gap-2 text-start">
                                @foreach([
                                    ['fas fa-user-plus','Create account + company','success'],
                                    ['fas fa-file-invoice','Generate subscription','success'],
                                    ['fas fa-credit-card','Redirect to SaaS checkout','primary'],
                                    ['fas fa-star','Redirect to SaaS success','primary'],
                                    ['fas fa-check-circle','Payment activates workspace','warning'],
                                    ['fas fa-globe','Subdomain/workspace deployed','warning'],
                                    ['fas fa-percentage','Commission credited to you','success'],
                                ] as [$icon, $label, $colour])
                                <div class="d-flex align-items-center gap-2 small">
                                    <i class="{{ $icon }} text-{{ $colour }}"></i>
                                    <span>{{ $label }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </form>
</div>

<script>
/* ── State ───────────────────────────────── */
const DOMAIN = '{{ config("session.domain", "smatbook.com") }}';
let plan = { id: null, name: null, price: 0, cycle: 'monthly' };

/* ── Helpers ─────────────────────────────── */
const fmt = n => Number(n).toLocaleString('en-NG');

const slug = s => s.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim();

/* ── Auto-slug company → subdomain ──────── */
document.getElementById('companyName').addEventListener('input', e => {
    document.getElementById('subdomainPrefix').value = slug(e.target.value);
});
document.getElementById('subdomainPrefix').addEventListener('input', e => {
    e.target.value = slug(e.target.value);
});

/* ── Billing cycle ───────────────────────── */
window.setCycle = function(cycle) {
    plan.cycle = cycle;
    document.getElementById('planCycle').value = cycle;
    document.getElementById('btnMonthly').classList.toggle('active', cycle === 'monthly');
    document.getElementById('btnYearly').classList.toggle('active',  cycle === 'yearly');
    document.getElementById('wrapMonthly').classList.toggle('active', cycle === 'monthly');
    document.getElementById('wrapYearly').classList.toggle('active',  cycle === 'yearly');
    // Reset selected plan
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    plan = { id: null, name: null, price: 0, cycle };
    document.getElementById('planId').value    = '';
    document.getElementById('planName').value  = '';
    document.getElementById('planPrice').value = '';
    document.getElementById('commPreview').textContent = '0';
    document.getElementById('btnToCreds').disabled = true;
};

/* ── Pick plan ───────────────────────────── */
window.pickPlan = function(id, name, price, cycle) {
    plan = { id, name, price, cycle };
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-pid="${id}"]`).classList.add('selected');
    document.getElementById('planId').value    = id;
    document.getElementById('planName').value  = name;
    document.getElementById('planPrice').value = price;
    document.getElementById('planCycle').value = cycle;
    document.getElementById('commPreview').textContent = fmt(price * 0.35);
    document.getElementById('btnToCreds').disabled = false;
};

/* ── Step navigation ─────────────────────── */
window.toStep = function(n) {
    // Validate before moving forward
    if (n === 2) {
        const co = document.getElementById('companyName').value.trim();
        const sd = document.getElementById('subdomainPrefix').value.trim();
        if (!co)  { alert('Please enter the company name.'); return; }
        if (!sd)  { alert('Please enter a subdomain.'); return; }
        if (!/^[a-z0-9-]+$/.test(sd)) {
            alert('Subdomain: lowercase letters, numbers and hyphens only.'); return;
        }
    }
    if (n === 3) {
        if (!plan.id) { alert('Please select a subscription plan.'); return; }
        updateSummary();
    }

    // Show correct pane
    [1,2,3].forEach(i => {
        document.getElementById(`pane${i}`).style.display = i === n ? 'block' : 'none';
        const step = document.getElementById(`step${i}`);
        step.classList.remove('active','done');
        if (i === n)         step.classList.add('active');
        else if (i < n)      step.classList.add('done');
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

/* ── Summary ─────────────────────────────── */
function updateSummary() {
    const co = document.getElementById('companyName').value.trim()    || '—';
    const sd = document.getElementById('subdomainPrefix').value.trim() || '—';
    document.getElementById('sumCompany').textContent  = co;
    document.getElementById('sumSubdomain').textContent = sd + '.' + DOMAIN;
    document.getElementById('sumPlan').textContent     = plan.name  || '—';
    document.getElementById('sumCycle').textContent    = plan.cycle || '—';
    document.getElementById('sumAmount').textContent   = '₦' + fmt(plan.price);
    document.getElementById('sumComm').textContent     = '₦' + fmt(plan.price * 0.35);
}

/* ── Password toggle ─────────────────────── */
window.togglePw = function(id, btn) {
    const inp  = document.getElementById(id);
    const icon = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye',      inp.type === 'password');
    icon.classList.toggle('fa-eye-slash',inp.type === 'text');
};

/* ── Password strength ───────────────────── */
document.getElementById('custPw').addEventListener('input', function() {
    const pw  = this.value;
    const bar = document.getElementById('pwStrength');
    bar.className = 'pw-bar';
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (score === 1) bar.classList.add('weak');
    else if (score === 2) bar.classList.add('medium');
    else if (score === 3) bar.classList.add('strong');
});

/* ── Password match ──────────────────────── */
document.getElementById('custPwConf').addEventListener('input', function() {
    const match = document.getElementById('pwMatch');
    const same  = this.value === document.getElementById('custPw').value;
    if (this.value) {
        match.textContent = same ? '✓ Passwords match' : '✗ Passwords do not match';
        match.className   = same ? 'text-success small mt-1 d-block' : 'text-danger small mt-1 d-block';
    } else {
        match.textContent = '';
    }
});

/* ── Submit validation ───────────────────── */
document.getElementById('regForm').addEventListener('submit', function(e) {
    const pw   = document.getElementById('custPw').value;
    const conf = document.getElementById('custPwConf').value;
    if (pw !== conf) {
        e.preventDefault();
        alert('Passwords do not match.');
        return;
    }
    if (!document.getElementById('planId').value) {
        e.preventDefault();
        alert('Please select a plan.');
        toStep(2);
        return;
    }
    // Disable button to prevent double-submit
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating account…';
    // Form submits → store() → redirect to /saas/checkout/{id}
});

/* ── Restore state after validation error ── */
@if($errors->any())
    toStep(3);
@endif
@if(old('plan_id'))
    pickPlan('{{ old("plan_id") }}','{{ old("plan_name") }}',{{ (int)old("plan_price",0) }},'{{ old("billing_cycle","monthly") }}');
@endif
</script>
@endsection
