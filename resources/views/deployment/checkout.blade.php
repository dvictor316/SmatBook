@extends('layout.master')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
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

    .smat-viewport {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: var(--spa-bg);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }

    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header { 
        display: none !important; 
        visibility: hidden !important;
    }

    .bubble-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
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

    .smat-card {
        background: var(--spa-surface);
        width: 90%;
        max-width: 900px; 
        min-height: 600px;
        border-radius: 32px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.03);
        display: flex;
        overflow: hidden;
        border: 1px solid var(--spa-border);
    }

    .smat-main {
        width: 60%;
        padding: 50px;
        background: var(--spa-surface);
        display: flex;
        flex-direction: column;
    }

    .form-title { 
        font-weight: 800; 
        color: var(--spa-text); 
        font-size: 1.6rem; 
        margin-bottom: 5px; 
    }

    .form-subtitle { 
        color: var(--spa-muted); 
        font-size: 14px; 
        margin-bottom: 35px; 
    }

    .payment-tile {
        display: flex;
        align-items: center;
        padding: 18px 20px;
        border: 2px solid #f8fafc;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 14px;
        background: #fcfdfe;
        position: relative;
    }

    .payment-tile:hover {
        border-color: #3b82f6;
        background: #f8fbff;
        transform: translateY(-2px);
    }

    .payment-tile.selected {
        border-color: var(--spa-primary);
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }

    .tile-radio {
        width: 22px;
        height: 22px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        margin-right: 16px;
        position: relative;
        transition: all 0.3s;
        flex-shrink: 0;
    }

    .payment-tile.selected .tile-radio {
        border-color: var(--spa-primary);
    }

    .payment-tile.selected .tile-radio::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 11px;
        height: 11px;
        background: var(--spa-primary);
        border-radius: 50%;
    }

    .tile-icon {
        width: 44px;
        height: 44px;
        background: #fff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        font-size: 1.2rem;
        border: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .tile-icon.paystack i { color: #00C3F7; font-size: 22px; }
    .tile-icon.flutterwave i { color: #F5A623; font-size: 22px; }
    .tile-icon.stripe i { color: #635bff; font-size: 22px; }

    .tile-content {
        flex: 1;
    }

    .tile-name { 
        font-size: 15px; 
        font-weight: 700; 
        color: #0f172a; 
        display: block; 
        margin-bottom: 3px;
    }

    .tile-desc { 
        font-size: 12px; 
        color: #94a3b8; 
    }

    .tile-badge {
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        margin-left: auto;
    }

    .tile-badge.recommended {
        background: var(--spa-primary);
        color: white;
    }

    .tile-badge.alternative {
        background: #f1f5f9;
        color: #64748b;
    }

    .tile-badge.credit {
        background: #fef3c7;
        color: #92400e;
    }

    /* Summary sidebar */
    .smat-aside {
        width: 40%;
        background: var(--spa-aside);
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-left: 1px solid var(--spa-border);
    }

    .summary-title {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 25px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        font-size: 13px;
        border-bottom: 1px solid rgba(16, 185, 129, 0.1);
    }

    .summary-label {
        color: #1e3a8a;
        font-weight: 600;
    }

    .summary-value {
        color: #0f172a;
        font-weight: 700;
    }

    .total-row {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #bfdbfe;
    }

    .total-label {
        font-size: 16px;
        font-weight: 800;
        color: var(--spa-primary-dark);
    }

    .total-value {
        font-size: 28px;
        font-weight: 900;
        color: var(--spa-primary);
    }

    .commission-box {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-top: 20px;
    }

    .commission-label {
        color: var(--spa-primary-dark);
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .commission-amount {
        font-size: 24px;
        font-weight: 900;
        color: var(--spa-primary);
        margin-bottom: 4px;
    }

    .commission-note {
        font-size: 11px;
        color: var(--spa-primary-dark);
    }

    .btn-previous {
        width: 100%;
        padding: 15px;
        background: white;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-previous:hover {
        border-color: #94a3b8;
        color: #1e293b;
    }

    .btn-process {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--spa-primary) 0%, var(--spa-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-process:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }

    .btn-process:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .security-note {
        text-align: center;
        margin-top: 16px;
        font-size: 11px;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    #paymentModal {
        position: fixed; 
        inset: 0; 
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        display: none; 
        align-items: center; 
        justify-content: center; 
        z-index: 10000; 
        text-align: center;
    }

    .spinner { 
        width: 50px; 
        height: 50px; 
        border: 4px solid #f1f5f9; 
        border-top: 4px solid var(--spa-primary); 
        border-radius: 50%; 
        animation: spin 0.8s linear infinite; 
        margin: 0 auto 20px; 
    }

    @keyframes spin { 
        100% { transform: rotate(360deg); } 
    }

    @media (max-width: 991px) {
        .smat-card { 
            flex-direction: column-reverse; 
            width: 95%; 
            height: auto; 
            margin: 20px 0; 
        }
        .smat-aside, .smat-main { 
            width: 100%; 
            padding: 35px; 
        }
        .smat-viewport { 
            position: absolute; 
            height: auto; 
            min-height: 100vh; 
            overflow-y: auto; 
        }
    }
</style>

<div class="smat-viewport">

    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    <div class="smat-card">

        <!-- Payment panel (left) -->
        <div class="smat-main">
            <h1 class="form-title">Payment Method</h1>
            <p class="form-subtitle">Choose your preferred payment gateway</p>

            <form action="{{ route('deployment.process-payment', $subscription->id) }}" method="POST" id="paymentForm">
                @csrf
                <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                <input type="hidden" name="gateway" id="selectedGateway" value="paystack">

                <!-- Paystack -->
                <div class="payment-tile selected" data-gateway="paystack" onclick="selectGateway('paystack')">
                    <div class="tile-radio"></div>
                    <div class="tile-icon paystack">
                        <i class="fab fa-cc-visa"></i>
                    </div>
                    <div class="tile-content">
                        <span class="tile-name">Paystack</span>
                        <span class="tile-desc">Card, Bank Transfer, USSD</span>
                    </div>
                    <span class="tile-badge recommended">Recommended</span>
                </div>

                <!-- Flutterwave -->
                <div class="payment-tile" data-gateway="flutterwave" onclick="selectGateway('flutterwave')">
                    <div class="tile-radio"></div>
                    <div class="tile-icon flutterwave">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="tile-content">
                        <span class="tile-name">Flutterwave</span>
                        <span class="tile-desc">Card, Bank, Mobile Money</span>
                    </div>
                    <span class="tile-badge alternative">Alternative</span>
                </div>

                <!-- Stripe -->
                <div class="payment-tile" data-gateway="stripe" onclick="selectGateway('stripe')">
                    <div class="tile-radio"></div>
                    <div class="tile-icon stripe">
                        <i class="fab fa-stripe"></i>
                    </div>
                    <div class="tile-content">
                        <span class="tile-name">Stripe</span>
                        <span class="tile-desc">Global card checkout (test mode)</span>
                    </div>
                    <span class="tile-badge alternative">Alternative</span>
                </div>

                <div class="mt-auto pt-3">
                    <button type="button" class="btn-previous" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i>
                        <span>Previous</span>
                    </button>

                    <button type="submit" class="btn-process" id="processBtn">
                        <i class="fas fa-lock"></i>
                        <span>Process Payment</span>
                    </button>

                    <div class="security-note">
                        <i class="fas fa-shield-alt"></i>
                        Secured by SSL encryption
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary sidebar (right) -->
        <div class="smat-aside">
            <div>
                <h3 class="summary-title">Order Summary</h3>

                <div class="summary-row">
                    <span class="summary-label">Company</span>
                    <span class="summary-value">{{ $subscription->company->name ?? 'N/A' }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Plan</span>
                    <span class="summary-value">{{ $subscription->plan ?? 'Professional' }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Billing</span>
                    <span class="summary-value">{{ ucfirst($subscription->billing_cycle ?? 'Monthly') }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Subdomain</span>
                    <span class="summary-value" style="font-size: 11px;">{{ $subdomain }}.smatbook.com</span>
                </div>

                <div class="summary-row total-row">
                    <span class="total-label">Total</span>
                    <span class="total-value">₦{{ number_format($subscription->amount ?? 0) }}</span>
                </div>

                <div class="commission-box">
                    <div class="commission-label">
                        <i class="fas fa-percentage"></i>
                        Your Commission (35%)
                    </div>
                    <div class="commission-amount">
                        ₦{{ number_format(($subscription->amount ?? 0) * 0.35) }}
                    </div>
                    <div class="commission-note">
                        Credited after successful payment
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="paymentModal">
    <div>
        <div class="spinner"></div>
        <h5 id="modalTitle" style="font-weight: 700; color: #2563eb; font-size: 1.1rem;">PROCESSING PAYMENT...</h5>
        <p id="modalSub" style="font-size: 0.85rem; color: #64748b; margin-top: 8px;">
            Please wait while we process your transaction
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
    const paystackPublicKey = @json(
        trim((string) config('services.paystack.public_key'))
            ?: trim((string) config('services.paystack.publicKey'))
            ?: trim((string) \App\Models\Setting::getSensitive('paystack_key', \App\Models\Setting::get('paystack_key', '')))
    );
    const flutterwavePublicKey = @json(
        trim((string) config('services.flutterwave.public_key'))
            ?: trim((string) \App\Models\Setting::getSensitive('flutterwave_key', \App\Models\Setting::get('flutterwave_key', '')))
    );
    const stripePublishableKey = @json(
        trim((string) config('services.stripe.key'))
            ?: trim((string) env('STRIPE_TEST_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY', '')))
            ?: trim((string) \App\Models\Setting::getSensitive('stripe_key', \App\Models\Setting::get('stripe_key', '')))
    );

    let selectedGateway = 'paystack';

    function selectGateway(gateway) {
        selectedGateway = gateway;
        document.getElementById('selectedGateway').value = gateway;

        document.querySelectorAll('.payment-tile').forEach(tile => {
            tile.classList.remove('selected');
        });
        document.querySelector(`[data-gateway="${gateway}"]`).classList.add('selected');
    }

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const modal = document.getElementById('paymentModal');
        const btn = document.getElementById('processBtn');
        const subscription = {!! json_encode($subscription) !!};

        modal.style.display = 'flex';
        btn.disabled = true;

        if (selectedGateway === 'paystack') {
            modal.style.display = 'none';
            payWithPaystack(subscription);
        } else if (selectedGateway === 'flutterwave') {
            modal.style.display = 'none';
            payWithFlutterwave(subscription);
        } else if (selectedGateway === 'stripe') {
            if (!stripePublishableKey) {
                modal.style.display = 'none';
                btn.disabled = false;
                alert('Stripe test publishable key is missing. Add STRIPE_TEST_PUBLISHABLE_KEY in your .env file.');
                return;
            }
            this.submit();
        } else {
            modal.style.display = 'none';
            btn.disabled = false;
            alert('Please select Paystack, Flutterwave, or Stripe.');
        }
    });

    function payWithPaystack(subscription) {
        if (!paystackPublicKey) {
            alert('Paystack public key is missing. Update it in Payment Settings.');
            document.getElementById('processBtn').disabled = false;
            return;
        }

        let handler = PaystackPop.setup({
            key: paystackPublicKey,
            email: '{{ $subscription->user->email ?? auth()->user()->email }}',
            amount: {{ (int)(($subscription->amount ?? 0) * 100) }},
            currency: 'NGN',
            ref: 'DEP_' + Math.floor(Math.random() * 1000000000),
            callback: function(response) {
                window.location.href = "{{ route('payment.callback') }}?reference=" + response.reference + 
                    "&gateway=paystack&sub_id={{ $subscription->id }}";
            },
            onClose: function() {
                document.getElementById('paymentModal').style.display = 'none';
                document.getElementById('processBtn').disabled = false;
            }
        });
        handler.openIframe();
    }

    function payWithFlutterwave(subscription) {
        if (!flutterwavePublicKey) {
            alert('Flutterwave public key is missing. Update it in Payment Settings.');
            document.getElementById('processBtn').disabled = false;
            return;
        }

        FlutterwaveCheckout({
            public_key: flutterwavePublicKey,
            tx_ref: 'DEP_FLW_' + Math.floor(Math.random() * 1000000000),
            amount: {{ $subscription->amount ?? 0 }},
            currency: "NGN",
            payment_options: "card,banktransfer,ussd",
            customer: { 
                email: '{{ $subscription->user->email ?? auth()->user()->email }}', 
                name: '{{ $subscription->user->name ?? auth()->user()->name }}' 
            },
            callback: function (data) {
                if (data.status === 'successful') {
                    window.location.href = "{{ route('payment.callback') }}?transaction_id=" + data.transaction_id + 
                        "&gateway=flutterwave&sub_id={{ $subscription->id }}";
                }
            },
            onclose: function() {
                document.getElementById('paymentModal').style.display = 'none';
                document.getElementById('processBtn').disabled = false;
            },
            customizations: { 
                title: "SmartProbook Subscription", 
                description: "Payment for {{ $subscription->plan ?? 'Plan' }}" 
            },
        });
    }
</script>
@endpush
