<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportingPeriod
{
    public const OPTIONS = [
        'today' => 'Today', 'last_3_hours' => 'Last 3 hours', 'yesterday' => 'Yesterday', 'last_3_days' => 'Last 3 days',
        'last_7_days' => 'Last 7 days', 'last_30_days' => 'Last 30 days', 'last_week' => 'Last week', 'week_to_date' => 'Week to date',
        'month_to_date' => 'Month to date', 'year_to_date' => 'Year to date',
        'custom' => 'Custom dates', 'all' => 'All recorded dates',
    ];

    public function __construct(
        public string $key,
        public ?string $from,
        public ?string $to,
        public ?string $timestampFrom = null,
        public ?string $timestampTo = null,
    ) {}

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('reporting.timezone', 'Africa/Lagos'))->startOfDay();
    }

    public static function fromRequest(Request $request, string $default = 'today'): self
    {
        $request->validate([
            'period' => ['nullable', Rule::in(array_keys(self::OPTIONS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'sale_date' => ['nullable', 'date_format:Y-m-d'],
            'date_time_from' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'date_time_to' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);
        $hasCustomRange = $request->filled('sale_date')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->filled('date_time_from')
            || $request->filled('date_time_to');
        $key = $request->input('period') ?: ($hasCustomRange ? 'custom' : $default);
        if ($key !== 'custom') return self::preset($key);
        if ($request->filled('date_time_from') || $request->filled('date_time_to')) {
            $timezone = config('reporting.timezone', 'Africa/Lagos');
            $from = $request->input('date_time_from');
            $to = $request->input('date_time_to');
            if (! $from && ! $to) throw ValidationException::withMessages(['date_time_from' => 'Choose at least one time for a custom period.']);
            if ($from && $to && $from > $to) throw ValidationException::withMessages(['date_time_to' => 'The end time must be on or after the start time.']);
            return new self(
                'custom',
                null,
                null,
                $from ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $from, $timezone)->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s') : null,
                $to ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $to, $timezone)->endOfMinute()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s') : null,
            );
        }
        $from = $request->input('sale_date') ?: $request->input('date_from');
        $to = $request->input('sale_date') ?: $request->input('date_to');
        if (! $from && ! $to) throw ValidationException::withMessages(['date_from' => 'Choose at least one date for a custom period.']);
        if ($from && $to && $from > $to) throw ValidationException::withMessages(['date_to' => 'The end date must be on or after the start date.']);
        return new self('custom', $from ?: null, $to ?: null);
    }

    public static function preset(string $key): self
    {
        $today = self::today();
        if ($key === 'last_3_hours') {
            $now = CarbonImmutable::now(config('reporting.timezone', 'Africa/Lagos'));
            $storageTimezone = config('app.timezone', 'UTC');
            return new self(
                $key,
                null,
                null,
                $now->subHours(3)->setTimezone($storageTimezone)->format('Y-m-d H:i:s'),
                $now->setTimezone($storageTimezone)->format('Y-m-d H:i:s'),
            );
        }
        [$from, $to] = match ($key) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'last_3_days' => [$today->subDays(2), $today],
            'last_7_days' => [$today->subDays(6), $today],
            'last_30_days' => [$today->subDays(29), $today],
            'last_week' => [$today->startOfWeek(1)->subWeek(), $today->startOfWeek(1)->subDay()],
            'week_to_date' => [$today->startOfWeek(1), $today],
            'month_to_date' => [$today->startOfMonth(), $today],
            'year_to_date' => [$today->startOfYear(), $today],
            'all' => [null, null],
            default => throw new \InvalidArgumentException('Unknown reporting period.'),
        };
        return new self($key, $from?->toDateString(), $to?->toDateString());
    }

    public function label(): string
    {
        return self::OPTIONS[$this->key];
    }

    public function dates(): string
    {
        if ($this->timestampFrom || $this->timestampTo) {
            $timezone = config('reporting.timezone', 'Africa/Lagos');
            $format = fn (?string $value) => $value
                ? CarbonImmutable::parse($value, config('app.timezone', 'UTC'))->setTimezone($timezone)->format('M j, Y H:i')
                : null;
            return ($format($this->timestampFrom) ?: 'Beginning').' → '.($format($this->timestampTo) ?: 'Latest');
        }
        return $this->from === $this->to && $this->from ? $this->from
            : ($this->from ?: 'Beginning').' → '.($this->to ?: 'Latest');
    }

    public function apply($query, string $expression, string $timestampColumn = 'sales.created_at')
    {
        if ($this->timestampFrom) $query->where($timestampColumn, '>=', $this->timestampFrom);
        if ($this->timestampTo) $query->where($timestampColumn, '<=', $this->timestampTo);
        if ($this->timestampFrom || $this->timestampTo) return $query;
        if ($this->from) $query->whereRaw($expression.' >= ?', [$this->from]);
        if ($this->to) $query->whereRaw($expression.' <= ?', [$this->to]);
        return $query;
    }
}
