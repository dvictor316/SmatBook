<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;

class VendorLedgerTransaction extends Model
{
    use TenantScoped;
    protected $fillable = [
        'vendor_id',
        'name',
        'reference',
        'mode',
        'amount',
    ];

    // Define the relationship back to the Vendor model
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
