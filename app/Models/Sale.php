<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\GeoCurrency;

class Sale extends Model
{
    use Multitenantable, HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'company_id',
        'order_number', 'invoice_no', 'receipt_no', 'order_date', 'delivery_date',
        'customer_id', 'customer_name', 'user_id', 'terminal_id', 
        'subtotal', 'discount', 'tax', 'shipping_cost', 'total', 
        'paid', 'amount_paid', 'change_amount', 'balance', 
        'currency', 'amount_in_words', 'payment_method', 
        'payment_status', 'payment_details', 'order_status'
    ];

    /**
     * Type casting for attributes.
     */
    protected $casts = [
        'order_date'      => 'date',
        'delivery_date'   => 'date',
        'subtotal'        => 'decimal:2',
        'tax'             => 'decimal:2',
        'discount'        => 'decimal:2',
        'shipping_cost'   => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'balance'         => 'decimal:2',
    ];

    /**
     * Auto-generate Order Number on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->order_number)) {
                $sale->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            }
            if (empty($sale->order_date)) {
                $sale->order_date = today();
            }
        });
    }

    /**
     * RELATIONSHIPS
     */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    /**
     * SCOPES
     */

    public function scopePending($query) { return $query->where('order_status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('order_status', 'completed'); }
    public function scopeToday($query) { return $query->whereDate('order_date', today()); }
    public function scopeThisMonth($query) {
        return $query->whereMonth('order_date', now()->month)->whereYear('order_date', now()->year);
    }

    /**
     * CALCULATIONS & LOGIC
     */

    public function calculateTotal()
    {
        $calculatedSubtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $this->subtotal = $calculatedSubtotal;
        $this->total = ($calculatedSubtotal + $this->tax + ($this->shipping_cost ?? 0)) - ($this->discount ?? 0);
        
        return $this->total;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->amount_paid >= $this->total;
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'order_status'   => 'completed',
            'amount_paid'    => $this->total,
            'balance'        => 0
        ]);
    }

    /**
     * ACCESSORS
     */

    public function getPaidAmountSumAttribute()
    {
        return $this->payments->where('status', 'success')->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return $this->total - $this->amount_paid;
    }

    public function getFormattedTotalAttribute()
    {
        $sourceCurrency = GeoCurrency::normalizeCurrency((string) ($this->currency ?: 'NGN'));
        return GeoCurrency::format((float) $this->total, $sourceCurrency);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending'    => 'badge-warning',
            'processing' => 'badge-info',
            'completed'  => 'badge-success',
            'cancelled'  => 'badge-danger',
        ];
        return '<span class="badge ' . ($badges[$this->order_status] ?? 'badge-secondary') . '">' . ucfirst($this->order_status) . '</span>';
    }
}
