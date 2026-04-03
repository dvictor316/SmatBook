<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->runningInConsole()) {
                return;
            }

            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (!$user) {
                return;
            }

            $role = strtolower((string) ($user->role ?? ''));
            $isSuperAdmin = in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true);
            $isSuperAdminArea = request()->is('superadmin*');

            /** @var Model $model */
            $model = $builder->getModel();
            $table = $model->getTable();

            $companyId = (int) ($user->company_id ?? 0);
            $userId = (int) ($user->id ?? 0);

            if ($isSuperAdmin && $isSuperAdminArea && $companyId === 0) {
                return;
            }

            $hasCompany = Schema::hasColumn($table, 'company_id');
            $hasUser = Schema::hasColumn($table, 'user_id');

            if ($hasCompany && $companyId > 0) {
                $builder->where(function ($q) use ($table, $companyId, $hasUser, $userId) {
                    $q->where("{$table}.company_id", $companyId);

                    if ($hasUser) {
                        $q->orWhere(function ($sub) use ($table, $userId) {
                            $sub->whereNull("{$table}.company_id")
                                ->where("{$table}.user_id", $userId);
                        });
                    }
                });
                return;
            }

            if ($hasUser && $userId > 0) {
                $builder->where("{$table}.user_id", $userId);
            }

            $activeBranchId = trim((string) session('active_branch_id', ''));
            $activeBranchName = trim((string) session('active_branch_name', ''));
            if ($activeBranchId !== '' || $activeBranchName !== '') {
                $hasBranchId = Schema::hasColumn($table, 'branch_id');
                $hasBranchName = Schema::hasColumn($table, 'branch_name');

                if ($hasBranchId && $activeBranchId !== '') {
                    $builder->where("{$table}.branch_id", $activeBranchId);
                } elseif ($hasBranchName && $activeBranchName !== '') {
                    $builder->where("{$table}.branch_name", $activeBranchName);
                }
            }
        });
    }
}
