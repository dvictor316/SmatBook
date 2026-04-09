<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ActivityLog extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'user_id',
        'company_id',
        'branch_id',
        'branch_name',
        'module',
        'action',
        'description',
        'properties',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $module, string $action, string $description, array $context = []): ?self
    {
        $instance = new static();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return null;
        }

        $user = Auth::user();
        $rawPayload = [
            'user_id' => $context['user_id'] ?? $user?->id,
            'company_id' => $context['company_id'] ?? $user?->company_id ?? session('current_tenant_id'),
            'branch_id' => $context['branch_id'] ?? session('active_branch_id'),
            'branch_name' => $context['branch_name'] ?? session('active_branch_name'),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'properties' => array_key_exists('properties', $context)
                ? (is_string($context['properties']) ? $context['properties'] : json_encode($context['properties']))
                : null,
            'ip_address' => $context['ip_address'] ?? request()->ip(),
            'user_agent' => $context['user_agent'] ?? (string) request()->userAgent(),
        ];

        $payload = collect($rawPayload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();

        return static::withoutGlobalScopes()->create($payload);
    }
}
