<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    // Add the fields that are in your purchase_returns table
    protected $fillable = [
        'purchase_id',
        'return_no',
        'vendor_id',
        'amount',
        'reason'
    ];
    
    public function vendor() {
    return $this->belongsTo(Vendor::class, 'vendor_id');
}

    /**
     * Link back to the original Purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}