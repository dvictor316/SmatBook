<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;

class ProductBranchStock extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'product_id',
        'company_id',
        'branch_id',
        'branch_name',
        'quantity',
        'is_available',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
