<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class Account extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'code',
        'name',
        'company_id',
        'user_id',
        'type',
        'sub_type',
        'description',
        'opening_balance',
        'current_balance',
        'is_active',
        'parent_id'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Account types
     */
    const TYPE_ASSET = 'Asset';
    const TYPE_LIABILITY = 'Liability';
    const TYPE_EQUITY = 'Equity';
    const TYPE_REVENUE = 'Revenue';
    const TYPE_EXPENSE = 'Expense';

    /**
     * Sub-types for Assets
     */
    const SUBTYPE_CURRENT_ASSET = 'Current Asset';
    const SUBTYPE_FIXED_ASSET = 'Fixed Asset';
    const SUBTYPE_INTANGIBLE_ASSET = 'Intangible Asset';

    /**
     * Sub-types for Liabilities
     */
    const SUBTYPE_CURRENT_LIABILITY = 'Current Liability';
    const SUBTYPE_LONG_TERM_LIABILITY = 'Long-term Liability';

    /**
     * Get all transactions for this account
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get parent account
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get child accounts
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Scope for active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific account type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Calculate current balance
     */
    public function calculateBalance($upToDate = null)
    {
        $query = $this->transactions();
        
        if ($upToDate) {
            $query->where('transaction_date', '<=', $upToDate);
        }

        $debits = $query->sum('debit');
        $credits = $query->sum('credit');

        // For Assets and Expenses: Debit increases, Credit decreases
        if (in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE])) {
            return $this->opening_balance + $debits - $credits;
        }

        // For Liabilities, Equity, and Revenue: Credit increases, Debit decreases
        return $this->opening_balance + $credits - $debits;
    }

    /**
     * Update current balance
     */
    public function updateBalance()
    {
        $this->current_balance = $this->calculateBalance();
        $this->save();
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->current_balance, 2);
    }

    /**
     * Check if account is a debit account
     */
    public function isDebitAccount()
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE]);
    }

    /**
     * Check if account is a credit account
     */
    public function isCreditAccount()
    {
        return in_array($this->type, [self::TYPE_LIABILITY, self::TYPE_EQUITY, self::TYPE_REVENUE]);
    }
}
