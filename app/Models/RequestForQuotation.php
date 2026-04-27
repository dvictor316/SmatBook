<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestForQuotation extends Model
{
    use SoftDeletes;

    protected $table = 'request_for_quotations';

    protected $fillable = [
        'company_id', 'branch_id', 'rfq_number', 'purchase_requisition_id',
        'issue_date', 'closing_date', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'closing_date' => 'date',
    ];

    public function company(): BelongsTo            { return $this->belongsTo(Company::class); }
    public function purchaseRequisition(): BelongsTo { return $this->belongsTo(PurchaseRequisition::class); }
    public function rfqSuppliers(): HasMany          { return $this->hasMany(RfqSupplier::class); }
    public function items(): HasMany                 { return $this->hasMany(RfqItem::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
}

class RfqSupplier extends Model
{
    protected $table = 'rfq_suppliers';

    protected $fillable = [
        'rfq_id', 'supplier_id', 'quoted_date', 'total_quoted_amount',
        'status', 'is_selected', 'notes',
    ];

    protected $casts = [
        'quoted_date'        => 'date',
        'total_quoted_amount' => 'decimal:2',
        'is_selected'        => 'boolean',
    ];

    public function rfq(): BelongsTo      { return $this->belongsTo(RequestForQuotation::class, 'rfq_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}

class RfqItem extends Model
{
    protected $table = 'rfq_items';

    protected $fillable = [
        'rfq_id', 'product_id', 'product_name', 'quantity', 'unit',
        'quoted_unit_price', 'quoted_total', 'rfq_supplier_id',
    ];

    protected $casts = [
        'quantity'          => 'decimal:4',
        'quoted_unit_price' => 'decimal:4',
        'quoted_total'      => 'decimal:2',
    ];

    public function rfq(): BelongsTo     { return $this->belongsTo(RequestForQuotation::class, 'rfq_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
