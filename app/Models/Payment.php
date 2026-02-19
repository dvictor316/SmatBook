<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Necessary for relationships
use App\Support\GeoCurrency;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'sale_id',
        'payment_account_id', // ADDED: Required to save the account link
        'reference',
        'amount',
        'method',
        'status',
        'note',
        'attachment',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            if (empty($payment->payment_id)) {
                $payment->payment_id = 'PAY-' . str_pad(Payment::max('id') + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationship with the Ledger/Bank Account (The fix for your error)
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    /**
     * Relationship with sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Relationship with user who created the payment
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- Accessors ---

    public function getFormattedAmountAttribute()
    {
        return GeoCurrency::format((float) $this->amount, 'NGN');
    }

    public function getStatusBadgeAttribute()
    {
        $classes = [
            'Pending' => 'bg-warning',
            'Completed' => 'bg-success',
            'Failed' => 'bg-danger',
            'Refunded' => 'bg-info',
            'Cancelled' => 'bg-secondary'
        ];
        return $classes[$this->status] ?? 'bg-secondary';
    }

    public function getMethodIconAttribute()
    {
        $icons = [
            'Cash' => 'fas fa-money-bill-wave',
            'Credit Card' => 'fas fa-credit-card',
            'Bank Transfer' => 'fas fa-university',
            'Cheque' => 'fas fa-file-invoice-dollar',
        ];
        return $icons[$this->method] ?? 'fas fa-money-check-alt';
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment ? asset('assets/img/payments/' . $this->attachment) : null;
    }

    // --- Scopes ---

    public function scopePending($query) { return $query->where('status', 'Pending'); }
    public function scopeCompleted($query) { return $query->where('status', 'Completed'); }
}
