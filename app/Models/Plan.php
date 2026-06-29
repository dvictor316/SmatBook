<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const DEFAULT_USER_LIMITS = [
        'starter' => 1,
        'basic' => 3,
        'professional' => 5,
        'enterprise' => 8,
    ];

    public const SOLO_USER_LIMITS = [
        'starter' => 1,
        'basic' => 1,
        'professional' => 2,
        'enterprise' => 3,
    ];

    public const DEFAULT_BRANCH_LIMITS = [
        'starter' => 1,
        'basic' => 2,
        'professional' => 5,
        'enterprise' => 8,
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

        if (str_contains($value, 'starter')) {
            return 'starter';
        }

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

    public static function defaultBranchLimitForName(?string $planName): ?int
    {
        return static::DEFAULT_BRANCH_LIMITS[static::normalizeTier($planName)] ?? 1;
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
        $normalizedTier = strtolower($tier) === 'pro' ? 'professional' : strtolower($tier);
        $seatLabel = static::userSeatLabel($userLimit ?? (static::DEFAULT_USER_LIMITS[$normalizedTier] ?? null));

        return match ($normalizedTier) {
            'enterprise' => [
                $seatLabel,
                'Full accounting suite with tax, bank, and audit controls',
                'Budgets, fixed assets, payroll, and compliance workflows',
                'Departments, cost centers, and period-close task controls',
                'Enterprise reporting, approvals, and operational governance',
            ],
            'starter' => [
                $seatLabel,
                'POS checkout with receipt printing and cashier history',
                'Product catalog, stock overview, and low-stock visibility',
                'Expiry-date tracking and sell-time expiry alerts',
                'Starter workspace for fast retail operations',
            ],
            'professional' => [
                $seatLabel,
                'Everything in Basic plus advanced inventory tools',
                'Price lists, returns, requisitions, GRN, and branch workflows',
                'Expiry alerts, reorder controls, and richer stock management',
                'Cash flow, forecasting, and scheduled reporting',
                'Operational controls for growing multi-user teams',
            ],
            default => [
                $seatLabel,
                'POS, invoices, quotations, and customer account workflows',
                'Suppliers, purchases, expenses, payments, and stock tracking',
                'Core accounting tools with business reporting and ledgers',
                'Workspace collaboration and standard support',
            ],
        };
    }

    public static function marketingCardCatalog(): array
    {
        return [
            'starter' => [
                'label' => 'Starter POS',
                'description' => 'For businesses that need a focused sales counter, product shelf, and stock visibility.',
                'featured' => false,
                'from_price' => 1000,
                'team_price' => 1000,
                'solo_users' => 1,
                'team_users' => 1,
                'benefits' => static::marketingBenefitsForTier('starter', 1),
            ],
            'basic' => [
                'label' => 'Basic Core',
                'description' => 'For small teams running day-to-day sales, invoicing, purchases, and reporting together.',
                'featured' => false,
                'from_price' => 3000,
                'team_price' => 5500,
                'solo_users' => 1,
                'team_users' => 3,
                'benefits' => static::marketingBenefitsForTier('basic', 3),
            ],
            'pro' => [
                'label' => 'Pro Engine',
                'description' => 'For growing operations that need stronger controls, advanced inventory, and richer reporting.',
                'featured' => true,
                'from_price' => 7000,
                'team_price' => 19500,
                'solo_users' => 2,
                'team_users' => 5,
                'benefits' => static::marketingBenefitsForTier('professional', 5),
            ],
            'enterprise' => [
                'label' => 'Institutional',
                'description' => 'For larger organizations that need enterprise accounting, compliance, and operational governance.',
                'featured' => false,
                'from_price' => 15000,
                'team_price' => 28500,
                'solo_users' => 3,
                'team_users' => 8,
                'benefits' => static::marketingBenefitsForTier('enterprise', 8),
            ],
        ];
    }

    public static function suggestedUpgradeForTier(?string $tier): ?string
    {
        return match (strtolower((string) $tier)) {
            'starter' => 'basic',
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

    public function resolvedBranchLimit(): ?int
    {
        $features = strtolower((string) ($this->features ?? ''));

        if ($features !== '') {
            if (str_contains($features, 'unlimited branch')) {
                return null;
            }

            if (str_contains($features, 'single branch')) {
                return 1;
            }

            if (preg_match('/up to\s+(\d+)\s+branches?/', $features, $matches)) {
                return (int) $matches[1];
            }

            if (preg_match('/(\d+)\s+branches?/', $features, $matches)) {
                return (int) $matches[1];
            }
        }

        return static::defaultBranchLimitForName($this->name);
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
