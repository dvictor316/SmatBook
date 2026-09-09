<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function scopeContext(): array
    {
        return [
            'company_id' => (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0),
            'branch_id' => trim((string) session('active_branch_id', '')),
            'branch_name' => trim((string) session('active_branch_name', '')),
        ];
    }

    protected function applyTenantBranchScope(Builder $query, ?string $table = null): Builder
    {
        $table ??= $query->getModel()->getTable();
        $scope = $this->scopeContext();
        $user = Auth::user();
        $role = strtolower((string) ($user?->role ?? ''));
        $isSuperAdmin = in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true);
        $userId = (int) ($user?->id ?? 0);
        $hasCompany = Schema::hasColumn($table, 'company_id');
        $hasUser = Schema::hasColumn($table, 'user_id');

        if (!($isSuperAdmin && request()->is('superadmin*') && $scope['company_id'] === 0)) {
            if ($hasCompany && $scope['company_id'] > 0) {
                $query->where(function ($tenantQuery) use ($table, $scope, $hasUser, $userId) {
                    $tenantQuery->where("{$table}.company_id", $scope['company_id']);

                    if ($hasUser && $userId > 0) {
                        $tenantQuery->orWhere(function ($legacy) use ($table, $userId) {
                            $legacy->whereNull("{$table}.company_id")
                                ->where("{$table}.user_id", $userId);
                        });
                    }
                });
            } elseif ($hasUser && $userId > 0) {
                $query->where("{$table}.user_id", $userId);
            } elseif ($hasCompany || $hasUser) {
                $query->whereRaw('1 = 0');
            }
        }

        if ($scope['branch_id'] !== '' || $scope['branch_name'] !== '') {
            $hasBranchId = Schema::hasColumn($table, 'branch_id');
            $hasBranchName = Schema::hasColumn($table, 'branch_name');

            if ($hasBranchId || $hasBranchName) {
                $query->where(function ($branchQuery) use ($table, $scope, $hasBranchId, $hasBranchName) {
                    if ($hasBranchId && $scope['branch_id'] !== '') {
                        $branchQuery->where("{$table}.branch_id", $scope['branch_id']);
                        return;
                    }

                    if ($hasBranchName && $scope['branch_name'] !== '') {
                        $branchQuery->where("{$table}.branch_name", $scope['branch_name']);
                    }
                });
            }
        }

        return $query;
    }

    protected function authorizeTenantBranchModelAccess(Model $model): void
    {
        $scope = $this->scopeContext();
        $table = $model->getTable();
        $user = Auth::user();
        $role = strtolower((string) ($user?->role ?? ''));
        $isSuperAdmin = in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true);
        $userId = (int) ($user?->id ?? 0);
        $hasCompany = Schema::hasColumn($table, 'company_id');
        $hasUser = Schema::hasColumn($table, 'user_id');

        if (!($isSuperAdmin && request()->is('superadmin*') && $scope['company_id'] === 0)) {
            if ($hasCompany && $scope['company_id'] > 0) {
                $sameCompany = (int) $model->getAttribute('company_id') === $scope['company_id'];
                $legacyOwner = $hasUser
                    && (int) ($model->getAttribute('company_id') ?? 0) === 0
                    && (int) $model->getAttribute('user_id') === $userId;
                abort_unless($sameCompany || $legacyOwner, 403);
            } elseif ($hasUser) {
                abort_unless((int) $model->getAttribute('user_id') === $userId, 403);
            } elseif ($hasCompany) {
                abort(403);
            }
        }

        if ($scope['branch_id'] !== '' && Schema::hasColumn($table, 'branch_id')) {
            abort_unless((string) $model->getAttribute('branch_id') === $scope['branch_id'], 403);
            return;
        }

        if ($scope['branch_name'] !== '' && Schema::hasColumn($table, 'branch_name')) {
            abort_unless((string) $model->getAttribute('branch_name') === $scope['branch_name'], 403);
        }
    }
}
