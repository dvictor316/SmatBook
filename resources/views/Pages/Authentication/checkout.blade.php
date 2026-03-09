@extends('layout.master')

@section('content')
<style>
    /* Institutional Command Framework */
    :root {
        --muji-blue-deep: #002347; 
        --muji-blue-light: #f4f8ff; 
        --muji-gold: #c5a059; 
        --muji-red: #bc002d; 
        --muji-text: #1d1d1f;
    }

    html, body { height: 100%; overflow: hidden !important; background-color: var(--muji-blue-light); font-family: 'Inter', sans-serif; }
    .header, .sidebar, .settings-icon, .two-col-bar, .breadcrumb { display: none !important; }
    
    .page-wrapper { 
        margin-left: 0 !important; 
        padding: 0 !important; 
        height: 100vh; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background: radial-gradient(circle at top right, #fff, var(--muji-blue-light));
    }

    .landscape-card {
        max-width: 1050px;
        width: 95%;
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 40px 100px rgba(0,35,71,0.15);
        border: 1px solid rgba(197, 160, 89, 0.3);
        display: flex;
    }

    /* Left Side: Institutional Summary */
    .summary-side {
        background: var(--muji-blue-deep);
        color: white;
        padding: 60px 45px;
        width: 40%;
        display: flex;
        flex-direction: column;
        border-right: 5px solid var(--muji-gold);
    }

    .gold-label {
        color: var(--muji-gold);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.75rem;
        margin-bottom: 10px;
        display: block;
    }

    .total-amount {
        font-size: 3rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -2px;
    }

    /* Right Side: Payment Methods */
    .payment-side { padding: 60px; background: #fff; width: 60%; overflow-y: auto; }

    .payment-tile {
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid #eef0f2;
        border-left: 4px solid #eef0f2;
        border-radius: 4px;
        cursor: pointer;
        padding: 20px 25px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .payment-tile:hover {
        border-color: var(--muji-blue-deep);
        border-left-color: var(--muji-gold);
        background: var(--muji-blue-light);
        transform: translateX(8px);
    }

    .gateway-logo { height: 22px; object-fit: contain; filter: grayscale(100%); transition: 0.3s; }
    .payment-tile:hover .gateway-logo { filter: grayscale(0%); }

    .btn-cancel {
        color: var(--muji-text);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.5;
        transition: 0.3s;
    }
    .btn-cancel:hover { opacity: 1; color: var(--muji-red); }

    .secure-badge {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 0.7rem;
        margin-top: auto;
    }
</style>

<div class="landscape-card" data-aos="zoom-in">
    {{-- Left Side: Summary Panel --}}
    <div class="summary-side">
        <img src="{{ asset('assets/img/logos.png') }}" alt="Logo" height="70" class="mb-5 align-self-start">
        
        <span class="gold-label">Institutional Uplink</span>
        <h2 class="fw-bold mb-1 text-white">Finalize Setup</h2>
        
        {{-- CORRECTED: Accessing via subscription object property --}}
        <p class="text-white-50 small mb-5">Deployment Tier: <strong>{{ ucfirst($subscription->plan_name ?? 'Standard') }} Hub</strong></p>
        
        <div class="workspace-info mb-5">
            <div class="d-flex justify-content-between mb-2 small">
                <span class="opacity-50">Workspace Domain</span>
                <span class="fw-bold text-white">{{ $subscription->domain_prefix }}.smatbook.com</span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="opacity-50">Billing Frequency</span>
                <span class="fw-bold text-white">{{ $subscription->billing_cycle }}</span>
            </div>
            <div class="mt-4 pt-4 border-top border-secondary">
                <span class="gold-label">Total Due Now</span>
                {{-- CORRECTED: Mapping $amount to object property to prevent ErrorException --}}
                <div class="total-amount">₦{{ number_format($subscription->amount, 2) }}</div>
            </div>
        </div>

        <div class="secure-badge">
            <i class="fas fa-shield-check text-warning me-2"></i> 
            <span class="opacity-75">Encrypted AES-256 Secure Uplink Active</span>
        </div>
    </div>

    {{-- Right Side: Gateway Selection --}}
    <div class="payment-side">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0" style="color: var(--muji-blue-deep);">Select Gateway</h4>
            <a href="{{ url('/') }}" class="btn-cancel">Exit Checkout</a>
        </div>

        <div class="mb-3"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Primary Node: Paystack</small></div>
        
        <div class="payment-tile" onclick="payWithPaystack()">
            <div class="d-flex align-items-center">
                <img src="https://paystack.com/assets/img/login/paystack-logo.png" class="gateway-logo me-3" alt="Paystack">
                <div>
                    <span class="fw-bold d-block" style="color: var(--muji-blue-deep);">Instant Checkout</span>
                    <small class="text-muted">Card, Bank, Transfer, USSD</small>
                </div>
            </div>
            <i class="fas fa-arrow-right text-muted small"></i>
        </div>

        <div class="mb-3 mt-5"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Secondary Node: Flutterwave</small></div>

        <div class="payment-tile" onclick="makePayment()">
            <div class="d-flex align-items-center">
                <img src="https://flutterwave.com/images/logo/logo-colored.svg" class="gateway-logo me-3" alt="Flutterwave">
                <div>
                    <span class="fw-bold d-block" style="color: var(--muji-blue-deep);">Global Checkout</span>
                    <small class="text-muted">International Cards & Local Payment</small>
                </div>
            </div>
            <i class="fas fa-arrow-right text-muted small"></i>
        </div>

        <div class="mb-3 mt-5"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Global Node: Stripe</small></div>

        <div class="payment-tile" onclick="payWithStripe()">
            <div class="d-flex align-items-center">
                <i class="fab fa-stripe-s me-3" style="font-size: 22px; color:#635bff;"></i>
                <div>
                    <span class="fw-bold d-block" style="color: var(--muji-blue-deep);">Stripe Checkout</span>
                    <small class="text-muted">Global cards (real-time confirmation)</small>
                </div>
            </div>
            <i class="fas fa-arrow-right text-muted small"></i>
        </div>
        
        <p class="text-center text-muted mt-5 mb-0" style="font-size: 0.7rem;">
            Securely initializing connection for <strong>{{ auth()->user()->email }}</strong>.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>

<script>
    /**
     * Paystack Uplink Implementation
     */
    function payWithPaystack() {
        let handler = PaystackPop.setup({
            key: '{{ env("PAYSTACK_PUBLIC_KEY") }}', 
            email: '{{ auth()->user()->email }}',
            // CORRECTED: amount from subscription object
            amount: {{ (int)$subscription->amount * 100 }},
            currency: 'NGN',
            ref: 'SMAT_' + Math.floor((Math.random() * 1000000000) + 1),
            callback: function(response) {
                window.location.href = "{{ route('payment.callback') }}?reference=" + response.reference + "&gateway=paystack&sub_id={{ $subscription->id }}";
            }
        });
        handler.openIframe();
    }

    /**
     * Flutterwave Uplink Implementation
     */
    function makePayment() {
        FlutterwaveCheckout({
            public_key: '{{ env("FLW_PUBLIC_KEY") }}',
            tx_ref: 'SMAT_FLW_' + Math.floor((Math.random() * 1000000000) + 1),
            // CORRECTED: amount from subscription object
            amount: {{ $subscription->amount }},
            currency: "NGN",
            payment_options: "card, banktransfer, ussd",
            customer: {
                email: '{{ auth()->user()->email }}',
                name: '{{ auth()->user()->name }}',
            },
            callback: function (data) {
                window.location.href = "{{ route('payment.callback') }}?transaction_id=" + data.transaction_id + "&gateway=flutterwave&sub_id={{ $subscription->id }}";
            },
            customizations: {
                title: "SmartProbook Intelligence",
                description: "Uplink for {{ $subscription->plan_name ?? 'Institutional' }} Plan",
            },
        });
    }

    function payWithStripe() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('saas.payment.process.checkout', $subscription->id) }}";

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = "{{ csrf_token() }}";
        form.appendChild(token);

        const gateway = document.createElement('input');
        gateway.type = 'hidden';
        gateway.name = 'gateway';
        gateway.value = 'stripe';
        form.appendChild(gateway);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
