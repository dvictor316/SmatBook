@extends('layout.mainlayout', ['hideNavbar' => true, 'hideSidebar' => true])

@section('page-title', 'Payment')

@section('content')
@php
    $checkoutProfileName = old('customer_name', $subscription->user->name ?? auth()->user()->name);
    $checkoutProfileEmail = old('customer_email', $subscription->user->email ?? auth()->user()->email);
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --spa-bg: #f7faff;
        --spa-surface: #ffffff;
        --spa-aside: #eef4ff;
        --spa-border: #e2e8f0;
        --spa-primary: #2563eb;
        --spa-primary-dark: #1d4ed8;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
    }

    .payment-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 14px;
        background: radial-gradient(circle at 0 0, #dbeafe 0%, transparent 35%), radial-gradient(circle at 100% 100%, #e0e7ff 0%, transparent 35%), var(--spa-bg);
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .payment-card {
        width: min(680px, 100%);
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--spa-border);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1);
        background: #fff;
        display: grid;
        grid-template-columns: 1fr;
    }

    .payment-summary {
        background: var(--spa-aside);
        color: var(--spa-text);
        padding: 28px 24px;
        border-bottom: 1px solid var(--spa-border);
    }

    .payment-brand {
        display: inline-flex;
        align-items: center;
        gap: 14px;
    }

    .payment-brand img {
        height: 68px;
        width: auto;
        flex-shrink: 0;
    }

    .payment-wordmark {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .payment-wordmark-title {
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1;
        color: #0f172a;
    }

    .payment-wordmark-title .brand-main {
        color: #143b8f;
    }

    .payment-wordmark-title .brand-accent {
        color: #dc2626;
    }

    .payment-wordmark-tagline {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #64748b;
    }

    .payment-summary h2 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 18px 0 8px;
        color: var(--spa-text);
    }

    .payment-summary p {
        margin: 0;
        color: var(--spa-muted);
        font-size: 13px;
    }

    .summary-box {
        margin-top: 20px;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        padding: 14px;
        background: #ffffff;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        margin-bottom: 10px;
        color: #334155;
    }

    .summary-row:last-child {
        margin-bottom: 0;
    }

    .summary-total {
        margin-top: 14px;
        border-top: 1px dashed #bfdbfe;
        padding-top: 12px;
        font-size: 24px;
        font-weight: 800;
        color: var(--spa-primary-dark);
    }

    .payment-form {
        padding: 28px;
    }

    .payment-form h1 {
        color: var(--spa-text);
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .payment-form p {
        color: var(--spa-muted);
        font-size: 13px;
        margin-bottom: 20px;
    }

    .field-label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .field-input {
        border: 1px solid var(--spa-border);
        border-radius: 10px;
        padding: 11px 12px;
        font-size: 14px;
        width: 100%;
        background: #fff;
    }

    .field-input:focus {
        border-color: var(--spa-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        outline: none;
    }

    .stripe-note {
        margin-top: 16px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        border-radius: 10px;
        padding: 11px 12px;
        font-size: 12px;
    }

    .gateway-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 8px;
    }

    .gateway-option {
        position: relative;
    }

    .gateway-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .gateway-pill {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        border: 1px solid var(--spa-border);
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        background: #fff;
        cursor: pointer;
        transition: all .2s ease;
        text-align: center;
        padding: 8px 10px;
    }

    .gateway-pill.gateway-stripe {
        font-family: 'Plus Jakarta Sans', sans-serif;
        letter-spacing: .2px;
        color: #4338ca;
        border-color: #c7d2fe;
        background: linear-gradient(180deg, #eef2ff 0%, #ffffff 100%);
    }

    .gateway-pill.gateway-paystack {
        font-family: 'Plus Jakarta Sans', sans-serif;
        letter-spacing: .2px;
        color: #0f766e;
        border-color: #99f6e4;
        background: linear-gradient(180deg, #ecfeff 0%, #ffffff 100%);
    }

    .gateway-pill.gateway-flutterwave {
        font-family: 'Plus Jakarta Sans', sans-serif;
        letter-spacing: .2px;
        color: #b45309;
        border-color: #fde68a;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }

    .gateway-option input:checked + .gateway-pill {
        border-color: var(--spa-primary);
        color: #1d4ed8;
        background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .14);
    }

    .pay-btn {
        width: 100%;
        margin-top: 18px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--spa-primary), var(--spa-primary-dark));
        color: #fff;
        font-weight: 800;
        font-size: 14px;
        padding: 13px 16px;
        transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.32);
    }

    .pay-btn:hover {
        transform: translateY(-2px);
    }

    .pay-btn:disabled {
        opacity: .75;
        cursor: not-allowed;
    }

    .secondary-link {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    #embeddedStripeWrap {
        display: none;
        margin-top: 16px;
        border: 1px solid var(--spa-border);
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
        min-height: 520px;
    }

    #embeddedStripeCheckout {
        min-height: 520px;
    }

    @media (max-width: 991px) {
        .payment-summary,
        .payment-form {
            padding: 22px 18px;
        }

        .gateway-grid {
            grid-template-columns: 1fr;
        }

        .payment-brand {
            gap: 10px;
        }

        .payment-brand img {
            height: 60px;
        }

        .payment-wordmark-title {
            font-size: 1.55rem;
        }

        .payment-wordmark-tagline {
            font-size: 10px;
            letter-spacing: 0.18em;
        }
    }
</style>

<div class="payment-shell">
    <div class="payment-card">
        <aside class="payment-summary">
            <div class="payment-brand">
                <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook">
                <div class="payment-wordmark" aria-label="SmartProbook">
                    <div class="payment-wordmark-title">
                        <span class="brand-main">SmartPro</span><span class="brand-accent">book</span>
                    </div>
                    <div class="payment-wordmark-tagline">Secure Business Stack</div>
                </div>
            </div>
            <h2>Workspace Activation</h2>
            <p>Complete your payment to activate your workspace instantly.</p>

            <div class="summary-box">
                <div class="summary-row">
                    <span>Plan</span>
                    <strong>{{ $subscription->plan_name ?? $subscription->plan ?? 'Standard' }}</strong>
                </div>
                <div class="summary-row">
                    <span>Billing</span>
                    <strong>{{ ucfirst($subscription->billing_cycle ?? 'Monthly') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Email</span>
                    <strong style="font-size:11px;">{{ $checkoutProfileEmail }}</strong>
                </div>
                <div class="summary-total">₦{{ number_format((float) ($subscription->amount ?? 0), 2) }}</div>
            </div>
        </aside>

        <section class="payment-form">
            @if(session('error'))
                <div class="alert alert-danger py-2">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="alert alert-info py-2">{{ session('info') }}</div>
            @endif

            <h1>Payment Details</h1>
            <p>Choose your preferred payment gateway. Paystack, Stripe, and Flutterwave remain available for test mode.</p>

            <form id="checkoutPaymentForm" method="POST" action="{{ route('saas.payment.process.checkout', $subscription->id) }}">
                @csrf
                <input type="hidden" name="gateway" id="selectedGatewayInput" value="paystack">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="field-label">Full Name</label>
                        <input class="field-input" type="text" value="{{ $checkoutProfileName }}" autocomplete="name" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Email Address</label>
                        <input class="field-input" type="email" value="{{ $checkoutProfileEmail }}" autocomplete="email" readonly>
                    </div>
                    <div class="col-12">
                        <label class="field-label">Payment Gateway</label>
                        <div class="gateway-grid">
                            <label class="gateway-option">
                                <input type="radio" name="gateway_option" value="stripe">
                                <span class="gateway-pill gateway-stripe">Stripe</span>
                            </label>
                            <label class="gateway-option">
                                <input type="radio" name="gateway_option" value="paystack" checked>
                                <span class="gateway-pill gateway-paystack">Paystack</span>
                            </label>
                            <label class="gateway-option">
                                <input type="radio" name="gateway_option" value="flutterwave">
                                <span class="gateway-pill gateway-flutterwave">Flutterwave</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="stripe-note" id="gatewayHelpText">
                    You will be redirected to Paystack secure checkout and returned automatically.
                </div>

                <button class="pay-btn" type="submit" id="payNowBtn">
                    Continue to Paystack (₦{{ number_format((float) ($subscription->amount ?? 0), 2) }})
                </button>
            </form>

            <a class="secondary-link" href="{{ route('saas.setup', $subscription->id) }}">
                <i class="fas fa-arrow-left"></i> Back to setup
            </a>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('checkoutPaymentForm');
        const payNowBtn = document.getElementById('payNowBtn');
        const gatewayInput = document.getElementById('selectedGatewayInput');
        const gatewayRadios = Array.from(document.querySelectorAll('input[name="gateway_option"]'));
        const gatewayHelpText = document.getElementById('gatewayHelpText');
        if (!form || !payNowBtn) return;

        const amountLabel = '₦{{ number_format((float) ($subscription->amount ?? 0), 2) }}';

        const setGatewayUI = (gateway) => {
            if (gatewayInput) {
                gatewayInput.value = gateway;
            }

            if (gateway === 'stripe') {
                payNowBtn.innerHTML = `Continue to Stripe (${amountLabel})`;
                gatewayHelpText.textContent = 'You will be redirected to Stripe secure checkout and returned automatically.';
                return;
            }

            if (gateway === 'flutterwave') {
                payNowBtn.innerHTML = `Continue to Flutterwave (${amountLabel})`;
                gatewayHelpText.textContent = 'You will be redirected to Flutterwave secure checkout and returned automatically.';
                return;
            }

            payNowBtn.innerHTML = `Continue to Paystack (${amountLabel})`;
            gatewayHelpText.textContent = 'You will be redirected to Paystack secure checkout and returned automatically.';
        };

        gatewayRadios.forEach((radio) => {
            radio.addEventListener('change', function () {
                if (this.checked) {
                    setGatewayUI(this.value);
                }
            });
        });

        setGatewayUI(gatewayInput ? gatewayInput.value : 'paystack');

        form.addEventListener('submit', function () {
            payNowBtn.disabled = true;
            payNowBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Redirecting to secure checkout...';
        });
    });
</script>
@endpush
