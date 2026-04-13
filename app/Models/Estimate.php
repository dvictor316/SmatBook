<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Estimate extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'estimate_number',
        'customer_id',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'status',
        'notes',
        'company_id',
        'user_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Estimate $estimate) {
            $estimate->subtotal = self::normalizeMoney($estimate->subtotal ?? 0);
            $estimate->tax = self::normalizeMoney($estimate->tax ?? 0);
            $estimate->discount = self::normalizeMoney($estimate->discount ?? 0);
            $estimate->total_amount = self::calculateTotal(
                $estimate->subtotal,
                $estimate->tax,
                $estimate->discount
            );
        });
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Accessors
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'Sent':
                return 'success';
            case 'Draft':
                return 'warning';
            case 'Expired':
                return 'danger';
            case 'Accepted':
                return 'primary';
            case 'Declined':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    public function getAmountAttribute()
    {
        return $this->total_amount;
    }

    public static function calculateTotal($subtotal, $tax = 0, $discount = 0): float
    {
        return round(
            max(0, self::normalizeMoney($subtotal) + self::normalizeMoney($tax) - self::normalizeMoney($discount)),
            2
        );
    }

    public static function normalizeMoney($value): float
    {
        return round((float) $value, 2);
    }
}
