@if(isset($reportingPeriod, $periodSales))
<section class="spb-sales-period mb-4" aria-label="Sales reporting period">
    <div class="spb-sales-period-heading">
        <div><span class="spb-period-eyebrow">Sales performance</span><h4>{{ $reportingPeriod->label() }}</h4><p>{{ $reportingPeriod->dates() }}</p></div>
    </div>
    <div class="spb-period-controls">
        <form method="GET" action="{{ url()->current() }}" data-reporting-period class="spb-period-form">
            @foreach(request()->except(['period', 'date_from', 'date_to', 'date_time_from', 'date_time_to', 'sale_date', 'page', 'export', '_token']) as $key => $value)
                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
            @include('components.reporting-period-fields', ['periodFieldId' => 'dashboard'])
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1" aria-hidden="true"></i> Apply dates</button>
            <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['period' => 'today', 'date_from' => null, 'date_to' => null, 'date_time_from' => null, 'date_time_to' => null, 'sale_date' => null, 'page' => null]) }}">Today</a>
        </form>
    </div>
    @if(isset($headerSalesMetrics))
        <div class="spb-header-metrics" aria-label="Sales summary">
            <div class="card metric-glass metric-theme-primary p-3 spb-header-metric spb-header-metric-customers">
                <div class="d-flex align-items-center gap-3">
                    <span class="metric-icon-box spb-metric-icon"><i class="fas fa-user-group" aria-hidden="true"></i></span>
                    <div class="spb-metric-copy">
                        <small class="text-uppercase fw-bold spb-metric-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Unique customers</small>
                        <h4 class="fw-bold mb-0 spb-metric-value">{{ number_format($headerSalesMetrics['customers']) }}</h4>
                        <small class="text-muted d-block mt-2">Made a purchase in this period</small>
                    </div>
                </div>
            </div>
            <div class="card metric-glass metric-theme-success p-3 spb-header-metric spb-header-metric-average">
                <div class="d-flex align-items-center gap-3">
                    <span class="metric-icon-box spb-metric-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                    <div class="spb-metric-copy">
                        <small class="text-uppercase fw-bold spb-metric-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Average daily sales</small>
                        <h4 class="fw-bold mb-0 spb-metric-value">₦{{ \App\Support\CompactNumber::format($headerSalesMetrics['average_daily_sales'], 2) }}</h4>
                        <small class="text-muted d-block mt-2">Across {{ number_format($headerSalesMetrics['days']) }} {{ \Illuminate\Support\Str::plural('day', $headerSalesMetrics['days']) }}</small>
                    </div>
                </div>
            </div>
            <div class="card metric-glass metric-theme-danger p-3 spb-header-metric spb-header-metric-change">
                <div class="d-flex align-items-center gap-3">
                    <span class="metric-icon-box spb-metric-icon"><i class="fas fa-arrow-trend-up" aria-hidden="true"></i></span>
                    <div class="spb-metric-copy">
                        <small class="text-uppercase fw-bold spb-metric-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Vs previous period</small>
                        <h4 class="fw-bold mb-0 spb-metric-value {{ ($headerSalesMetrics['change_percent'] ?? 0) >= 0 ? 'is-positive' : 'is-negative' }}">
                            {{ is_null($headerSalesMetrics['change_percent']) ? '—' : (($headerSalesMetrics['change_percent'] > 0 ? '+' : '').number_format($headerSalesMetrics['change_percent'], 1).'%') }}
                        </h4>
                        <small class="text-muted d-block mt-2">
                            @if(is_null($headerSalesMetrics['previous_revenue']))
                                No comparison available
                            @elseif($headerSalesMetrics['previous_revenue'] <= 0)
                                No previous-period sales
                            @else
                                Previous: ₦{{ \App\Support\CompactNumber::format($headerSalesMetrics['previous_revenue'], 2) }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="spb-period-cards">
        @foreach($periodSales as $key => $summary)
            @php
                $periodIcon = match ($key) {
                    'today' => 'fa-calendar-day',
                    'week_to_date' => 'fa-calendar-week',
                    'month_to_date' => 'fa-calendar-alt',
                    default => 'fa-calendar-check',
                };
                $periodTheme = match ($key) {
                    'today' => 'metric-theme-primary',
                    'week_to_date' => 'metric-theme-success',
                    'month_to_date' => 'metric-theme-danger',
                    default => 'metric-theme-dark',
                };
            @endphp
            <a class="card metric-glass {{ $periodTheme }} p-3 spb-period-card {{ $reportingPeriod->key === $key ? 'is-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['period' => $key, 'date_from' => null, 'date_to' => null, 'date_time_from' => null, 'date_time_to' => null, 'sale_date' => null, 'page' => null]) }}" @if($reportingPeriod->key === $key) aria-current="true" @endif>
                <span class="d-flex align-items-center gap-3 w-100">
                    <span class="metric-icon-box spb-metric-icon"><i class="fas {{ $periodIcon }}" aria-hidden="true"></i></span>
                    <span class="spb-period-copy">
                        <small class="text-uppercase fw-bold spb-period-title" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ $summary['period']->label() }}</small>
                        <h4 class="fw-bold mb-0 spb-period-value">₦{{ \App\Support\CompactNumber::format($summary['revenue'], 2) }}</h4>
                        <small class="text-muted d-block mt-2">{{ number_format($summary['orders']) }} {{ \Illuminate\Support\Str::plural('sale', $summary['orders']) }} · {{ $summary['period']->dates() }}</small>
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</section>
@endif
