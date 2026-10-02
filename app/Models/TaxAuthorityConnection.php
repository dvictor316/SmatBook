<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxAuthorityConnection extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'branch_id', 'branch_name', 'country_code',
        'provider', 'environment', 'taxpayer_id', 'organization_id', 'client_id',
        'client_secret', 'access_token', 'refresh_token', 'token_expires_at',
        'scopes', 'is_active', 'last_connected_at', 'last_sync_at', 'last_error', 'metadata',
    ];

    protected $hidden = ['client_secret', 'access_token', 'refresh_token'];

    protected $casts = [
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(TaxFilingSubmission::class);
    }

    public function baseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://api.nrs.gov.ng'
            : 'https://sandbox.api.nrs.gov.ng';
    }
}
