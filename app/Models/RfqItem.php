<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_id', 'product_id', 'product_name',
        'quantity', 'unit', 'specifications',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function rfq()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
