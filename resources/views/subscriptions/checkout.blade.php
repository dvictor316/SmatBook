@extends('layout.mainlayout')

@section('page-title', 'Complete Payment')

@section('content')
<style>
    :root {
        --blue: #0f3a8a;
        --blue-dark: #061a44;
        --gold: #d7a928;
        --gold-soft: #fff1bf;
        --green: #0f3a8a;
        --surface: #f7faff;
        --card: #ffffff;
        --border: #d8e3f5;
    }

    .checkout-wrapper {
        min-height: 100vh;
        background:
            radial-gradient(circle at 0 0, rgba(215, 169, 40, 0.18) 0%, transparent 34%),
            radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.14) 0%, transparent 35%),
            var(--surface);
        padding: 2rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .checkout-card {
        background: var(--card);
        max-width: 600px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 24px 54px -34px rgba(6, 26, 68, 0.55);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .checkout-header {
        background: linear-gradient(135deg, var(--blue-dark), var(--blue));
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .checkout-header h1 {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .checkout-body {
        padding: 2rem;
    }

    .order-summary {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-label { color: #64748b; font-size: 14px; }
    .summary-value { font-weight: 700; color: var(--blue-dark); }

    .total-row {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px solid rgba(215, 169, 40, 0.45);
        font-size: 1.25rem;
    }
    .total-amount { color: var(--green); font-size: 1.5rem; }

    .payment-methods {
        display: grid;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .payment-option {
        border: 2px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .payment-option:hover { border-color: var(--gold); background: #fff8db; }
    .payment-option.selected { border-color: var(--gold); background: #fff8db; box-shadow: 0 0 0 3px rgba(215,169,40,0.16); }

    .payment-radio {
        width: 20px;
        height: 20px;
        accent-color: var(--blue);
    }

    .payment-info {
        flex: 1;
    }
    .payment-name {
        font-weight: 700;
        color: var(--blue-dark);
        margin-bottom: 0.25rem;
    }
    .payment-desc {
        font-size: 13px;
        color: #64748b;
    }

    .btn-pay {
        background: linear-gradient(135deg, var(--blue-dark), var(--blue));
        color: white;
        border: 1px solid transparent;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        width: 100%;
        transition: all 0.2s;
        box-shadow: 0 10px 24px rgba(15, 58, 138, 0.32);
    }
    .btn-pay:hover { background: #fff; color: var(--blue); border-color: var(--gold); transform: translateY(-2px); }
    .btn-pay:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }

    .security-note {
        text-align: center;
        font-size: 12px;
        color: #64748b;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
    }

    @media (max-width: 640px) {
        .checkout-card { margin: 1rem; }
        .checkout-header h1 { font-size: 1.4rem; }
    }
</style>

<div class="checkout-wrapper">
    <div class="checkout-card">
        <div class="checkout-header">
            <h1><i class="fas fa-lock me-2"></i>Secure Checkout</h1>
            <p class="mb-0">Complete your payment to activate your workspace</p>
        </div>

        <div class="checkout-body">

            <div class="order-summary">
                <h5 class="fw-bold mb-3">Order Summary</h5>

                @if($subscription->company)
                <div class="summary-row">
                    <span class="summary-label">Company</span>
                    <span class="summary-value">{{ $subscription->company->company_name ?? $subscription->company->name }}</span>
                </div>
                @endif

                @if($subscription->domain_prefix)
                <div class="summary-row">
                    <span class="summary-label">Workspace</span>
                    <span class="summary-value text-primary" style="font-family: monospace; font-size: 13px;">
                        {{ $subscription->domain_prefix }}.{{ config('session.domain', 'smatbook.com') }}
                    </span>
                </div>
                @endif

                <div class="summary-row">
                    <span class="summary-label">Plan</span>
                    <span class="summary-value">{{ $subscription->plan_name ?? $subscription->plan }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Billing Cycle</span>
                    <span class="summary-value text-capitalize">{{ $subscription->billing_cycle }}</span>
                </div>

                <div class="summary-row total-row">
                    <span class="summary-label" style="font-size: 1.1rem;">Total Amount</span>
                    <span class="total-amount">₦{{ number_format($subscription->amount, 0) }}</span>
                </div>
            </div>

            <h5 class="fw-bold mb-3">Select Payment Method</h5>

            <form action="{{ route('saas.payment.process.checkout', $subscription->id) }}" method="POST" id="paymentForm">
                @csrf

                <div class="payment-methods">
                    <label class="payment-option" data-gateway="paystack">
                        <input type="radio" name="gateway" value="paystack" class="payment-radio" required>
                        <div class="payment-info">
                            <div class="payment-name">
                                <i class="fas fa-credit-card me-2 text-primary"></i>Paystack
                            </div>
                            <div class="payment-desc">Pay with card, bank transfer, or USSD</div>
                        </div>
                    </label>

                    <label class="payment-option" data-gateway="flutterwave">
                        <input type="radio" name="gateway" value="flutterwave" class="payment-radio">
                        <div class="payment-info">
                            <div class="payment-name">
                                <i class="fas fa-university me-2 text-success"></i>Flutterwave
                            </div>
                            <div class="payment-desc">Secure payment via Flutterwave</div>
                        </div>
                    </label>

                    <label class="payment-option" data-gateway="stripe">
                        <input type="radio" name="gateway" value="stripe" class="payment-radio">
                        <div class="payment-info">
                            <div class="payment-name">
                                <i class="fab fa-stripe me-2 text-info"></i>Stripe
                            </div>
                            <div class="payment-desc">Global card checkout (real-time confirmation)</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn-pay" id="btnPay">
                    <i class="fas fa-shield-alt me-2"></i>Proceed to Payment
                </button>

                <div class="security-note">
                    <i class="fas fa-lock me-1"></i>
                    Your payment information is encrypted and secure.
                    <br>PCI-DSS compliant transaction processing.
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form    = document.getElementById('paymentForm');
    const btnPay  = document.getElementById('btnPay');
    const options = document.querySelectorAll('.payment-option');

    // Click label → select radio
    options.forEach(opt => {
        opt.addEventListener('click', function () {
            options.forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });

    // Prevent double-submit
    form.addEventListener('submit', function (e) {
        const selected = form.querySelector('input[name="gateway"]:checked');
        if (!selected) {
            e.preventDefault();
            alert('Please select a payment method.');
            return;
        }

        btnPay.disabled = true;
        btnPay.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    });

    // Pre-select first option
    if (options.length > 0) {
        options[0].click();
    }
});
</script>
@endsection
