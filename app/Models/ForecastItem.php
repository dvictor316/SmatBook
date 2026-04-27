<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForecastItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'forecast_id', 'category', 'period_label',
        'forecasted_amount', 'actual_amount', 'notes',
    ];

    protected $casts = [
        'forecasted_amount' => 'decimal:2',
        'actual_amount'     => 'decimal:2',
    ];

    public function forecast()
    {
        return $this->belongsTo(Forecast::class);
    }

    public function getVarianceAttribute(): float
    {
        return $this->actual_amount - $this->forecasted_amount;
    }

    public function getVariancePercentAttribute(): ?float
    {
        if ($this->forecasted_amount == 0) {
            return null;
        }
        return round(($this->variance / $this->forecasted_amount) * 100, 2);
    }
}
