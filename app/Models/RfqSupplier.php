<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfqSupplier extends Model
{
    protected $fillable = [
        'rfq_id', 'supplier_id', 'supplier_name',
        'email', 'status', 'sent_at', 'responded_at',
        'total_quoted_amount', 'currency', 'notes',
    ];

    protected $casts = [
        'sent_at'             => 'datetime',
        'responded_at'        => 'datetime',
        'total_quoted_amount' => 'decimal:2',
    ];

    public function rfq()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
