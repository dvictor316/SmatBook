<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolioItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id','property_id','folio_id','stay_id','reservation_id','description','amount','type','posted_by'
    ];

    public function folio()
    {
        return $this->belongsTo(GuestFolio::class, 'folio_id');
    }
}
