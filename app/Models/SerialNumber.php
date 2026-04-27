<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'product_id', 'lot_id', 'serial_number',
        'status', 'sold_to_customer_id', 'sold_date',
        'warranty_expiry', 'notes',
    ];

    protected $casts = [
        'sold_date'       => 'date',
        'warranty_expiry' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lot()
    {
        return $this->belongsTo(ProductLot::class, 'lot_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
