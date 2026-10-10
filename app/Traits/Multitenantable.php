<?php

namespace App\Traits;

use App\Support\ActiveBranchResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait Multitenantable {
    protected static function bootMultitenantable() {
        // Always register scope/creating hooks; decide per request at runtime.
        static::addGlobalScope('company_id', function (Builder $builder) {
            if (app()->runningInConsole() && ! config('tenancy.enforce_scopes_in_console', false)) {
                return;
            }

            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();
            $role = strtolower((string) ($user->role ?? ''));
            $isSuperAdmin = in_array($role, ['super_admin', 'superadmin'], true);
            $companyId = (int) (session('current_tenant_id') ?? $user->company_id ?? 0);

            if ($isSuperAdmin && request()->is('superadmin*') && $companyId === 0) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $userId = (int) ($user->id ?? 0);
            $hasCompany = Schema::hasColumn($table, 'company_id');
            $hasUser = Schema::hasColumn($table, 'user_id');

            if ($hasCompany && $companyId > 0) {
                $builder->where(function ($query) use ($table, $companyId, $hasUser, $userId) {
                    $query->where($table . '.company_id', $companyId);

                    if ($hasUser && $userId > 0) {
                        $query->orWhere(function ($legacy) use ($table, $userId) {
                            $legacy->whereNull($table . '.company_id')
                                ->where($table . '.user_id', $userId);
                        });
                    }
                });
            } elseif ($hasUser && $userId > 0) {
                $builder->where($table . '.user_id', $userId);
            } elseif ($hasCompany || $hasUser) {
                $builder->whereRaw('1 = 0');
            }

            $requestBranchId = trim((string) request()->get('branch_id', ''));
            $requestAllBranches = strtolower(trim((string) session('active_branch_scope', ''))) === 'all';

            if ($requestAllBranches) {
                return;
            }

            $activeBranchId = trim((string) session('active_branch_id', ''));
            $activeBranchName = trim((string) session('active_branch_name', ''));

            if ($requestBranchId !== '') {
                $requestBranch = app(ActiveBranchResolver::class)->resolveBranchById($requestBranchId, Auth::user());
                if ($requestBranch) {
                    $activeBranchId = trim((string) ($requestBranch['id'] ?? ''));
                    $activeBranchName = trim((string) ($requestBranch['name'] ?? ''));
                }
            }

            if ($activeBranchId === '' && $activeBranchName === '' && $companyId && Schema::hasTable('settings')) {
                $branchKey = 'branches_json_company_' . $companyId;
                $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
                $branches = json_decode($rawBranches, true) ?: [];
                $firstBranch = collect($branches)->first();
                if ($firstBranch) {
                    $activeBranchId = trim((string) ($firstBranch['id'] ?? ''));
                    $activeBranchName = trim((string) ($firstBranch['name'] ?? ''));
                }
            }

            if ($activeBranchId !== '' || $activeBranchName !== '') {
                $hasBranchId = Schema::hasColumn($table, 'branch_id');
                $hasBranchName = Schema::hasColumn($table, 'branch_name');

                if ($hasBranchId && $activeBranchId !== '' && !ctype_digit($activeBranchId)) {
                    try {
                        $branchColumnType = strtolower((string) Schema::getColumnType($table, 'branch_id'));
                        if (!in_array($branchColumnType, ['string', 'text', 'char', 'varchar'], true)) {
                            $hasBranchId = false;
                        }
                    } catch (\Throwable) {
                        $hasBranchId = false;
                    }
                }

                $builder->where(function ($q) use ($table, $hasBranchId, $hasBranchName, $activeBranchId, $activeBranchName) {
                    if ($hasBranchId && $activeBranchId !== '') {
                        $q->where($table . '.branch_id', $activeBranchId);

                        if ($hasBranchName && $activeBranchName !== '') {
                            $q->orWhere(function ($legacy) use ($table, $activeBranchName) {
                                $legacy->where(function ($emptyBranchId) use ($table) {
                                    $emptyBranchId->whereNull($table . '.branch_id')
                                        ->orWhere($table . '.branch_id', '');
                                })->where($table . '.branch_name', $activeBranchName);
                            });
                        }

                        return;
                    }

                    if ($hasBranchName && $activeBranchName !== '') {
                        $q->where($table . '.branch_name', $activeBranchName);
                    }
                });
            }
        });

        static::saving(function ($model) {
            if ((app()->runningInConsole() && ! config('tenancy.enforce_scopes_in_console', false)) || ! Auth::check()) {
                return;
            }

            $table = $model->getTable();
            $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
            if (Schema::hasColumn($table, 'company_id') && $companyId > 0) {
                $model->company_id = $companyId;
            }

            if ($model->exists) {
                return;
            }

            // Auto-stamp branch from session so every new record is always
            // isolated to the correct branch (mirrors company_id stamping above).
            if (Schema::hasColumn($table, 'branch_id') && empty($model->branch_id)) {
                $branch = app(ActiveBranchResolver::class)->resolveBranchById(
                    trim((string) session('active_branch_id', '')),
                    Auth::user()
                );
                if ($branch) {
                    $model->branch_id = $branch['id'];
                }
            }
            if (Schema::hasColumn($table, 'branch_name') && empty($model->branch_name)) {
                $branch = app(ActiveBranchResolver::class)->resolveBranchById(
                    trim((string) session('active_branch_id', '')),
                    Auth::user()
                );
                if ($branch) {
                    $model->branch_name = $branch['name'];
                } else {
                    $branchName = trim((string) session('active_branch_name', ''));
                    if ($branchName !== '') {
                        $model->branch_name = $branchName;
                    }
                }
            }
        });
    }
}
