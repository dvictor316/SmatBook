<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forecast extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'type', 'scenario',
        'period_start', 'period_end', 'frequency', 'status',
        'total_forecast_amount', 'actual_amount', 'assumptions', 'created_by',
    ];

    protected $casts = [
        'period_start'          => 'date',
        'period_end'            => 'date',
        'total_forecast_amount' => 'decimal:2',
        'actual_amount'         => 'decimal:2',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function items(): HasMany     { return $this->hasMany(ForecastItem::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)  { return $query->where('status', 'active'); }

    public function getVarianceAttribute(): float
    {
        return (float) ($this->actual_amount - $this->total_forecast_amount);
    }
}

class ForecastItem extends Model
{
    protected $table = 'forecast_items';

    protected $fillable = [
        'forecast_id', 'period_date', 'account_id', 'category',
        'forecast_amount', 'actual_amount', 'variance', 'notes',
    ];

    protected $casts = [
        'period_date'      => 'date',
        'forecast_amount'  => 'decimal:2',
        'actual_amount'    => 'decimal:2',
        'variance'         => 'decimal:2',
    ];

    public function forecast(): BelongsTo { return $this->belongsTo(Forecast::class); }
    public function account(): BelongsTo  { return $this->belongsTo(Account::class); }
}
