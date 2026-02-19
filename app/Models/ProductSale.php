<?php

// app/Models/ProductSale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    use HasFactory;

    // Define fillable fields, relationships, etc.
    protected $fillable = [
        'product_id', 'quantity', 'price',
    ];

    // Define relationships if needed
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
