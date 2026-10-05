<?php

namespace App\Support;

use App\Models\TaxAccountMapping;
use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use Illuminate\Support\Facades\Schema;

class TaxEngineBootstrapService
{
    public function bootstrapDefaults(string $countryCode = 'NGA', ?int $companyId = null, ?int $userId = null, ?array $branch = null): array
    {
        $countryCode = strtoupper($countryCode);
        $preset = TaxCountryCatalog::presetsFor($countryCode);
        if ($preset === null) {
            throw new \InvalidArgumentException("No verified tax preset is available for {$countryCode}.");
        }

        $companyId = $companyId ?: (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0) ?: null;
        $userId = $userId ?: (int) (auth()->id() ?? 0) ?: null;
        $branchId = trim((string) ($branch['id'] ?? session('active_branch_id', '')));
        $branchName = trim((string) ($branch['name'] ?? session('active_branch_name', '')));

        $created = [
            'country_code' => $countryCode,
            'jurisdictions' => 0,
            'tax_codes' => 0,
            'withholding_rules' => 0,
            'account_mappings' => 0,
        ];

        $hasDefaultJurisdiction = TaxJurisdiction::query()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId !== '' ? $branchId : null)
            ->where('is_default', true)
            ->exists();

        $jurisdiction = TaxJurisdiction::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'country_code' => $countryCode,
                'name' => $preset['jurisdiction']['name'],
                'branch_id' => $branchId !== '' ? $branchId : null,
            ],
            [
                'user_id' => $userId,
                'branch_name' => $branchName !== '' ? $branchName : null,
                'region' => $preset['jurisdiction']['region'],
                'currency_code' => $preset['jurisdiction']['currency_code'],
                'filing_frequency' => $preset['jurisdiction']['filing_frequency'],
                'filing_deadline_days' => $preset['jurisdiction']['filing_deadline_days'],
                'tax_authority_name' => $preset['jurisdiction']['tax_authority_name'],
                'registration_threshold' => $preset['jurisdiction']['registration_threshold'],
                'portal_url' => $preset['jurisdiction']['portal_url'],
                'metadata' => $preset['jurisdiction']['metadata'],
                'is_default' => ! $hasDefaultJurisdiction,
                'is_active' => true,
            ]
        );

        if ($jurisdiction->wasRecentlyCreated) {
            $created['jurisdictions']++;
        } elseif (! $jurisdiction->is_active) {
            $jurisdiction->update(['is_active' => true]);
        }

        foreach ($preset['tax_codes'] as $taxCodePreset) {
            $taxCode = TaxCode::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'tax_jurisdiction_id' => $jurisdiction->id,
                    'code' => $taxCodePreset['code'],
                    'branch_id' => $branchId !== '' ? $branchId : null,
                ],
                array_merge($taxCodePreset, [
                    'user_id' => $userId,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                    'country_code' => $countryCode,
                    'is_active' => true,
                ])
            );

            if ($taxCode->wasRecentlyCreated) {
                $created['tax_codes']++;
            } elseif (! $taxCode->is_active) {
                $taxCode->update(['is_active' => true]);
            }
        }

        foreach ($preset['withholding_rules'] as $rulePreset) {
            $rule = WithholdingRule::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'tax_jurisdiction_id' => $jurisdiction->id,
                    'name' => $rulePreset['name'],
                    'branch_id' => $branchId !== '' ? $branchId : null,
                ],
                array_merge($rulePreset, [
                    'user_id' => $userId,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                    'country_code' => $countryCode,
                    'is_active' => true,
                ])
            );

            if ($rule->wasRecentlyCreated) {
                $created['withholding_rules']++;
            } elseif (! $rule->is_active) {
                $rule->update(['is_active' => true]);
            }
        }

        if (Schema::hasTable('tax_account_mappings')) {
            foreach ($preset['account_mappings'] as $mappingPreset) {
                $mapping = TaxAccountMapping::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'tax_jurisdiction_id' => $jurisdiction->id,
                        'tax_type' => $mappingPreset['tax_type'],
                        'role' => $mappingPreset['role'],
                        'branch_id' => $branchId !== '' ? $branchId : null,
                    ],
                    array_merge($mappingPreset, [
                        'user_id' => $userId,
                        'branch_name' => $branchName !== '' ? $branchName : null,
                        'country_code' => $countryCode,
                        'is_required' => true,
                    ])
                );

                if ($mapping->wasRecentlyCreated) {
                    $created['account_mappings']++;
                }
            }
        }

        TaxAuditService::record(null, 'tax.bootstrap_defaults', null, $created, [
            'company_id' => $companyId,
            'country_code' => $countryCode,
        ]);

        return $created;
    }
}
