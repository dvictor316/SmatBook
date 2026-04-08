<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class ActiveBranchResolver
{
    public function ensureSession(?Authenticatable $user = null): bool
    {
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId !== '' || $branchName !== '') {
            return true;
        }

        $branch = $this->resolveDefaultBranch($user);

        if (!$branch) {
            return false;
        }

        session([
            'active_branch_id' => $branch['id'],
            'active_branch_name' => $branch['name'],
        ]);

        return true;
    }

    public function resolveDefaultBranch(?Authenticatable $user = null): ?array
    {
        $branches = $this->branchesForUser($user);

        if ($branches->isEmpty()) {
            return null;
        }

        return $branches->firstWhere('is_active', true) ?: $branches->first();
    }

    private function branchesForUser(?Authenticatable $user = null): Collection
    {
        $companyId = (int) (
            data_get($user, 'company_id')
            ?? data_get($user, 'company.id')
            ?? session('current_tenant_id')
            ?? 0
        );

        $keys = $companyId > 0
            ? ["branches_json_company_{$companyId}", 'branches_json']
            : ['branches_json'];

        foreach ($keys as $key) {
            $raw = Setting::where('key', $key)->value('value');
            $decoded = json_decode((string) $raw, true);

            if (!is_array($decoded) || $decoded === []) {
                continue;
            }

            $branches = collect($decoded)
                ->map(function ($branch) {
                    return [
                        'id' => trim((string) ($branch['id'] ?? '')),
                        'name' => trim((string) ($branch['name'] ?? '')),
                        'is_active' => (bool) ($branch['is_active'] ?? true),
                    ];
                })
                ->filter(fn (array $branch) => $branch['id'] !== '' && $branch['name'] !== '')
                ->values();

            if ($branches->isNotEmpty()) {
                return $branches;
            }
        }

        return collect();
    }
}
