<div class="spb-period-fields">
    <div>
        <label for="{{ $periodFieldId ?? 'reporting' }}-period">Reporting period</label>
        <select id="{{ $periodFieldId ?? 'reporting' }}-period" name="period" class="form-select">
            @foreach(\App\Support\ReportingPeriod::OPTIONS as $key => $label)
                <option value="{{ $key }}" @selected($reportingPeriod->key === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $periodFieldId ?? 'reporting' }}-from">From date</label>
        <input id="{{ $periodFieldId ?? 'reporting' }}-from" type="date" name="date_from" class="form-control" value="{{ $reportingPeriod->key === 'custom' ? $reportingPeriod->from : '' }}">
    </div>
    <div>
        <label for="{{ $periodFieldId ?? 'reporting' }}-to">Through date</label>
        <input id="{{ $periodFieldId ?? 'reporting' }}-to" type="date" name="date_to" class="form-control" value="{{ $reportingPeriod->key === 'custom' ? $reportingPeriod->to : '' }}">
    </div>
    <div>
        <label for="{{ $periodFieldId ?? 'reporting' }}-time-from">From date &amp; time</label>
        <input id="{{ $periodFieldId ?? 'reporting' }}-time-from" type="datetime-local" name="date_time_from" class="form-control" value="{{ $reportingPeriod->key === 'custom' && $reportingPeriod->timestampFrom ? \Carbon\CarbonImmutable::parse($reportingPeriod->timestampFrom, config('app.timezone', 'UTC'))->setTimezone(config('reporting.timezone'))->format('Y-m-d\TH:i') : '' }}">
    </div>
    <div>
        <label for="{{ $periodFieldId ?? 'reporting' }}-time-to">Through date &amp; time</label>
        <input id="{{ $periodFieldId ?? 'reporting' }}-time-to" type="datetime-local" name="date_time_to" class="form-control" value="{{ $reportingPeriod->key === 'custom' && $reportingPeriod->timestampTo ? \Carbon\CarbonImmutable::parse($reportingPeriod->timestampTo, config('app.timezone', 'UTC'))->setTimezone(config('reporting.timezone'))->format('Y-m-d\TH:i') : '' }}">
    </div>
</div>
@once
<link rel="stylesheet" href="{{ asset('css/reporting-period.css') }}?v=5">
<script src="{{ asset('js/reporting-period.js') }}?v=2" defer></script>
@endonce
