<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const DEFAULT_USER_LIMITS = [
        'basic' => 3,
        'professional' => 5,
        'enterprise' => 8,
    ];

    public const SOLO_USER_LIMITS = [
        'basic' => 1,
        'professional' => 2,
        'enterprise' => 3,
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
            return static::SOLO_USER_LIMITS[static::normalizeTier($planName)] ?? 1;
        }

        return static::DEFAULT_USER_LIMITS[static::normalizeTier($planName)] ?? null;
    }

    public static function userSeatLabel(?int $limit): string
    {
        if ($limit === null) {
            return 'Custom seats';
        }

        return $limit === 1 ? '1 User' : $limit . ' Users';
    }

    public static function marketingBenefitsForTier(string $tier, ?int $userLimit = null): array
    {
        $seatLabel = static::userSeatLabel($userLimit ?? (static::DEFAULT_USER_LIMITS[$tier] ?? null));

        return match (strtolower($tier)) {
            'enterprise' => [
                $seatLabel,
                'Advanced financial statements',
                'Budgets, assets, and audit trail',
                'Priority implementation support',
                'Multi-team operational controls',
            ],
            'professional' => [
                $seatLabel,
                'Inventory and purchase workflows',
                'Multi-user approvals and controls',
                'Advanced reports and analytics',
                'Priority business support',
            ],
            default => [
                $seatLabel,
                'Core accounting and invoicing',
                'Sales tracking and daily reporting',
                'Clean workspace setup',
                'Standard support',
            ],
        };
    }

    public static function suggestedUpgradeForTier(?string $tier): ?string
    {
        return match (strtolower((string) $tier)) {
            'basic' => 'pro',
            'professional' => 'enterprise',
            default => null,
        };
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
