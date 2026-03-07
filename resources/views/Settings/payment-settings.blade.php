<?php $page = 'payment-settings'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* Sidebar Offset Consistency */
    .page-wrapper {
        margin-left: 270px;
        transition: all 0.3s ease-in-out;
        background-color: #f8fafc;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }
    
    @media (max-width: 1200px) {
        .page-wrapper { margin-left: 0 !important; }
    }

    /* Integration Block Styling */
    .payment-gateway-block {
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        background: #fff;
    }
    .payment-toggle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f1f5f9;
    }
    .form-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }
    .form-title img {
        height: 24px;
        margin-right: 12px;
    }

    /* Print Styles */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .btn, .sidebar, .header, .status-toggle, .btn-path { display: none !important; }
        .col-xl-9 { width: 100% !important; }
        .payment-gateway-block { border: 1px solid #ccc !important; break-inside: avoid; }
        input { border: none !important; font-weight: bold !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="page-header mb-3">
                            <div class="content-page-header">
                                <h5 class="fw-bold text-primary"><i class="fas fa-credit-card me-2"></i>Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Payment Settings</h5>
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-print-none">
                                <i class="fas fa-print me-1"></i> Print Config
                            </button>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                        <div class="payment-gateway-block">
                            <div class="payment-toggle">
                                <h5 class="form-title d-flex align-items-center gap-2">
                                    Stripe
                                    @php
                                        $stripeOk = (bool) ($stripeStatus['configured'] ?? false);
                                        $stripeEnabled = (bool) ($stripeStatus['enabled'] ?? false);
                                        $stripeMode = strtoupper((string) ($stripeStatus['mode'] ?? 'UNKNOWN'));
                                        $stripeSource = (string) ($stripeStatus['source'] ?? 'none');
                                    @endphp
                                    <span class="badge {{ $stripeOk ? 'bg-success' : 'bg-danger' }}">
                                        {{ $stripeOk ? 'Configured' : 'Invalid / Missing' }}
                                    </span>
                                    @if($stripeEnabled)
                                        <span class="badge bg-primary">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </h5>
                                <div class="status-toggle">
                                    <input id="stripe_toggle" class="check" type="checkbox" name="payment_stripe_enabled" value="1" {{ !empty($settings['payment_stripe_enabled']) ? 'checked' : '' }}>
                                    <label for="stripe_toggle" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                            <div class="mb-3 small {{ $stripeOk ? 'text-success' : 'text-danger' }}">
                                <strong>Status:</strong> {{ $stripeOk ? 'Ready for checkout' : 'Please provide a valid Stripe secret key' }}
                                <span class="text-muted ms-2">Source: {{ $stripeSource }} | Mode: {{ $stripeMode }}</span>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Stripe Key</label>
                                        <input type="password" class="form-control" name="stripe_key" placeholder="{{ !empty($secretFlags['has_stripe_key']) ? 'Saved (leave blank to keep existing)' : 'Enter Stripe Key' }}" value="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Stripe Secret</label>
                                        <input type="password" class="form-control" name="stripe_secret" placeholder="{{ !empty($secretFlags['has_stripe_secret']) ? 'Saved (leave blank to keep existing)' : 'Enter Stripe Secret' }}" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-block">
                            <div class="payment-toggle">
                                <h5 class="form-title d-flex align-items-center gap-2">
                                    Paystack
                                    @php
                                        $paystackOk = (bool) ($paystackStatus['configured'] ?? false);
                                        $paystackEnabled = (bool) ($paystackStatus['enabled'] ?? false);
                                        $paystackMode = strtoupper((string) ($paystackStatus['mode'] ?? 'UNKNOWN'));
                                        $paystackSource = (string) ($paystackStatus['source'] ?? 'none');
                                    @endphp
                                    <span class="badge {{ $paystackOk ? 'bg-success' : 'bg-danger' }}">
                                        {{ $paystackOk ? 'Configured' : 'Invalid / Missing' }}
                                    </span>
                                    @if($paystackEnabled)
                                        <span class="badge bg-primary">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </h5>
                                <div class="status-toggle">
                                    <input id="paystack_toggle" class="check" type="checkbox" name="payment_paystack_enabled" value="1" {{ !empty($settings['payment_paystack_enabled']) ? 'checked' : '' }}>
                                    <label for="paystack_toggle" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                            <div class="mb-3 small {{ $paystackOk ? 'text-success' : 'text-danger' }}">
                                <strong>Status:</strong> {{ $paystackOk ? 'Ready for checkout' : 'Please provide a valid Paystack secret key' }}
                                <span class="text-muted ms-2">Source: {{ $paystackSource }} | Mode: {{ $paystackMode }}</span>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Paystack Public Key</label>
                                        <input type="password" class="form-control" name="paystack_key" placeholder="{{ !empty($secretFlags['has_paystack_key']) ? 'Saved (leave blank to keep existing)' : 'Enter Paystack Public Key' }}" value="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Paystack Secret Key</label>
                                        <input type="password" class="form-control" name="paystack_secret" placeholder="{{ !empty($secretFlags['has_paystack_secret']) ? 'Saved (leave blank to keep existing)' : 'Enter Paystack Secret Key' }}" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-block">
                            <div class="payment-toggle">
                                <h5 class="form-title d-flex align-items-center gap-2">
                                    Flutterwave
                                    @php
                                        $flutterwaveOk = (bool) ($flutterwaveStatus['configured'] ?? false);
                                        $flutterwaveEnabled = (bool) ($flutterwaveStatus['enabled'] ?? false);
                                        $flutterwaveMode = strtoupper((string) ($flutterwaveStatus['mode'] ?? 'UNKNOWN'));
                                        $flutterwaveSource = (string) ($flutterwaveStatus['source'] ?? 'none');
                                    @endphp
                                    <span class="badge {{ $flutterwaveOk ? 'bg-success' : 'bg-danger' }}">
                                        {{ $flutterwaveOk ? 'Configured' : 'Invalid / Missing' }}
                                    </span>
                                    @if($flutterwaveEnabled)
                                        <span class="badge bg-primary">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </h5>
                                <div class="status-toggle">
                                    <input id="flutterwave_toggle" class="check" type="checkbox" name="payment_flutterwave_enabled" value="1" {{ !empty($settings['payment_flutterwave_enabled']) ? 'checked' : '' }}>
                                    <label for="flutterwave_toggle" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                            <div class="mb-3 small {{ $flutterwaveOk ? 'text-success' : 'text-danger' }}">
                                <strong>Status:</strong> {{ $flutterwaveOk ? 'Ready for checkout' : 'Please provide a valid Flutterwave secret key' }}
                                <span class="text-muted ms-2">Source: {{ $flutterwaveSource }} | Mode: {{ $flutterwaveMode }}</span>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Flutterwave Public Key</label>
                                        <input type="password" class="form-control" name="flutterwave_key" placeholder="{{ !empty($secretFlags['has_flutterwave_key']) ? 'Saved (leave blank to keep existing)' : 'Enter Flutterwave Public Key' }}" value="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Flutterwave Secret Key</label>
                                        <input type="password" class="form-control" name="flutterwave_secret" placeholder="{{ !empty($secretFlags['has_flutterwave_secret']) ? 'Saved (leave blank to keep existing)' : 'Enter Flutterwave Secret Key' }}" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-block">
                            <div class="payment-toggle">
                                <h5 class="form-title">PayPal</h5>
                                <div class="status-toggle">
                                    <input id="paypal_toggle" class="check" type="checkbox" name="payment_paypal_enabled" value="1" {{ !empty($settings['payment_paypal_enabled']) ? 'checked' : '' }}>
                                    <label for="paypal_toggle" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Client Id</label>
                                        <input type="text" class="form-control" name="paypal_client_id" placeholder="Enter Paypal Client Id" value="{{ $settings['paypal_client_id'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Secret Key</label>
                                        <input type="password" class="form-control" name="paypal_secret" placeholder="{{ !empty($secretFlags['has_paypal_secret']) ? 'Saved (leave blank to keep existing)' : 'Enter Paypal Secret' }}" value="">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Environment Mode</label>
                                        <select class="select" name="paypal_environment">
                                            <option>Sandbox (Testing)</option>
                                            <option>Live (Production)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-gateway-block">
                            <div class="payment-toggle">
                                <h5 class="form-title">Razorpay</h5>
                                <div class="status-toggle">
                                    <input id="razor_toggle" class="check" type="checkbox" name="payment_razorpay_enabled" value="1" {{ !empty($settings['payment_razorpay_enabled']) ? 'checked' : '' }}>
                                    <label for="razor_toggle" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-0">
                                        <label class="form-label">Key Id</label>
                                        <input type="text" class="form-control" name="razorpay_key_id" placeholder="Enter Razorpay Key Id" value="{{ $settings['razorpay_key_id'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-0">
                                        <label class="form-label">Key Secret</label>
                                        <input type="password" class="form-control" name="razorpay_secret" placeholder="{{ !empty($secretFlags['has_razorpay_secret']) ? 'Saved (leave blank to keep existing)' : 'Enter Razorpay Secret' }}" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="btn-path text-end border-top pt-4 mt-2">
                                <a href="javascript:void(0);" class="btn btn-cancel bg-primary-light me-3 px-4">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
