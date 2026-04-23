<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Traits\TenantScoped;

class Vendor extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vendors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
        'balance',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * You might add 'email_verified_at' casting here if you add that column later.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No specific casts needed for the fields above right now
    ];

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(VendorLedgerTransaction::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return Storage::url($this->logo);
        }

        return asset('assets/img/profiles/default-avatar.jpg');
    }
}
