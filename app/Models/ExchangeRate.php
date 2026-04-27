<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'base_currency', 'target_currency',
        'rate', 'effective_date', 'source', 'is_active', 'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active'      => 'boolean',
        'rate'           => 'decimal:6',
    ];

    // --- Tenant scoping ---
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // --- Helpers ---
    /**
     * Get the latest rate for a given currency pair in a company context.
     */
    public static function getRate(int $companyId, string $from, string $to, ?string $date = null): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();

        $rate = static::where('company_id', $companyId)
            ->where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->latest('effective_date')
            ->value('rate');

        if ($rate) {
            return (float) $rate;
        }

        // Try inverse
        $inverse = static::where('company_id', $companyId)
            ->where('base_currency', $to)
            ->where('target_currency', $from)
            ->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->latest('effective_date')
            ->value('rate');

        return $inverse ? (float) (1 / $inverse) : null;
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
