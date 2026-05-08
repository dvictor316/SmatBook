<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\GeoCurrency;
use App\Models\Traits\TenantScoped;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    private const BRANCH_TOLERANCE_TYPES = [
        self::TYPE_SALE,
        self::TYPE_PURCHASE,
        self::TYPE_PAYMENT,
        self::TYPE_RECEIPT,
        self::TYPE_JOURNAL,
        self::TYPE_ADJUSTMENT,
        self::TYPE_OPENING_BALANCE,
        'Expense',
    ];

    protected $fillable = [
        'account_id',
        'company_id',
        'branch_id',
        'branch_name',
        'transaction_date',
        'reference',
        'description',
        'debit',
        'credit',
        'balance',
        'transaction_type',
        'related_id',
        'related_type',
        'user_id'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Transaction types
     */
    const TYPE_SALE = 'Sale';
    const TYPE_PURCHASE = 'Purchase';
    const TYPE_PAYMENT = 'Payment';
    const TYPE_RECEIPT = 'Receipt';
    const TYPE_JOURNAL = 'Journal Entry';
    const TYPE_ADJUSTMENT = 'Adjustment';
    const TYPE_OPENING_BALANCE = 'Opening Balance';

    /**
     * Get the account this transaction belongs to
     */
    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Get the user who created this transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic)
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Scope for transactions within date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Get formatted debit amount
     */
    public function getFormattedDebitAttribute()
    {
        return $this->debit > 0 ? GeoCurrency::format((float) $this->debit, 'NGN') : '-';
    }

    /**
     * Get formatted credit amount
     */
    public function getFormattedCreditAttribute()
    {
        return $this->credit > 0 ? GeoCurrency::format((float) $this->credit, 'NGN') : '-';
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute()
    {
        return GeoCurrency::format((float) $this->balance, 'NGN');
    }

    /**
     * Boot method to update account balance after transaction
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($transaction) {
            self::guardBranchIntegrity($transaction);
        });

        static::created(function ($transaction) {
            $account = $transaction->account()->withoutGlobalScopes()->first();
            $account?->updateBalance();
        });

        static::updated(function ($transaction) {
            $account = $transaction->account()->withoutGlobalScopes()->first();
            $account?->updateBalance();
        });

        static::deleted(function ($transaction) {
            $account = $transaction->account()->withoutGlobalScopes()->first();
            $account?->updateBalance();
        });
    }

    private static function guardBranchIntegrity(self $transaction): void
    {
        if (!Schema::hasColumn('transactions', 'branch_id')) {
            return;
        }

        if (!self::requiresBranchContext($transaction)) {
            return;
        }

        $companyId = (int) ($transaction->company_id ?? 0);
        $branchId = trim((string) ($transaction->branch_id ?? ''));
        $branchName = trim((string) ($transaction->branch_name ?? ''));

        if ($branchId === '' && $branchName === '') {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch is required for accounting transactions.',
            ]);
        }

        $validBranches = self::configuredBranches($companyId);
        if ($validBranches->isEmpty()) {
            return;
        }

        if ($branchId !== '') {
            $match = $validBranches->first(fn ($branch) => (string) $branch['id'] === $branchId);
            if (!$match) {
                throw ValidationException::withMessages([
                    'branch_id' => 'The selected branch is invalid for this company.',
                ]);
            }

            if (Schema::hasColumn('transactions', 'branch_name')) {
                $transaction->branch_name = (string) $match['name'];
            }
            return;
        }

        if ($branchName !== '') {
            $match = $validBranches->first(fn ($branch) => (string) $branch['name_lc'] === strtolower($branchName));
            if (!$match) {
                throw ValidationException::withMessages([
                    'branch_name' => 'The selected branch name is invalid for this company.',
                ]);
            }

            $transaction->branch_id = (string) $match['id'];
            $transaction->branch_name = (string) $match['name'];
        }
    }

    private static function requiresBranchContext(self $transaction): bool
    {
        $type = trim((string) ($transaction->transaction_type ?? ''));
        $relatedType = trim((string) ($transaction->related_type ?? ''));

        if ($relatedType === \App\Models\IntercompanyTransaction::class) {
            return false;
        }

        return in_array($type, self::BRANCH_TOLERANCE_TYPES, true) || $type !== '';
    }

    private static function configuredBranches(int $companyId)
    {
        if ($companyId <= 0 || !Schema::hasTable('settings')) {
            return collect();
        }

        $raw = (string) (DB::table('settings')->where('key', 'branches_json_company_' . $companyId)->value('value') ?? '');

        return collect(json_decode($raw, true) ?: [])
            ->filter(fn ($branch) => !empty($branch['id']) || !empty($branch['name']))
            ->map(fn ($branch) => [
                'id' => trim((string) ($branch['id'] ?? '')),
                'name' => trim((string) ($branch['name'] ?? '')),
                'name_lc' => strtolower(trim((string) ($branch['name'] ?? ''))),
            ])
            ->values();
    }
}
