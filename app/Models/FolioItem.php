<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class FolioItem extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id','property_id','folio_id','stay_id','reservation_id',
        'description','posting_key','amount','quantity','unit_price','type','service_code','service_date',
        'source_type','source_id','payment_account_id','posted_by','meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'service_date' => 'date',
        'meta' => 'array',
    ];

    public function folio()
    {
        return $this->belongsTo(GuestFolio::class, 'folio_id');
    }
}
