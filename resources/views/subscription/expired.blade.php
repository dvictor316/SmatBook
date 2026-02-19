@extends('layout.mainlayout')

@section('content')
<div class="main-wrapper">
    <div class="account-content">
        <div class="container">
            <div class="account-logo text-center mb-4">
                <a href="{{ url('/') }}"><img src="{{ asset('assets/images/logo.png') }}" alt="SmatBook"></a>
            </div>
            
            <div class="account-box shadow-lg border-0">
                <div class="account-wrapper p-5 text-center">
                    <div class="display-1 text-danger mb-3">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 class="account-title">Subscription Expired</h3>
                    <p class="text-muted">
                        Access to <strong>{{ auth()->user()->company->name }}</strong> has been suspended because your subscription ended on 
                        <span class="text-danger font-weight-bold">
                            {{ auth()->user()->company->subscription_end->format('d M, Y') }}
                        </span>.
                    </p>

                    <div class="renewal-actions mt-4 p-4 bg-light rounded border">
                        <h5>Ready to get back to work?</h5>
                        <p class="small text-muted">Your data is safe and exactly where you left it. Renew now to restore full access to your modules.</p>
                        
                        <div class="d-grid gap-2 d-md-block">
                            <a href="{{ route('membership-plans') }}" class="btn btn-primary btn-rounded btn-lg px-5 shadow">
                                <i class="fas fa-rocket"></i> Renew Subscription
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-center gap-3">
                        {{-- Implementation of your [2025-12-30] Print Script Rule --}}
                        <button onclick="window.print();" class="btn btn-outline-secondary btn-rounded">
                            <i class="fas fa-print"></i> Print Expiry Notice
                        </button>

                        <a href="{{ route('logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                           class="btn btn-link text-muted">
                            Switch Account
                        </a>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <hr class="my-4">

                    <div class="support-context">
                        <p class="mb-2">Need a custom quote or help with payment?</p>
                        {{-- Integration of Chat and Message for emails [2026-01-17] --}}
                        <div class="btn-group">
                            <a href="{{ route('messages.index', ['type' => 'chat']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-comments"></i> Live Chat
                            </a>
                            <a href="{{ route('messages.index', ['type' => 'email']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-envelope"></i> Open Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .account-logo, .support-context, hr {
            display: none !important;
        }
        .account-box {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }
        .text-danger { color: #000 !important; font-weight: bold; }
    }
</style>
@endsection