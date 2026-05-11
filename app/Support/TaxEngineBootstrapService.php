<?php

namespace App\Support;

use App\Models\TaxAccountMapping;
use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use Illuminate\Support\Facades\Schema;

class TaxEngineBootstrapService
{
    public function bootstrapDefaults(?int $companyId = null, ?int $userId = null, ?array $branch = null): array
    {
        $companyId = $companyId ?: (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0) ?: null;
        $userId = $userId ?: (int) (auth()->id() ?? 0) ?: null;
        $branchId = trim((string) ($branch['id'] ?? session('active_branch_id', '')));
        $branchName = trim((string) ($branch['name'] ?? session('active_branch_name', '')));

        $supported = TaxCountryCatalog::supportedCountries();
        $created = [
            'jurisdictions' => 0,
            'tax_codes' => 0,
            'withholding_rules' => 0,
            'account_mappings' => 0,
        ];

        foreach ($supported as $countryCode => $definition) {
            if (!Schema::hasTable('tax_jurisdictions')) {
                break;
            }

            $jurisdiction = TaxJurisdiction::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'country_code' => $countryCode,
                    'name' => $definition['name'],
                    'branch_id' => $branchId !== '' ? $branchId : null,
                ],
                [
                    'user_id' => $userId,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                    'currency_code' => $definition['currency'],
                    'filing_frequency' => $definition['filing_frequency'],
                    'filing_deadline_days' => $definition['filing_deadline_days'],
                    'is_active' => true,
                    'is_default' => $countryCode === 'NGA',
                ]
            );

            if ($jurisdiction->wasRecentlyCreated) {
                $created['jurisdictions']++;
            }
        }

        $preset = TaxCountryCatalog::nigeriaPresets();
        $jurisdiction = TaxJurisdiction::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'country_code' => 'NGA',
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
                'is_default' => true,
                'is_active' => true,
            ]
        );

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
                    'country_code' => 'NGA',
                    'is_active' => true,
                ])
            );

            if ($taxCode->wasRecentlyCreated) {
                $created['tax_codes']++;
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
                    'country_code' => 'NGA',
                    'is_active' => true,
                ])
            );

            if ($rule->wasRecentlyCreated) {
                $created['withholding_rules']++;
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
                        'country_code' => 'NGA',
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
            'country_code' => 'NGA',
        ]);

        return $created;
    }
}
