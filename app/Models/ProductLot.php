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

class SerialNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'product_id', 'serial_number', 'status',
        'lot_id', 'grn_id', 'sale_id', 'customer_id', 'sold_date',
        'warranty_expiry', 'notes',
    ];

    protected $casts = [
        'sold_date'       => 'date',
        'warranty_expiry' => 'date',
    ];

    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function lot(): BelongsTo      { return $this->belongsTo(ProductLot::class, 'lot_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}

class ProductBarcode extends Model
{
    protected $table = 'product_barcodes';

    protected $fillable = [
        'company_id', 'product_id', 'barcode', 'barcode_type', 'is_primary',
    ];

    protected $casts = ['is_primary' => 'boolean'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
