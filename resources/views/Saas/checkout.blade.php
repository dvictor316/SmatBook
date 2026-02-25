@extends('layout.master')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    :root {
        --spa-primary: #2563eb;
        --spa-primary-dark: #1d4ed8;
    }

    html, body {
        height: 100%;
        overflow: hidden !important;
    }

    .main-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        height: 100% !important;
        overflow: hidden !important;
    }

    /* 1. ABSOLUTE VIEWPORT CENTERING */
    .smat-viewport {
        position: fixed; /* Changed from relative to fixed to guarantee full cover */
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        padding: 18px 12px;
        background-color: var(--sb-bg, #f8fafc);
        /* Z-Index adjusted: High enough to cover sidebar, lower than Bootstrap Modal (1050) */
        z-index: 900; 
        display: flex;
        align-items: flex-start;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Hide standard layout noise */
    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header { 
        display: none !important; 
        visibility: hidden !important;
    }

    /* 2. SUBTLE MINIMALIST BUBBLES */
    .bubble-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
        top: 0;
        left: 0;
        overflow: hidden;
    }

    .bubble {
        position: absolute;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.04) 0%, rgba(59, 130, 246, 0) 70%);
        animation: floatBubble 25s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.05); }
        66% { transform: translate(-20px, 20px) scale(0.95); }
    }

    /* 3. COMPACT PROFESSIONAL CARD */
    .smat-card {
        background: #ffffff;
        width: min(100%, 780px);
        max-width: 780px; 
        min-height: 550px;
        border-radius: 14px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
        display: flex;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        position: relative;
        margin-top: 4vh; /* Slight offset from top */
        margin-bottom: 4vh;
    }

    /* Side Panel (Summary) */
    .smat-aside {
        width: 35%;
        background: #f8fafc;
        padding: 30px 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 1px solid #e2e8f0;
    }

    .logo-img { height: 34px; width: auto; margin-bottom: 14px; }

    .step-badge {
        display: inline-block;
        padding: 5px 12px;
        background: #ffffff;
        color: var(--spa-primary);
        border: 1px solid #cbd5e1;
        border-radius: 100px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-row { display: flex; justify-content: space-between; font-size: 11px; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
    .info-label { color: #94a3b8; font-size: 9px; text-transform: uppercase; font-weight: 700; }
    .info-value { color: #334155; font-weight: 700; }

    .amount-display { margin-top: 20px; padding: 14px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
    .amount-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }

    /* Main Panel (Gateways) */
    .smat-main {
        width: 65%;
        padding: 30px 32px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
    }

    .form-title { font-weight: 800; color: #0f172a; font-size: 1.35rem; margin-bottom: 4px; }
    .form-subtitle { color: #64748b; font-size: 12px; margin-bottom: 20px; }

    /* Payment Option Tiles */
    .payment-tile {
        display: flex;
        align-items: center;
        padding: 12px 14px;
        border: 2px solid #f1f5f9;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.25s ease;
        margin-bottom: 9px;
        background: #fcfdfe;
    }

    .payment-tile:hover {
        border-color: var(--spa-primary);
        background: #f0f9ff;
        transform: translateY(-2px);
    }

    .tile-icon {
        width: 34px;
        height: 34px;
        background: #fff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #64748b;
        font-size: 1rem;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .tile-name { font-size: 13px; font-weight: 700; color: #334155; display: block; }
    .tile-desc { font-size: 10px; color: #94a3b8; }

    /* Brand Colors for Icons */
    .icon-opay { color: #00b875; border-color: #00b87530; background: #00b8750d; }
    .icon-moniepoint { color: #034c81; border-color: #034c8130; background: #034c810d; }
    .icon-paystack { color: #0ba4db; }
    .icon-flutterwave { color: #fb9b2a; }

    .btn-cancel {
        color: #94a3b8;
        text-decoration: none;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-cancel:hover { color: #ef4444; }

    @media (max-width: 991px) {
        .smat-card { flex-direction: column; width: 100%; height: auto; margin: 0 auto; min-height: 0; }
        .smat-aside, .smat-main { width: 100%; padding: 20px 16px; }
        .smat-viewport { padding: 10px; }
        /* Add padding bottom to scroll past bottom content on mobile */
        .smat-main { padding-bottom: 50px; } 
    }
</style>

<div class="smat-viewport">
    
    <!-- Ultra Light Bubbles -->
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    <!-- The Compact Card -->
    <div class="smat-card">
        
        <!-- Summary Panel -->
        <div class="smat-aside">
            <div>
                <img src="{{ asset('assets/img/smat12.png') }}" alt="SmatBook" class="logo-img">
                <br>
                <span class="step-badge">Step 03: Settlement</span>
                <h2 class="fw-bold mt-4 mb-2" style="font-size: 1.5rem; color: #0f172a; line-height: 1.2;">Activate Workspace</h2>
                <p class="small text-muted">Initialize your subscription for the <strong>{{ $subscription->plan_name ?? 'Pro' }}</strong> environment.</p>
            </div>

            <div>
                <div class="info-row"><span class="info-label">Billing Cycle</span><span class="info-value">{{ ucfirst($subscription->billing_cycle ?? 'Monthly') }}</span></div>
                <div class="info-row"><span class="info-label">Account</span><span class="info-value" style="font-size: 11px;">{{ $subscription->user->email ?? auth()->user()->email }}</span></div>
                
                <div class="amount-display text-center">
                    <span class="info-label" style="display: block; margin-bottom: 5px;">Total Amount Due</span>
                    <div class="amount-value">₦{{ number_format($subscription->amount ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Gateway Panel -->
        <div class="smat-main">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h1 class="form-title">Payment Method</h1>
                <a href="{{ url('/') }}" class="btn-cancel">Cancel</a>
            </div>
            <p class="form-subtitle">Select a secure gateway to continue.</p>

            @if(strtolower((string) ($subscription->payment_status ?? '')) === 'pending_verification')
                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:12px;">
                    Bank transfer submitted and currently awaiting super admin validation.
                    @if(!empty($subscription->transfer_reference))
                        <br><strong>Reference:</strong> {{ $subscription->transfer_reference }}
                    @endif
                </div>
            @endif

            <!-- OPay -->
            <div class="payment-tile" onclick="openBankTransferModal('OPay')">
                <div class="tile-icon icon-opay"><i class="fas fa-wallet"></i></div>
                <div class="tile-content">
                    <span class="tile-name">OPay</span>
                    <span class="tile-desc">Direct transfer from OPay Wallet</span>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-25" style="font-size: 12px;"></i>
            </div>

            <!-- Moniepoint -->
            <div class="payment-tile" onclick="openBankTransferModal('Moniepoint')">
                <div class="tile-icon icon-moniepoint"><i class="fas fa-university"></i></div>
                <div class="tile-content">
                    <span class="tile-name">Moniepoint</span>
                    <span class="tile-desc">Business banking transfer</span>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-25" style="font-size: 12px;"></i>
            </div>

            <!-- Paystack -->
            <div class="payment-tile" onclick="payWithPaystack()">
                <div class="tile-icon icon-paystack"><i class="fas fa-credit-card"></i></div>
                <div class="tile-content">
                    <span class="tile-name">Paystack</span>
                    <span class="tile-desc">Local Cards, Bank Transfer & USSD</span>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-25" style="font-size: 12px;"></i>
            </div>

            <!-- Flutterwave -->
            <div class="payment-tile" onclick="makePayment()">
                <div class="tile-icon icon-flutterwave"><i class="fas fa-globe"></i></div>
                <div class="tile-content">
                    <span class="tile-name">Flutterwave</span>
                    <span class="tile-desc">International Cards & Digital Wallets</span>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-25" style="font-size: 12px;"></i>
            </div>

            <!-- Standard Bank Transfer -->
            <div class="payment-tile" onclick="openBankTransferModal('Bank')">
                <div class="tile-icon"><i class="fas fa-landmark"></i></div>
                <div class="tile-content">
                    <span class="tile-name">Other Bank Transfer</span>
                    <span class="tile-desc">Zenith/GTB Account Transfer</span>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-25" style="font-size: 12px;"></i>
            </div>

            <div class="mt-auto pt-4">
                <p class="text-center text-muted" style="font-size: 10px; line-height: 1.6;">
                    Transactions are secured with 256-bit SSL encryption.<br>Node deployment begins immediately after payment.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- 
    IMPORTANT: The Modal is placed OUTSIDE the .smat-viewport 
    to ensure the overlay/backdrop works correctly and z-index 
    is calculated relative to the body, not the scrollable div.
--}}
<div class="modal fade" id="bankTransferModal" tabindex="-1" aria-labelledby="bankTransferModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 65%); border-bottom: 1px solid #e2e8f0;">
                <h5 class="modal-title fw-bold" id="bankTransferModalLabel">Pay via Bank Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bankTransferModalForm" method="POST" enctype="multipart/form-data" novalidate action="{{ route('saas.payment.process.checkout', $subscription->id ?? 0) }}">
                @csrf
                <input type="hidden" name="gateway" value="bank_transfer">
                <div class="modal-body">
                    
                    {{-- Dynamic Header based on selection --}}
                    <div id="transferInstructions" class="mb-3">
                        <p class="small text-muted mb-0">Please complete your transfer to the account below.</p>
                    </div>

                    @if($errors->has('bank_id') || $errors->has('transfer_reference') || $errors->has('transfer_payer_name') || $errors->has('transfer_proof'))
                        <div class="alert alert-danger py-2">
                            Please check the transfer details and try again.
                        </div>
                    @endif

                    <div class="alert alert-primary d-flex align-items-center">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            <div><strong>Amount to Transfer:</strong> ₦{{ number_format($subscription->amount ?? 0, 2) }}</div>
                            <div class="small">Use the account below to complete your <span id="paymentProviderName">payment</span>.</div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted fs-7 fw-bold mb-2">Designated Account</h6>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Bank Name:</span>
                                <span class="fw-bold">Zenith Bank</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Account Number:</span>
                                <span class="fw-bold fs-5 text-dark">4234233940</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Account Name:</span>
                                <span class="fw-bold">SmatBook Systems</span>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden optional selection if backend requires it, otherwise defaults to main --}}
                    <div class="mb-3 d-none">
                        <select class="form-select" name="bank_id">
                            <option value="1" selected>Zenith Bank - 4234233940</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-7">Transfer Reference / Narration</label>
                            <input type="text" class="form-control" name="transfer_reference" placeholder="e.g. REF-123456" required>
                            @error('transfer_reference') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-7">Sender Name</label>
                            <input type="text" class="form-control" name="transfer_payer_name" value="{{ auth()->user()->name }}" required>
                            @error('transfer_payer_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold fs-7">Upload Proof (Screenshot)</label>
                            <input type="file" class="form-control" name="transfer_proof" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text">Optional, but speeds up verification.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="bankTransferSubmitBtn" class="btn btn-primary px-4">
                        I Have Made The Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
    async function saveCheckpoint() {
        try {
            const checkpointUrl = "{{ route('saas.checkout', ['subdomain' => $subscription->domain_prefix ?? 'pending', 'id' => $subscription->id ?? 0]) }}?step=payment_ready";
            await fetch(checkpointUrl, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        } catch (e) { console.warn("Heartbeat failed."); }
    }
    
    window.onload = saveCheckpoint;

    function payWithPaystack() {
        let handler = PaystackPop.setup({
            key: '{{ config("services.paystack.public_key") }}', 
            email: '{{ auth()->user()->email }}',
            amount: {{ (int)(($subscription->amount ?? 0) * 100) }},
            currency: 'NGN',
            callback: function(response) {
                window.location.href = "{{ route('saas.payment.callback', ['sub_id' => $subscription->id ?? 0, 'gateway' => 'paystack']) }}&reference=" + encodeURIComponent(response.reference);
            }
        });
        handler.openIframe();
    }

    function makePayment() {
        FlutterwaveCheckout({
            public_key: '{{ config("services.flutterwave.public_key") }}',
            tx_ref: 'SMAT_' + Math.floor(Math.random() * 1000000),
            amount: {{ $subscription->amount ?? 0 }},
            currency: "NGN",
            customer: { email: '{{ auth()->user()->email }}', name: '{{ auth()->user()->name }}' },
            callback: function (data) {
                const ref = data.tx_ref || ('FLW_' + Date.now());
                window.location.href = "{{ route('saas.payment.callback', ['sub_id' => $subscription->id ?? 0, 'gateway' => 'flutterwave']) }}&reference=" + encodeURIComponent(ref);
            },
            customizations: { title: "Smatbook SaaS", description: "Payment for {{ $subscription->plan_name }} Plan" },
        });
    }

    // Updated to accept a source (OPay, Moniepoint, etc) to customize the modal text slightly
    function openBankTransferModal(source = 'Bank') {
        const modalEl = document.getElementById('bankTransferModal');
        const providerLabel = document.getElementById('paymentProviderName');
        
        if (!modalEl) return;
        
        // Update label text based on tile clicked
        if(providerLabel) {
            providerLabel.textContent = source === 'Bank' ? 'payment' : source + ' transfer';
        }

        // Robust Bootstrap Modal Call
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            // Fallback for older bootstrap versions or if jquery is present
            if(typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                $('#bankTransferModal').modal('show');
            } else {
                alert('System error: Unable to load payment modal. Please refresh.');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const wireSubmitState = (formId) => {
            const form = document.getElementById(formId);
            if (!form) return;
            form.addEventListener('submit', function () {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
                }
            });
        };

        wireSubmitState('bankTransferModalForm');

        // Auto open if errors exist
        @if($errors->has('bank_id') || $errors->has('transfer_reference') || $errors->has('transfer_payer_name') || $errors->has('transfer_proof'))
            openBankTransferModal();
        @endif
    });
</script>
@endpush
