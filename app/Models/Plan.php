<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const DEFAULT_USER_LIMITS = [
        'basic' => 2,
        'professional' => 3,
        'enterprise' => null,
    ];

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'description',
        'features',
        'recommended',
        'icon',
        'status',
        'is_active',
        'user_limit',
        'expiry_date',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'is_active'   => 'boolean',
        'user_limit'  => 'integer',
        'expiry_date' => 'datetime',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Domain::class, 'package_name', 'name');
    }

    public static function normalizeTier(?string $planName): string
    {
        $value = strtolower(trim((string) $planName));

        if (str_contains($value, 'enterprise')) {
            return 'enterprise';
        }

        if (str_contains($value, 'professional') || $value === 'pro' || str_contains($value, 'pro ')) {
            return 'professional';
        }

        return 'basic';
    }

    public static function defaultUserLimitForName(?string $planName): ?int
    {
        $value = strtolower(trim((string) $planName));

        if (str_contains($value, 'solo') || str_contains($value, '1 user')) {
            return 1;
        }

        return static::DEFAULT_USER_LIMITS[static::normalizeTier($planName)] ?? null;
    }

    public function resolvedUserLimit(): ?int
    {
        if ($this->user_limit !== null) {
            return (int) $this->user_limit;
        }

        return static::defaultUserLimitForName($this->name);
    }

    public static function findByCatalogName(string $planName, string $billingCycle): ?self
    {
        $normalizedName = strtolower(trim($planName));
        $requiresSolo = str_contains($normalizedName, 'solo');
        $targetTier = static::normalizeTier($normalizedName);

        return static::query()
            ->whereRaw('LOWER(billing_cycle) = ?', [strtolower(trim($billingCycle))])
            ->get()
            ->first(function (self $candidate) use ($normalizedName, $requiresSolo, $targetTier) {
                $candidateName = strtolower(trim((string) $candidate->name));

                if ($candidateName === $normalizedName) {
                    return true;
                }

                if (static::normalizeTier($candidateName) !== $targetTier) {
                    return false;
                }

                return $requiresSolo === str_contains($candidateName, 'solo');
            });
    }
}
