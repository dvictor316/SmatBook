@php
    $domain = \App\Models\Domain::where('domain_name', explode('.', request()->getHost())[0])->first();
    $daysRemaining = $domain ? now()->diffInDays($domain->expiry_date, false) : 0;
@endphp

@if($domain && $daysRemaining <= 7 && $daysRemaining >= 0)
    <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 shadow-sm d-flex align-items-center justify-content-center" style="background: #fff3cd; color: #856404; font-size: 14px;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span>
            Your subscription expires in <strong>{{ ceil($daysRemaining) }} days</strong>. 
            Renew now to avoid workspace interruption.
        </span>
        <a href="{{ config('app.url') . '/checkout' }}" class="btn btn-warning btn-sm ms-3 fw-bold py-0 px-3" style="border-radius: 20px; font-size: 12px;">
            Renew Now
        </a>
    </div>
@endif