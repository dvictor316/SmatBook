<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'grn_number', 'purchase_order_id', 'supplier_id',
        'received_date', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function company(): BelongsTo        { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo       { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo  { return $this->belongsTo(Purchase::class, 'purchase_order_id'); }
    public function items(): HasMany            { return $this->hasMany(GrnItem::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
}

class GrnItem extends Model
{
    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id', 'product_id', 'product_name', 'ordered_quantity',
        'received_quantity', 'rejected_quantity', 'unit', 'unit_cost',
        'lot_number', 'serial_number', 'expiry_date',
    ];

    protected $casts = [
        'ordered_quantity'   => 'decimal:4',
        'received_quantity'  => 'decimal:4',
        'rejected_quantity'  => 'decimal:4',
        'unit_cost'          => 'decimal:4',
        'expiry_date'        => 'date',
    ];

    public function grn(): BelongsTo     { return $this->belongsTo(GoodsReceivedNote::class, 'grn_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
