<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     * Expanded to include all extended fields for Billing, Shipping, and Banking.
     */
    protected $fillable = [
        // Basic Info
        'customer_name', 
        'email', 
        'phone', 
        'address', 
        'status', 
        'balance', 
        'credit_limit',
        'image',
        'currency',
        'website',
        'notes',

        // Billing Details
        'billing_name',
        'billing_address_line1',
        'billing_address_line2',
        'billing_country',
        'billing_city',
        'billing_state',
        'billing_pincode',

        // Shipping Details
        'shipping_name',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_country',
        'shipping_city',
        'shipping_state',
        'shipping_pincode',

        // Bank Details
        'bank_name',
        'branch',
        'account_holder',
        'account_number',
        'ifsc'
    ];

    /**
     * Relationships
     */

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Accessors
     */

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status ?? 'inactive');
    }

    public function getFormattedBalanceAttribute(): string
    {
        // Automatically uses the saved currency or defaults to Naira
        $symbol = $this->currency ?: '₦';
        return $symbol . number_format($this->balance, 2);
    }
}
