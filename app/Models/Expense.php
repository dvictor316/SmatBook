<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Support\GeoCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model{
    use \App\Traits\Multitenantable;
    use HasFactory;

protected $fillable = [
    'expense_id', 
    'company_name', 
    'reference', 
    'email', 
    'amount', 
    'payment_mode', 
    'payment_status', 
    'category_id',
    'category', 
    'notes', 
    'status', 
    'company_id',
    'project_id',
    'expense_claim_id',
    'branch_id',
    'branch_name',
    'created_by', 
    'image'
];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Boot method to generate expense ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_id)) {
                $expense->expense_id = 'EXP-' . str_pad(Expense::max('id') + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationship with user who created the expense
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    /**
     * Scope for pending expenses
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope for paid expenses
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    /**
     * Scope for overdue expenses
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return GeoCurrency::format((float) $this->amount, 'NGN');
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            if (Storage::disk('public')->exists('expenses/' . $this->image)) {
                return Storage::disk('public')->url('expenses/' . $this->image);
            }

            return asset('assets/img/expenses/' . $this->image);
        }
        return null;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        $classes = [
            'Pending' => 'bg-warning',
            'Paid' => 'bg-success',
            'Overdue' => 'bg-danger',
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'cancelled' => 'bg-secondary',
            'overdue' => 'bg-danger'
        ];

        return $classes[$this->status] ?? 'bg-secondary';
    }

    /**
     * Get payment mode icon
     */
    public function getPaymentModeIconAttribute()
    {
        $icons = [
            'Cash' => 'fas fa-money-bill-wave',
            'Credit Card' => 'fas fa-credit-card',
            'Bank Transfer' => 'fas fa-university',
            'Cheque' => 'fas fa-file-invoice-dollar',
            'PayPal' => 'fab fa-paypal',
            'Stripe' => 'fab fa-stripe'
        ];

        return $icons[$this->payment_mode] ?? 'fas fa-money-check-alt';
    }
}
