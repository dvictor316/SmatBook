<?php

namespace App\Traits;

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
            $activeBranchId = trim((string) session('active_branch_id', ''));
            $activeBranchName = trim((string) session('active_branch_name', ''));

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
                if ($activeBranchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $builder->where($table . '.branch_id', $activeBranchId);
                } elseif ($activeBranchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $builder->where($table . '.branch_name', $activeBranchName);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id) && !empty(Auth::user()?->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}
