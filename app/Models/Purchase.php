<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'branch_name',
        'purchase_no', 
        'supplier_id',
        'vendor_id', 
        'bank_id',      // Added this
        'tax_id',       // Added this
        'total_amount', 
        'tax_amount', 
        'paid_amount',  // Added this based on your tinker output
        'paid_at',      // Added this based on your tinker output
        'status'
    ];

    public function getBranchLabelAttribute(): ?string
    {
        return $this->branch_name ?: null;
    }

    /**
     * Relationship to the Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Relationship to Supplier (current schema)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Define the relationship to the Bank model
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
    
    /**
     * Define the relationship to the Tax model
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * Relationship to the items (Products in this purchase)
     */
    public function items()
    {
        // Note: Ensure your PurchaseItem model exists
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /**
     * Relationship to Purchase Returns
     */
    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }
} // Class ends here
