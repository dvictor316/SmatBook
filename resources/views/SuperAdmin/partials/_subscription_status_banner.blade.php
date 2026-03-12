@if(!empty($subscriptionStatus))
    <style>
        .subscription-state-banner {
            border-radius: 18px;
            border: 1px solid transparent;
            padding: 18px 20px;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }
        .subscription-state-banner.warning {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }
        .subscription-state-banner.expired {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .subscription-state-banner .meta {
            font-size: 0.88rem;
            opacity: 0.9;
        }
    </style>

    <div class="subscription-state-banner {{ $subscriptionStatus['state'] }}">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="fw-bold mb-1">{{ $subscriptionStatus['title'] }}</div>
                <div class="meta">{{ $subscriptionStatus['message'] }}</div>
                @if(!empty($subscriptionStatus['usage']))
                    <div class="meta mt-2">{{ $subscriptionStatus['usage'] }}</div>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('membership-plans') }}" class="btn {{ $subscriptionStatus['state'] === 'expired' ? 'btn-danger' : 'btn-warning' }} btn-sm px-3">
                    {{ $subscriptionStatus['state'] === 'expired' ? 'Renew Plan' : 'Review Renewal' }}
                </a>
                @if(Route::has('plan-billing'))
                    <a href="{{ route('plan-billing') }}" class="btn btn-outline-dark btn-sm px-3">Plan Billing</a>
                @endif
            </div>
        </div>
    </div>
@endif
