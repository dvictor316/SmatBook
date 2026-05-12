<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;

class PurchaseTransaction extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'amount',
        'transaction_type',
        'date',
        'reference',
        'description',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
