<?php

namespace App\Support;

use App\Models\TaxAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TaxAuditService
{
    public static function record(?Model $auditable, string $action, ?array $oldValues = null, ?array $newValues = null, array $metadata = []): void
    {
        if (!class_exists(TaxAuditLog::class) || !Schema::hasTable('tax_audit_logs')) {
            return;
        }

        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0) ?: null;
        $userId = (int) (auth()->id() ?? 0) ?: null;
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        TaxAuditLog::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'branch_id' => $branchId !== '' ? $branchId : null,
            'branch_name' => $branchName !== '' ? $branchName : null,
            'auditable_type' => $auditable ? $auditable::class : 'tax.system',
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_by' => $userId,
        ]);
    }
}
