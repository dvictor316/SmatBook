@extends('layout.mainlayout', ['hideNavbar' => true, 'hideSidebar' => true])

@section('page-title', 'Payment Successful')

@section('content')
@php
    $shouldAutoRedirectToWorkspace = false;
@endphp
<style>
    .header,
    .sidebar,
    #toggle_btn,
    #mobile_btn {
        display: none !important;
    }

    .main-wrapper,
    .page-wrapper {
        margin-left: 0 !important;
        padding-left: 0 !important;
        padding-top: 0 !important;
        width: 100% !important;
    }

    .page-wrapper .content.container-fluid {
        padding: 0 !important;
        max-width: 100% !important;
    }

    #success-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 14px;
        background:
            radial-gradient(circle at 8% 10%, rgba(37, 99, 235, 0.12), transparent 34%),
            radial-gradient(circle at 92% 92%, rgba(16, 185, 129, 0.12), transparent 34%),
            #f5f8ff;
    }

    #success-wrapper .row {
        width: 100%;
        margin: 0;
        justify-content: center;
    }

    #success-wrapper .col-lg-7 {
        width: 100%;
        max-width: 760px;
        padding: 0;
    }

    .success-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #dbe6ff;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
        padding: 2.4rem 2.1rem;
        text-align: center;
        max-width: 100%;
        margin: 0 auto;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        box-shadow: 0 8px 24px rgba(16,185,129,0.35);
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    @keyframes popIn {
        0%   { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .workspace-box {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 2px solid #93c5fd;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        margin: 1.5rem 0;
    }

    .workspace-url {
        font-size: 18px;
        font-weight: 800;
        color: #1e40af;
        word-break: break-all;
        text-decoration: none;
        display: block;
    }
    .workspace-url:hover { color: #1d3a9f; text-decoration: underline; }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin: 1.5rem 0;
        text-align: left;
    }

    .detail-item {
        background: #f8fafc;
        border-radius: 10px;
        padding: 12px 16px;
        border: 1px solid #f1f5f9;
    }

    .detail-item .label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .detail-item .value {
        font-size: 14px;
        font-weight: 700;
        color: #1f2937;
    }

    .commission-box {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid #6ee7b7;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin: 1rem 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .btn-primary-solid {
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 14px rgba(30,64,175,0.3);
    }
    .btn-primary-solid:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30,64,175,0.4);
        color: white;
    }

    .btn-outline-soft {
        background: white;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-outline-soft:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #374151;
    }

    .steps-done {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 1.5rem 0;
        text-align: left;
    }

    .step-done-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #374151;
    }

    .step-done-item .dot {
        width: 22px;
        height: 22px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        flex-shrink: 0;
    }

    .auto-redirect-note {
        margin-top: 12px;
        font-size: 13px;
        color: #64748b;
    }

    @media (max-width: 768px) {
        #success-wrapper {
            padding: 16px 10px;
        }

        .success-card {
            border-radius: 16px;
            padding: 1.4rem 1rem;
        }

        .detail-grid {
            grid-template-columns: 1fr;
            gap: 0.7rem;
        }

        .action-buttons {
            gap: 0.6rem;
        }

        .btn-primary-solid,
        .btn-outline-soft {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div id="success-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="success-card">

                {{-- Success icon --}}
                <div class="success-icon">
                    <i class="fas fa-check fa-2x text-white"></i>
                </div>

                @if($isManager)
                    {{-- ── MANAGER VIEW ── --}}
                    <h3 class="fw-bold mb-1" style="color:#1f2937">Customer Deployed! 🎉</h3>
                    <p class="text-muted mb-0">Payment confirmed. The dashboard is live and ready to use.</p>

                    @if($subscription)
                    <div class="steps-done mt-3">
                        <div class="step-done-item"><div class="dot">✓</div> Customer account created</div>
                        <div class="step-done-item"><div class="dot">✓</div> Subscription activated ({{ $subscription->plan ?? $subscription->plan_name }})</div>
                        <div class="step-done-item"><div class="dot">✓</div> Dashboard provisioned</div>
                        <div class="step-done-item"><div class="dot">✓</div> Commission recorded (35%)</div>
                    </div>

                    @if($workspaceUrl)
                    <div class="workspace-box">
                        <div class="text-muted small mb-2 fw-semibold">
                            <i class="fas fa-globe me-1 text-primary"></i>Customer Dashboard URL
                        </div>
                        <a href="{{ $workspaceUrl }}" class="workspace-url" target="_blank">
                            {{ $workspaceUrl }}
                        </a>
                        <div class="text-muted small mt-2">Share this URL with your customer</div>
                    </div>
                    @endif

                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="label">Customer</div>
                            <div class="value">{{ $subscription->user?->name ?? '—' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Email</div>
                            <div class="value small">{{ $subscription->user?->email ?? '—' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Plan</div>
                            <div class="value">{{ $subscription->plan_name ?? $subscription->plan }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Amount Paid</div>
                            <div class="value text-success">₦{{ number_format($subscription->amount, 0) }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Valid From</div>
                            <div class="value">{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('M d, Y') : 'Today' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Valid Until</div>
                            <div class="value">{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : '—' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Transaction Ref</div>
                            <div class="value">{{ $subscription->transaction_reference ?? 'Paid' }}</div>
                        </div>
                    </div>

                    @php
                        $commission = ($subscription->amount ?? 0) * 0.35;
                    @endphp
                    <div class="commission-box">
                        <div>
                            <div class="text-muted small fw-semibold mb-1">Your Commission (35%)</div>
                            <div class="fw-bold" style="font-size:22px;color:#059669">
                                ₦{{ number_format($commission, 0) }}
                            </div>
                            <div class="text-muted" style="font-size:12px">Credited automatically after successful payment</div>
                        </div>
                        <i class="fas fa-coins fa-2x text-success opacity-50"></i>
                    </div>
                    @endif

                    <div class="action-buttons">
                        <a href="{{ $returnUrl }}" class="btn-primary-solid">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                        <a href="javascript:void(0)" onclick="window.print()" class="btn-outline-soft">
                            <i class="fas fa-print"></i> Print Customer Transaction
                        </a>
                        <a href="{{ route('deployment.customers.create') }}" class="btn-outline-soft">
                            <i class="fas fa-plus"></i> Register Another
                        </a>
                    </div>

                @else
                    {{-- ── CUSTOMER VIEW ── --}}
                    <h3 class="fw-bold mb-1" style="color:#1f2937">Payment Successful! 🎉</h3>
                    <p class="text-muted mb-0">Your dashboard is now active and ready to use.</p>
                    @php
                        $isWorkspaceOwner = auth()->check() && $subscription && (int) auth()->id() === (int) $subscription->user_id;
                        $shouldAutoRedirectToWorkspace = $isWorkspaceOwner && filled($workspaceUrl);
                    @endphp

                    @if($subscription)
                    <div class="steps-done mt-3">
                        <div class="step-done-item"><div class="dot">✓</div> Payment confirmed</div>
                        <div class="step-done-item"><div class="dot">✓</div> Subscription activated ({{ $subscription->plan ?? $subscription->plan_name }})</div>
                        <div class="step-done-item"><div class="dot">✓</div> Your dashboard is live</div>
                    </div>

                    @if($workspaceUrl)
                    <div class="workspace-box">
                        <div class="text-muted small mb-2 fw-semibold">
                            <i class="fas fa-globe me-1 text-primary"></i>Your Dashboard URL
                        </div>
                        <a href="{{ $workspaceUrl }}" class="workspace-url" target="_blank">
                            {{ $workspaceUrl }}
                        </a>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ $isWorkspaceOwner ? "You're already signed in to this workspace." : 'Use your registered credentials to access this workspace.' }}
                        </div>
                        @if($shouldAutoRedirectToWorkspace)
                        <div class="auto-redirect-note">
                            Opening your new workspace automatically...
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="label">Plan</div>
                            <div class="value">{{ $subscription->plan_name ?? $subscription->plan }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Billing</div>
                            <div class="value text-capitalize">{{ $subscription->billing_cycle }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Amount Paid</div>
                            <div class="value text-success">₦{{ number_format($subscription->amount, 0) }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Valid From</div>
                            <div class="value">
                                {{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('M d, Y') : 'Today' }}
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Valid Until</div>
                            <div class="value">
                                {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : '—' }}
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Transaction Ref</div>
                            <div class="value">{{ $subscription->transaction_reference ?? 'Paid' }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="action-buttons">
                        @if($workspaceUrl)
                        <a href="{{ $workspaceUrl }}" class="btn-primary-solid" target="_blank">
                            <i class="fas fa-tachometer-alt"></i> Open Dashboard
                        </a>
                        @endif
                        <a href="javascript:void(0)" onclick="window.print()" class="btn-outline-soft">
                            <i class="fas fa-print"></i> Print Transaction
                        </a>
                        @if(session('deployment_return_manager_id'))
                        <a href="{{ route('saas.switch-back-manager') }}" class="btn-outline-soft">
                            <i class="fas fa-user-shield"></i> Return to Manager Dashboard
                        </a>
                        @endif
                    </div>
                @endif

            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(!empty($shouldAutoRedirectToWorkspace))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.setTimeout(function () {
            window.location.href = @json($workspaceUrl);
        }, 1200);
    });
</script>
@endif
@endpush
