<?php $page = 'workspace-setup'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    body, html {
        height: 100%;
        background: #f8f9fa;
    }

    .setup-container {
        display: flex;
        height: 100vh;
        margin-left: 0 !important;
        background: #f8f9fa;
    }

    .setup-left-panel {
        width: 50%;
        background: linear-gradient(135deg, #0a1f3f 0%, #1a3a6a 100%);
        color: white;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    }

    .setup-logo {
        height: 40px;
        margin-bottom: 60px;
    }

    .setup-stage {
        color: #ffc107;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 15px;
    }

    .setup-title {
        font-size: 48px;
        font-weight: 900;
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .setup-subtitle {
        color: #a0b0c0;
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .progress-bar-custom {
        height: 6px;
        background: rgba(255,255,255,0.15);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 50px;
    }

    .progress-fill {
        height: 100%;
        width: 60%;
        background: linear-gradient(90deg, #ffc107, #ffb300);
        border-radius: 10px;
    }

    .summary-section {
        margin-bottom: 40px;
    }

    .summary-label {
        font-size: 11px;
        color: #7a8fa6;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .summary-value {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .summary-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 15px;
    }

    .summary-item {
        text-align: left;
    }

    .summary-item-label {
        font-size: 12px;
        color: #7a8fa6;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .summary-item-value {
        font-size: 18px;
        font-weight: 700;
    }

    .divider {
        border-top: 1px solid rgba(255,255,255,0.1);
        margin: 30px 0;
    }

    .inclusions-label {
        color: #ffc107;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .inclusions-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .inclusions-list li {
        font-size: 14px;
        color: #a0b0c0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
    }

    .inclusions-list i {
        color: #4caf50;
        margin-right: 12px;
        font-size: 16px;
    }

    .setup-right-panel {
        width: 50%;
        background: white;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow-y: auto;
    }

    .setup-form-title {
        font-size: 32px;
        font-weight: 900;
        color: #1a1f36;
        margin-bottom: 12px;
    }

    .setup-form-subtitle {
        color: #a0b0c0;
        font-size: 15px;
        margin-bottom: 40px;
    }

    .form-group {
        margin-bottom: 30px;
    }

    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 900;
        color: #1a1f36;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .form-input-wrapper {
        display: flex;
        align-items: stretch;
    }

    .form-control {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid #e0e6f2;
        border-radius: 8px 0 0 8px;
        font-size: 15px;
        font-weight: 500;
        color: #1a1f36;
        background: white;
        transition: all 0.3s ease;
    }

    .form-control::placeholder {
        color: #a0b0c0;
    }

    .form-control:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .form-input-suffix {
        padding: 12px 18px;
        background: white;
        border: 1px solid #e0e6f2;
        border-left: none;
        border-radius: 0 8px 8px 0;
        font-size: 15px;
        font-weight: 500;
        color: #a0b0c0;
        white-space: nowrap;
    }

    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e0e6f2;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        color: #1a1f36;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    .form-select:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .form-text-plain {
        padding: 12px 16px;
        background: #f5f7fb;
        border: 1px solid #e0e6f2;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        color: #1a1f36;
    }

    .form-hint {
        font-size: 13px;
        color: #a0b0c0;
        margin-top: 8px;
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 50px;
    }

    .btn-launch {
        flex: 1;
        padding: 16px 24px;
        background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-launch:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(196, 30, 58, 0.4);
    }

    .btn-cancel {
        flex: 1;
        padding: 16px 24px;
        background: white;
        color: #667085;
        border: 1px solid #d5dce0;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-cancel:hover {
        background: #f5f7fb;
        border-color: #a0b0c0;
    }

    @media (max-width: 1200px) {
        .setup-container {
            flex-direction: column;
            height: auto;
        }

        .setup-left-panel,
        .setup-right-panel {
            width: 100%;
        }

        .setup-left-panel {
            padding: 40px 30px;
            min-height: 50vh;
        }

        .setup-right-panel {
            padding: 40px 30px;
        }
    }
</style>

<div class="setup-container">
    <!-- Left Panel -->
    <div class="setup-left-panel">
        <div>
            <!-- Logo -->
            <div>
                <img src="{{ URL::asset('/assets/img/logo.svg') }}" alt="Logo" class="setup-logo">
            </div>

            <!-- Stage & Title -->
            <div class="mt-4">
                <div class="setup-stage">Setup Stage 02</div>
                <h1 class="setup-title">Business Identity</h1>
                <p class="setup-subtitle">Establishing your dedicated workspace on the SmartBook platform.</p>
            </div>

            <!-- Progress -->
            <div class="progress-bar-custom">
                <div class="progress-fill"></div>
            </div>
        </div>

        <!-- Summary at Bottom -->
        <div>
            <!-- Selected Tier -->
            <div class="summary-section">
                <div class="summary-label">Selected Tier</div>
                <div class="summary-value">{{ $subscription->plan->package_name ?? 'Standard' }}</div>
            </div>

<div class="summary-section">
    <div class="summary-label">Billing Frequency</div>
    <div class="summary-value">
        {{ ucfirst(optional(optional($subscription ?? null)->plan)->package_type ?? optional(optional($subscription ?? null)->plan)->billing_cycle ?? 'Monthly') }}
    </div>
</div>

<div class="divider"></div>

<div>
    <div class="inclusions-label">Inclusions</div>
    <ul class="inclusions-list">
        @php
            $features = [];
            // Check if subscription and plan exist before accessing features
            if (isset($subscription->plan->features)) {
                $rawFeatures = $subscription->plan->features;
                if (is_string($rawFeatures)) {
                    $decoded = json_decode($rawFeatures, true);
                    $features = is_array($decoded) ? $decoded : explode(',', $rawFeatures);
                } else {
                    $features = (array) $rawFeatures;
                }
            }

            // Clean and limit to 3
            $features = array_filter($features);
            $features = array_slice($features, 0, 3);
        @endphp

        @forelse($features as $feature)
            @php
                // Remove brackets, quotes, and whitespace
                $cleanFeature = preg_replace('/^[\[\"\s]+|[\]\"\s]+$/', '', $feature);
                $cleanFeature = trim($cleanFeature);
            @endphp

            @if(!empty($cleanFeature))
                <li>
                    <i class="fe fe-check"></i> {{ $cleanFeature }}
                </li>
            @endif
        @empty
            <li>
                <i class="fe fe-check"></i> Standard platform access
            </li>
        @endforelse
    </ul>
</div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="setup-right-panel">
        <div>
            <h2 class="setup-form-title">Workspace Setup</h2>
            <p class="setup-form-subtitle">Choose a unique domain prefix for your institution.</p>

            <form action="{{ route('super_admin.domain.store-setup', $subscription->id ?? '#') }}" method="POST">
                @csrf

                <!-- Institutional URL -->
                <div class="form-group">
                    <label class="form-label">Institutional URL</label>
                    <div class="form-input-wrapper">
                        <input type="text" 
                               class="form-control" 
                               id="domainPrefix" 
                               name="domain_prefix" 
                               placeholder="my-institution"
                               value="{{ old('domain_prefix', $subscription->domain_prefix ?? '') }}"
                               required
                               pattern="[a-z0-9\-]+"
                               minlength="3"
                               maxlength="63">
                        <span class="form-input-suffix">.smartbook.com</span>
                    </div>
                    <div class="form-hint">This serves as your permanent dashboard address.</div>
                    @error('domain_prefix')
                        <div class="alert alert-danger mt-2" style="font-size: 12px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Organization Scale -->
                <div class="form-group">
                    <label class="form-label">Organization Scale</label>
                    <select class="form-select" name="organization_scale" id="orgScale" required>
                        <option value="">Select organization size...</option>
                        <option value="1-10" {{ old('organization_scale', $subscription->organization_scale ?? '') === '1-10' ? 'selected' : '' }}>1-10 Members</option>
                        <option value="11-50" {{ old('organization_scale', $subscription->organization_scale ?? '') === '11-50' ? 'selected' : '' }}>11-50 Members</option>
                        <option value="51-200" {{ old('organization_scale', $subscription->organization_scale ?? '') === '51-200' ? 'selected' : '' }}>51-200 Members</option>
                        <option value="201-500" {{ old('organization_scale', $subscription->organization_scale ?? '') === '201-500' ? 'selected' : '' }}>201-500 Members</option>
                        <option value="500+" {{ old('organization_scale', $subscription->organization_scale ?? '') === '500+' ? 'selected' : '' }}>500+ Members</option>
                    </select>
                    @error('organization_scale')
                        <div class="alert alert-danger mt-2" style="font-size: 12px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Billing Node -->
                <div class="form-group">
                    <label class="form-label">Billing Node</label>
                    <div class="form-text-plain">
                        {{ ucfirst($subscription->plan->package_type ?? $subscription->plan->billing_cycle ?? 'Monthly') }}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="{{ route('super_admin.domains.index') }}" class="btn-cancel">
                        Cancel
                    </a>
                    <button type="submit" class="btn-launch">
                        <i class="fe fe-check"></i> Initialize Hub & Launch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection