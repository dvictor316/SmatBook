<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\GeoCurrency;
use App\Models\Traits\TenantScoped;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'account_id',
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
        return $this->belongsTo(Account::class);
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

        static::created(function ($transaction) {
            $transaction->account->updateBalance();
        });

        static::updated(function ($transaction) {
            $transaction->account->updateBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->account->updateBalance();
        });
    }
}
