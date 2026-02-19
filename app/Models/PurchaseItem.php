<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id', 
        'product_id', 
        'qty', 
        'unit_price'
    ];

    // Link back to the main Purchase
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Link to the Product details
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}