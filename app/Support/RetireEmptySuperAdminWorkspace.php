<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetireEmptySuperAdminWorkspace
{
    /** Called inside the recovery transaction when applying. Preview never writes. */
    public function retire(User $user, Company $keep, int $retireId, bool $apply): void
    {
        $retire = Company::withoutGlobalScopes()->when($apply, fn ($q) => $q->lockForUpdate())->findOrFail($retireId);
        if (! $user->isSuperAdmin()
            || $retireId === (int) $keep->id || ! $this->owned($user, $keep) || ! $this->owned($user, $retire)
            || $user->deleted_at || $keep->deleted_at || $retire->deleted_at) {
            throw new \RuntimeException('Both workspaces must belong to this super admin, and the retained and retired IDs must differ.');
        }
        if ($retire->name !== ($user->name ?: 'Super Admin').' Enterprise Business'
            || $retire->domain_prefix || $retire->subdomain || $retire->domain) {
            throw new \RuntimeException('This does not match the automatically created private workspace. Nothing retired.');
        }
        if (Setting::where('key', 'workspace_retired_company_'.$keep->id)->exists()) {
            throw new \RuntimeException('The business to keep is already retired.');
        }
        // Check every company-scoped table, including deleted rows and future modules.
        // Activity history is retained and is not an operational business record.
        foreach (Schema::getTableListing() as $table) {
            if ($table === 'activity_logs' || ! Schema::hasColumn($table, 'company_id')) continue;
            $query = DB::table($table)->where('company_id', $retireId);
            if ($table === 'users') $query->where('id', '!=', $user->id);
            if ($query->exists()) {
                throw new \RuntimeException('Refusing retirement: '.$table.' contains records for company #'.$retireId.'.');
            }
        }
        foreach (Setting::where('key', 'like', '%company_'.$retireId)->pluck('key') as $key) {
            if (! in_array($key, ['branches_json_company_'.$retireId, 'workspace_retired_company_'.$retireId], true)) {
                throw new \RuntimeException('Refusing retirement: the workspace has saved business settings.');
            }
        }
        if (! $apply) return;
        Setting::updateOrCreate(['key' => 'workspace_retired_company_'.$retireId], ['value' => json_encode([
            'user_id' => $user->id, 'kept_company_id' => $keep->id, 'retired_at' => now()->toIso8601String(),
        ])]);
        DB::table('companies')->where('id', $retireId)->update(['status' => 'inactive']);
    }

    private function owned(User $user, Company $company): bool
    {
        $owners = array_filter([(int) $company->user_id, (int) $company->owner_id]);
        return $owners !== [] && count(array_filter($owners, fn ($id) => $id !== (int) $user->id)) === 0;
    }
}
