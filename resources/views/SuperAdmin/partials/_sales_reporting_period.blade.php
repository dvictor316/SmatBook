@if(isset($reportingPeriod, $periodSales))
<section class="spb-sales-period mb-4" aria-label="Sales reporting period">
    <div class="spb-sales-period-heading">
        <div><span class="spb-period-eyebrow">Sales performance</span><h4>{{ $reportingPeriod->label() }}</h4><p>{{ $reportingPeriod->dates() }}</p></div>
    </div>
    <details class="spb-period-menu">
        <summary><i class="fas fa-sliders" aria-hidden="true"></i><span>Filters &amp; periods</span></summary>
        <div class="spb-period-menu-content">
            <form method="GET" action="{{ url()->current() }}" data-reporting-period class="spb-period-form">
                @foreach(request()->except(['period', 'date_from', 'date_to', 'date_time_from', 'date_time_to', 'sale_date', 'page', 'export', '_token']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                @include('components.reporting-period-fields', ['periodFieldId' => 'dashboard'])
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1" aria-hidden="true"></i> Apply dates</button>
                <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['period' => 'today', 'date_from' => null, 'date_to' => null, 'date_time_from' => null, 'date_time_to' => null, 'sale_date' => null, 'page' => null]) }}">Today</a>
            </form>
            <div class="spb-period-cards">
                @foreach($periodSales as $key => $summary)
                    <a class="spb-period-card {{ $reportingPeriod->key === $key ? 'is-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['period' => $key, 'date_from' => null, 'date_to' => null, 'date_time_from' => null, 'date_time_to' => null, 'sale_date' => null, 'page' => null]) }}" @if($reportingPeriod->key === $key) aria-current="true" @endif>
                        <span>{{ $summary['period']->label() }}</span>
                        <strong>₦{{ number_format($summary['revenue'], 2) }}</strong>
                        <small>{{ number_format($summary['orders']) }} {{ \Illuminate\Support\Str::plural('sale', $summary['orders']) }} · {{ $summary['period']->dates() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    </details>
</section>
@endif
