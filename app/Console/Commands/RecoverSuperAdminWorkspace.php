<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Support\RetireEmptySuperAdminWorkspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecoverSuperAdminWorkspace extends Command
{
    protected $signature = 'workspace:recover-super-admin
        {--user= : Super-admin user ID; omission audits all super admins}
        {--company= : Existing company to preview or restore as the primary workspace}
        {--retire-empty= : Automatically created empty company ID to retire while keeping --company}
        {--apply : Restore only the selected super admin primary link after verifying company ownership}';

    protected $description = 'Audit Enterprise sales locations; optionally restore a verified owned workspace without moving records.';

    public function handle(): int
    {
        foreach (['user', 'company', 'retire-empty'] as $option) {
            if ($this->option($option) !== null && (! ctype_digit((string) $this->option($option)) || (int) $this->option($option) < 1)) {
                $this->error('--'.$option.' must be a positive ID.');
                return self::FAILURE;
            }
        }
        if (($this->option('apply') || $this->option('retire-empty')) && (! $this->option('user') || ! $this->option('company'))) {
            $this->error('Applying requires both --user=ID and --company=ID. Nothing changed.');
            return self::FAILURE;
        }
        $users = User::withoutGlobalScopes()->whereIn(DB::raw('LOWER(role)'), ['super_admin', 'superadmin'])
            ->when(Schema::hasColumn('users', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->when($this->option('user'), fn ($q) => $q->where('id', (int) $this->option('user')))->get();
        if ($users->isEmpty()) {
            $this->error('No matching active super-admin account found.');
            return self::FAILURE;
        }
        foreach ($users as $user) {
            $this->info('Super admin #'.$user->id.' '.$user->name.'; primary company #'.((int) $user->company_id));
            $ids = Company::withoutGlobalScopes()->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('owner_id', $user->id))->pluck('id');
            $ids->push((int) $user->company_id);
            if ($this->option('company')) $ids->push((int) $this->option('company'));
            if ($this->option('retire-empty')) $ids->push((int) $this->option('retire-empty'));
            foreach (['sales', 'purchases', 'products', 'customers'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumns($table, ['user_id', 'company_id'])) {
                    $ids = $ids->merge(DB::table($table)->where('user_id', $user->id)->whereNotNull('company_id')->distinct()->pluck('company_id'));
                }
            }
            $companies = Company::withoutGlobalScopes()->whereIn('id', $ids->filter()->unique())->orderBy('id')->get();
            $rows = [];
            foreach ($companies as $company) {
                $rows[] = [$company->id, $company->name ?: $company->company_name,
                    $this->owned($user, $company) ? 'VERIFIED OWNED' : 'REVIEW: ownership unverified',
                    $this->count('sales', $company->id), $this->count('purchases', $company->id),
                    $this->count('products', $company->id), $this->count('customers', $company->id)];
            }
            $this->table(['Company ID', 'Business', 'Ownership', 'Sales', 'Purchases', 'Products', 'Customers'], $rows);
            $this->line('Counts are existing non-deleted rows, across all branches. Creator ID alone is not ownership proof.');
            foreach ($companies->filter(fn ($company) => $this->owned($user, $company)) as $company) {
                if (! Schema::hasTable('sales') || ! Schema::hasColumn('sales', 'company_id')) continue;
                $branchColumns = array_values(array_filter(['branch_id', 'branch_name'], fn ($column) => Schema::hasColumn('sales', $column)));
                if (! $branchColumns) continue;
                $query = DB::table('sales')->where('company_id', $company->id);
                if (Schema::hasColumn('sales', 'deleted_at')) $query->whereNull('deleted_at');
                $branches = $query->select($branchColumns)->selectRaw('COUNT(*) as record_count')->groupBy($branchColumns)->get();
                if ($branches->isNotEmpty()) {
                    $this->line('Sales branches for company #'.$company->id.':');
                    $this->table([...$branchColumns, 'Sales'], $branches->map(fn ($row) => [...array_map(fn ($column) => $row->{$column} ?: '(unassigned)', $branchColumns), $row->record_count])->all());
                }
            }
            if (Schema::hasTable('sales') && Schema::hasColumns('sales', ['user_id', 'company_id'])) {
                $unassigned = DB::table('sales')->where('user_id', $user->id)->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', 0));
                if (Schema::hasColumn('sales', 'deleted_at')) $unassigned->whereNull('deleted_at');
                $this->line('Sales created by this account without a company: '.$unassigned->count().' (review only; not automatically reassigned).');
            }
        }
        if (! $this->option('apply') && $this->option('retire-empty')) {
            try {
                app(RetireEmptySuperAdminWorkspace::class)->retire($users->first(),
                    Company::withoutGlobalScopes()->findOrFail((int) $this->option('company')),
                    (int) $this->option('retire-empty'), false);
                $this->info('Preview: the empty auto-created workspace can be retired; the original business will be kept.');
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }
        }
        if (! $this->option('apply')) {
            $this->info('READ ONLY: no accounts, records, branches or plans changed.');
            return self::SUCCESS;
        }
        try {
            DB::transaction(function () {
                $user = User::withoutGlobalScopes()->lockForUpdate()->findOrFail((int) $this->option('user'));
                $company = Company::withoutGlobalScopes()->lockForUpdate()->findOrFail((int) $this->option('company'));
                if (! in_array(strtolower((string) $user->role), ['super_admin', 'superadmin'], true) || ! $this->owned($user, $company)
                    || $user->deleted_at || $company->deleted_at) {
                    throw new \RuntimeException('Ownership is not verified. Refusing to link another business or transfer any records.');
                }
                if ($this->option('retire-empty')) {
                    app(RetireEmptySuperAdminWorkspace::class)->retire($user, $company, (int) $this->option('retire-empty'), true);
                    ActivityLog::withoutEvents(fn () => ActivityLog::record('Workspace', 'empty_retired',
                        'Retired empty auto-created company #'.$this->option('retire-empty').'; kept company #'.$company->id.'.',
                        ['user_id' => $user->id, 'company_id' => $company->id]));
                }
                if (\App\Models\Setting::where('key', 'workspace_retired_company_'.$company->id)->exists()) {
                    throw new \RuntimeException('Cannot restore a retired workspace as the primary business.');
                }
                $previous = (int) $user->company_id;
                if ($previous !== (int) $company->id) {
                    DB::table('users')->where('id', $user->id)->update(['company_id' => $company->id]);
                    ActivityLog::withoutEvents(fn () => ActivityLog::record('Workspace', 'primary_restored', 'Restored verified super-admin primary workspace from #'.$previous.' to #'.$company->id.'.', [
                        'user_id' => $user->id, 'company_id' => $company->id,
                        'properties' => ['previous_company_id' => $previous, 'restored_company_id' => $company->id],
                    ]));
                }
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        if ($this->option('retire-empty')) $this->info('Empty duplicate retired from the business selector; its company row and audit history were retained.');
        $this->info('Primary workspace restored. Sign out, sign in, open Business and choose All Branches. No business records moved or deleted.');
        return self::SUCCESS;
    }

    private function owned(User $user, Company $company): bool
    {
        $owners = array_filter([(int) $company->user_id, (int) $company->owner_id]);
        return $owners !== [] && count(array_filter($owners, fn ($id) => $id !== (int) $user->id)) === 0;
    }

    private function count(string $table, int $companyId): string
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) return 'n/a';
        $query = DB::table($table)->where('company_id', $companyId);
        if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull('deleted_at');
        return (string) $query->count();
    }
}
