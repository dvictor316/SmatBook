<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActiveBranchResolver
{
    public function ensureSession(?Authenticatable $user = null): bool
    {
        if ($this->allBranchesScopeIsActive()) {
            session()->forget(['active_branch_id', 'active_branch_name']);

            return true;
        }

        if ($this->sessionBranchIsValid($user)) {
            return true;
        }

        session()->forget(['active_branch_id', 'active_branch_name']);

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

    public function sessionBranchIsValid(?Authenticatable $user = null): bool
    {
        if ($this->allBranchesScopeIsActive()) {
            return true;
        }

        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId === '' && $branchName === '') {
            return false;
        }

        if ($branchId !== '') {
            $branch = $this->resolveBranchById($branchId, $user);
            if (!$branch) {
                return false;
            }

            if ($branchName !== '' && $branchName !== $branch['name']) {
                return false;
            }

            return true;
        }

        return $this->branchesForUser($user)->contains(
            fn (array $branch) => $branch['name'] === $branchName
        );
    }

    public function resolveBranchById(string $branchId, ?Authenticatable $user = null): ?array
    {
        $branchId = trim($branchId);
        if ($branchId === '') {
            return null;
        }

        return $this->branchesForUser($user)->firstWhere('id', $branchId);
    }

    public function resolveDefaultBranch(?Authenticatable $user = null): ?array
    {
        $branches = $this->branchesForUser($user);

        if ($branches->isEmpty()) {
            return $this->seedDefaultBranch($user);
        }

        return $branches->firstWhere('is_active', true) ?: $branches->first();
    }

    public function seedDefaultBranch(?Authenticatable $user = null, ?string $preferredName = null): ?array
    {
        $companyId = $this->resolveCompanyId($user);
        if ($companyId <= 0) {
            return null;
        }

        $key = "branches_json_company_{$companyId}";
        $raw = Setting::where('key', $key)->value('value');
        $decoded = json_decode((string) $raw, true);
        $branches = collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($branch) => !empty($branch['id']) && !empty($branch['name']))
            ->values();

        if ($branches->isNotEmpty()) {
            $existing = $branches->firstWhere('is_active', true) ?: $branches->first();

            return [
                'id' => trim((string) ($existing['id'] ?? '')),
                'name' => trim((string) ($existing['name'] ?? '')),
                'is_active' => (bool) ($existing['is_active'] ?? true),
            ];
        }

        $company = Company::find($companyId);
        $branchName = trim((string) ($preferredName ?: 'Headquarters'));

        $branch = [
            'id' => (string) Str::uuid(),
            'name' => $branchName !== '' ? $branchName : 'Headquarters',
            'code' => $this->makeBranchCode($branchName !== '' ? $branchName : 'Headquarters'),
            'manager' => '',
            'phone' => '',
            'address' => '',
            'is_active' => true,
        ];

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode([$branch])]
        );

        return [
            'id' => (string) $branch['id'],
            'name' => (string) $branch['name'],
            'is_active' => true,
        ];
    }

    private function branchesForUser(?Authenticatable $user = null): Collection
    {
        $companyId = $this->resolveCompanyId($user);

        // Never fall back to the generic 'branches_json' key when a company_id is
        // resolved — that unscoped key could hold another tenant's branches and
        // cause cross-company data leakage.
        $keys = $companyId > 0
            ? ["branches_json_company_{$companyId}"]
            : ['branches_json'];

        foreach ($keys as $key) {
            $raw = Setting::where('key', $key)->value('value');
            $decoded = json_decode((string) $raw, true);

            if (!is_array($decoded) || $decoded === []) {
                continue;
            }

            $branches = collect($decoded)
                ->map(fn ($branch) => $this->normalizeBranch($branch))
                ->filter(fn (array $branch) => $branch['id'] !== '' && $branch['name'] !== '')
                ->values();

            if ($branches->isNotEmpty()) {
                return $branches;
            }
        }

        return collect();
    }

    private function resolveCompanyId(?Authenticatable $user = null): int
    {
        return (int) (
            data_get($user, 'company_id')
            ?? data_get($user, 'company.id')
            ?? session('current_tenant_id')
            ?? 0
        );
    }

    private function makeBranchCode(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));

        return $code !== '' ? $code . '-' . now()->format('Hi') : 'MAIN-' . now()->format('Hi');
    }

    private function normalizeBranch(array $branch): array
    {
        return [
            'id' => trim((string) ($branch['id'] ?? '')),
            'name' => trim((string) ($branch['name'] ?? '')),
            'is_active' => (bool) ($branch['is_active'] ?? true),
        ];
    }

    private function allBranchesScopeIsActive(): bool
    {
        return strtolower(trim((string) session('active_branch_scope', ''))) === 'all';
    }
}
