<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            } elseif ($hasUser && $userId > 0) {
                $builder->where("{$table}.user_id", $userId);
            }

            $requestBranchScope = (string) request()->get('branch_scope', '');
            $requestBranchId = (string) request()->get('branch_id', '');
            $requestAllBranches = request()->boolean('all_branches')
                || strtolower($requestBranchScope) === 'all'
                || strtolower($requestBranchId) === 'all';

            if ($requestAllBranches) {
                return;
            }

            $activeBranchId = trim((string) session('active_branch_id', ''));
            $activeBranchName = trim((string) session('active_branch_name', ''));

            if ($requestBranchId !== '') {
                $activeBranchId = trim($requestBranchId);
                $activeBranchName = '';
                if ($activeBranchId !== '' && $companyId > 0 && Schema::hasTable('settings')) {
                    $branchKey = 'branches_json_company_' . $companyId;
                    $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
                    $branches = json_decode($rawBranches, true) ?: [];
                    $branchMatch = collect($branches)->firstWhere('id', $activeBranchId);
                    if ($branchMatch) {
                        $activeBranchName = trim((string) ($branchMatch['name'] ?? ''));
                    }
                }
            }
            if ($activeBranchId === '' && $activeBranchName === '' && $companyId > 0 && Schema::hasTable('settings')) {
                $branchKey = 'branches_json_company_' . $companyId;
                $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
                $branches = json_decode($rawBranches, true) ?: [];
                $firstBranch = collect($branches)
                    ->filter(fn ($branch) => !empty($branch['id']) || !empty($branch['name']))
                    ->first();
                if ($firstBranch) {
                    $activeBranchId = trim((string) ($firstBranch['id'] ?? ''));
                    $activeBranchName = trim((string) ($firstBranch['name'] ?? ''));
                }
            }
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
