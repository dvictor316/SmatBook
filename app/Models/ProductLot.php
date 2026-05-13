<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'product_id', 'lot_number', 'batch_number',
        'manufacture_date', 'expiry_date', 'quantity_received', 'quantity_available',
        'quantity_used', 'status', 'grn_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'manufacture_date'    => 'date',
        'expiry_date'         => 'date',
        'quantity_received'   => 'decimal:4',
        'quantity_available'  => 'decimal:4',
        'quantity_used'       => 'decimal:4',
    ];

    public function product(): BelongsTo        { return $this->belongsTo(Product::class); }
    public function grn(): BelongsTo            { return $this->belongsTo(GoodsReceivedNote::class, 'grn_id'); }
    public function serialNumbers(): HasMany    { return $this->hasMany(SerialNumber::class, 'lot_id'); }

    public function isExpired(): bool           { return $this->expiry_date && $this->expiry_date->isPast(); }
    public function isExpiringWithinDays(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->between(now(), now()->addDays($days));
    }
}
