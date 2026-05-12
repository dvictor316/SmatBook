<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\TenantScoped;

class Supplier extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'supplier_name',
        'company_name',
        'email',
        'phone',
        'address',
        'currency',
        'website',
        'notes',
        'status',
        'opening_balance',
        'opening_balance_date',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id');
    }
}
