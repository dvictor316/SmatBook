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

        if ($scope['company_id'] > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $scope['company_id']);
        }

        if ($scope['branch_id'] !== '' || $scope['branch_name'] !== '') {
            $hasBranchId = Schema::hasColumn($table, 'branch_id');
            $hasBranchName = Schema::hasColumn($table, 'branch_name');

            if ($hasBranchId || $hasBranchName) {
                $query->where(function ($branchQuery) use ($table, $scope, $hasBranchId, $hasBranchName) {
                    if ($hasBranchId && $scope['branch_id'] !== '') {
                        $branchQuery->where("{$table}.branch_id", $scope['branch_id']);
                    }

                    if ($hasBranchName && $scope['branch_name'] !== '') {
                        $method = ($hasBranchId && $scope['branch_id'] !== '') ? 'orWhere' : 'where';
                        $branchQuery->{$method}("{$table}.branch_name", $scope['branch_name']);
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

        if ($scope['company_id'] > 0 && Schema::hasColumn($table, 'company_id')) {
            abort_unless((int) $model->getAttribute('company_id') === $scope['company_id'], 403);
        }

        if ($scope['branch_id'] !== '' && Schema::hasColumn($table, 'branch_id')) {
            abort_unless((string) $model->getAttribute('branch_id') === $scope['branch_id'], 403);
        }
    }
}
