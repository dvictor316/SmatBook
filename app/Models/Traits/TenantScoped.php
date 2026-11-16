<?php

namespace App\Models\Traits;

use App\Support\ActiveBranchResolver;
use App\Support\SchemaColumnCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->runningInConsole() && ! config('tenancy.enforce_scopes_in_console', false)) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (! $user) {
                return;
            }

            $isSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
            $isSuperAdminArea = request()->is('superadmin*');

            /** @var Model $model */
            $model = $builder->getModel();
            $table = $model->getTable();
            $includeCompanyWideRecords = method_exists($model, 'includesCompanyWideRecords')
                && $model->includesCompanyWideRecords();

            $companyId = (int) (session('current_tenant_id') ?? $user->company_id ?? 0);
            $userId = (int) ($user->id ?? 0);

            if ($isSuperAdmin && $isSuperAdminArea && $companyId === 0) {
                return;
            }

            $schema = request()->attributes->get('product_import_cache_schema') ? app(SchemaColumnCache::class) : null;
            $hasColumn = fn (string $column) => $schema
                ? $schema->hasColumn($table, $column)
                : Schema::hasColumn($table, $column);
            $hasCompany = $hasColumn('company_id');
            $hasUser = $hasColumn('user_id');

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
                $requestBranch = app(ActiveBranchResolver::class)->resolveBranchById($requestBranchId, $user);
                if ($requestBranch) {
                    $activeBranchId = $requestBranch['id'];
                    $activeBranchName = $requestBranch['name'];
                }
            }
            if ($activeBranchId === '' && $activeBranchName === '' && $companyId > 0 && ($schema ? $schema->hasTable('settings') : Schema::hasTable('settings'))) {
                $branchKey = 'branches_json_company_'.$companyId;
                $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
                $branches = json_decode($rawBranches, true) ?: [];
                $firstBranch = collect($branches)
                    ->filter(fn ($branch) => ! empty($branch['id']) || ! empty($branch['name']))
                    ->first();
                if ($firstBranch) {
                    $activeBranchId = trim((string) ($firstBranch['id'] ?? ''));
                    $activeBranchName = trim((string) ($firstBranch['name'] ?? ''));
                }
            }
            if ($activeBranchId !== '' || $activeBranchName !== '') {
                $hasBranchId = $hasColumn('branch_id');
                $hasBranchName = $hasColumn('branch_name');

                if ($hasBranchId && $activeBranchId !== '' && ! ctype_digit($activeBranchId)) {
                    try {
                        $branchColumnType = strtolower((string) ($schema
                            ? $schema->columnType($table, 'branch_id')
                            : Schema::getColumnType($table, 'branch_id')));
                        if (! in_array($branchColumnType, ['string', 'text', 'char', 'varchar'], true)) {
                            $hasBranchId = false;
                        }
                    } catch (\Throwable) {
                        $hasBranchId = false;
                    }
                }

                $builder->where(function ($q) use ($table, $hasBranchId, $hasBranchName, $activeBranchId, $activeBranchName, $includeCompanyWideRecords) {
                    if ($hasBranchId && $activeBranchId !== '') {
                        $q->where("{$table}.branch_id", $activeBranchId);

                        if ($hasBranchName && $activeBranchName !== '') {
                            $q->orWhere(function ($legacy) use ($table, $activeBranchName) {
                                $legacy->where(function ($emptyBranchId) use ($table) {
                                    $emptyBranchId->whereNull("{$table}.branch_id")
                                        ->orWhere("{$table}.branch_id", '');
                                })->where("{$table}.branch_name", $activeBranchName);
                            });
                        }

                    } elseif ($hasBranchName && $activeBranchName !== '') {
                        $q->where("{$table}.branch_name", $activeBranchName);
                    }

                    if ($includeCompanyWideRecords) {
                        $q->orWhere(function ($companyWide) use ($table, $hasBranchId, $hasBranchName) {
                            if ($hasBranchId) {
                                $companyWide->where(function ($emptyBranchId) use ($table) {
                                    $emptyBranchId->whereNull("{$table}.branch_id")
                                        ->orWhere("{$table}.branch_id", '');
                                });
                            }

                            if ($hasBranchName) {
                                $companyWide->where(function ($emptyBranchName) use ($table) {
                                    $emptyBranchName->whereNull("{$table}.branch_name")
                                        ->orWhere("{$table}.branch_name", '');
                                });
                            }
                        });
                    }
                });
            }
        });

        static::saving(function (Model $model) {
            if ((app()->runningInConsole() && ! config('tenancy.enforce_scopes_in_console', false)) || ! Auth::check()) {
                return;
            }

            $table = $model->getTable();
            $schema = request()->attributes->get('product_import_cache_schema') ? app(SchemaColumnCache::class) : null;
            if (! ($schema ? $schema->hasColumn($table, 'company_id') : Schema::hasColumn($table, 'company_id'))) {
                return;
            }

            $user = Auth::user();
            $companyId = (int) (session('current_tenant_id') ?? $user?->company_id ?? 0);
            $allowsUnassignedOwnership = method_exists($model, 'allowsUnassignedTenantOwnership')
                && $model->allowsUnassignedTenantOwnership();
            $managesOwnershipExplicitly = method_exists($model, 'managesTenantOwnershipExplicitly')
                && $model->managesTenantOwnershipExplicitly();

            if ($companyId > 0 && ! $allowsUnassignedOwnership && ! $managesOwnershipExplicitly) {
                $model->setAttribute('company_id', $companyId);
            }
        });
    }
}
