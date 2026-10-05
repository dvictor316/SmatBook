<?php

namespace Tests\Unit;

use App\Models\TaxAccountMapping;
use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use App\Support\TaxFilingCatalog;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TaxFilingCatalogTest extends TestCase
{
    public function test_nigeria_federal_options_use_configured_returns_and_exclude_state_paye(): void
    {
        $jurisdiction = new TaxJurisdiction([
            'country_code' => 'NGA',
            'region' => 'Federal',
            'currency_code' => 'NGN',
            'filing_frequency' => 'monthly',
            'filing_deadline_days' => 21,
            'tax_authority_name' => 'Nigeria Revenue Service',
        ]);
        $jurisdiction->setRelation('taxCodes', new Collection([
            new TaxCode(['type' => 'vat', 'filing_frequency' => 'monthly', 'filing_deadline_days' => 21, 'is_active' => true]),
            new TaxCode(['type' => 'corporate_income_tax', 'filing_frequency' => 'annual', 'filing_deadline_days' => 180, 'is_active' => true]),
        ]));
        $jurisdiction->setRelation('withholdingRules', new Collection([
            new WithholdingRule(['is_active' => true]),
        ]));
        $jurisdiction->setRelation('accountMappings', new Collection([
            new TaxAccountMapping(['tax_type' => 'paye']),
        ]));

        $options = app(TaxFilingCatalog::class)->optionsFor($jurisdiction);

        $this->assertArrayHasKey('vat', $options);
        $this->assertArrayHasKey('withholding', $options);
        $this->assertArrayHasKey('corporate_income_tax', $options);
        $this->assertArrayNotHasKey('paye', $options);
        $this->assertSame(['annual'], $options['corporate_income_tax']['frequencies']);
        $this->assertSame(6, $options['corporate_income_tax']['deadline_months']);
    }

    public function test_us_sales_tax_is_available_only_when_an_active_code_exists(): void
    {
        $jurisdiction = new TaxJurisdiction([
            'country_code' => 'USA',
            'region' => 'Multi-level',
            'currency_code' => 'USD',
            'filing_frequency' => 'annual',
            'tax_authority_name' => 'State Revenue Authority',
            'metadata' => ['requires_nexus_review' => true],
        ]);
        $jurisdiction->setRelation('taxCodes', new Collection([
            new TaxCode(['type' => 'sales_tax', 'filing_frequency' => 'monthly', 'is_active' => true]),
        ]));
        $jurisdiction->setRelation('withholdingRules', new Collection);
        $jurisdiction->setRelation('accountMappings', new Collection);

        $options = app(TaxFilingCatalog::class)->optionsFor($jurisdiction);

        $this->assertSame(['sales_tax'], array_keys($options));
        $this->assertSame(['monthly'], $options['sales_tax']['frequencies']);
        $this->assertSame('USD', $options['sales_tax']['currency']);
        $this->assertTrue($options['sales_tax']['requires_review']);
    }
}
