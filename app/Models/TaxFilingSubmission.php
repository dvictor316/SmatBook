<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxFilingSubmission extends Model
{
    protected $fillable = [
        'tax_filing_id', 'tax_authority_connection_id', 'company_id', 'created_by',
        'provider', 'environment', 'status', 'idempotency_key', 'authority_reference',
        'request_payload', 'response_payload', 'response_status', 'error_message',
        'submitted_at', 'acknowledged_at', 'last_checked_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function filing(): BelongsTo
    {
        return $this->belongsTo(TaxFiling::class, 'tax_filing_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(TaxAuthorityConnection::class, 'tax_authority_connection_id');
    }
}
