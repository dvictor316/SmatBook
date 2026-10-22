@if(isset($reportingPeriod, $periodSales))
<section class="spb-sales-period mb-4" aria-label="Sales reporting period">
    <div class="spb-sales-period-heading">
        <div><span class="spb-period-eyebrow">Sales performance</span><h4>{{ $reportingPeriod->label() }}</h4><p>{{ $reportingPeriod->dates() }} · {{ config('reporting.timezone') }}</p></div>
        <span class="spb-period-live"><span aria-hidden="true"></span> Updated {{ \Carbon\CarbonImmutable::now(config('reporting.timezone'))->format('H:i') }}</span>
    </div>
    <form method="GET" action="{{ url()->current() }}" data-reporting-period class="spb-period-form">
        @foreach(request()->except(['period', 'date_from', 'date_to', 'sale_date', 'page', 'export', '_token']) as $key => $value)
            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
        @endforeach
        @include('components.reporting-period-fields', ['periodFieldId' => 'dashboard'])
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1" aria-hidden="true"></i> Apply dates</button>
        <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['period' => 'today', 'date_from' => null, 'date_to' => null, 'sale_date' => null, 'page' => null]) }}">Today</a>
    </form>
    <div class="spb-period-cards">
        @foreach($periodSales as $key => $summary)
            <a class="spb-period-card {{ $reportingPeriod->key === $key ? 'is-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['period' => $key, 'date_from' => null, 'date_to' => null, 'sale_date' => null, 'page' => null]) }}" @if($reportingPeriod->key === $key) aria-current="true" @endif>
                <span>{{ $summary['period']->label() }}</span>
                <strong>₦{{ number_format($summary['revenue'], 2) }}</strong>
                <small>{{ number_format($summary['orders']) }} {{ \Illuminate\Support\Str::plural('sale', $summary['orders']) }} · {{ $summary['period']->dates() }}</small>
            </a>
        @endforeach
    </div>
    <p class="spb-period-note">Weeks start on Monday. Last 3 / 7 days include today. Sales, invoices, expenses, profit and recent sales follow the selected dates. Stock, receivables and customer counts show current balances. Monthly charts show this year to date.</p>
</section>
@endif
