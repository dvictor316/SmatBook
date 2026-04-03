<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Tenant extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The table associated with the model.
     * * @var string
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     * Based on your DB describe: id, user_id, name, slug, onboarding_step, is_active
     * * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'onboarding_step',
        'is_active',
    ];

    /**
     * RELATIONSHIP: Subscriptions
     * Reverse relationship to allow $tenant->subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id', 'user_id');
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}
