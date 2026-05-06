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
            if (app()->runningInConsole()) {
                return;
            }

            $companyId = Auth::user()?->company_id;
            if (!empty($companyId)) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }

            $table = $builder->getModel()->getTable();
            $requestBranchScope = strtolower(trim((string) request()->get('branch_scope', '')));
            $requestBranchId = trim((string) request()->get('branch_id', ''));
            $requestAllBranches = request()->boolean('all_branches')
                || $requestBranchScope === 'all'
                || strtolower($requestBranchId) === 'all';

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

        static::creating(function ($model) {
            if (empty($model->company_id) && !empty(Auth::user()?->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }

            // Auto-stamp branch from session so every new record is always
            // isolated to the correct branch (mirrors company_id stamping above).
            if (empty($model->branch_id)) {
                $branch = app(ActiveBranchResolver::class)->resolveBranchById(
                    trim((string) session('active_branch_id', '')),
                    Auth::user()
                );
                if ($branch) {
                    $model->branch_id = $branch['id'];
                }
            }
            if (empty($model->branch_name)) {
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
