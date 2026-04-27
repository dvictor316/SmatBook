<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBarcode extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'barcode',
        'barcode_type', 'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
