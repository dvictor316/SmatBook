<?php

namespace Tests\Unit;

use App\Support\TaxCountryCatalog;
use PHPUnit\Framework\TestCase;

class TaxCountryCatalogTest extends TestCase
{
    public function test_only_verified_country_packs_are_offered_for_installation(): void
    {
        $this->assertSame(['NGA', 'USA'], array_keys(TaxCountryCatalog::presetCountries()));
        $this->assertNull(TaxCountryCatalog::presetsFor('GBR'));
    }

    public function test_nigeria_pack_uses_nrs_vat_source_and_rate(): void
    {
        $preset = TaxCountryCatalog::presetsFor('NGA');
        $standardVat = collect($preset['tax_codes'])->firstWhere('code', 'NGA-VAT-STD');

        $this->assertSame('Nigeria Revenue Service', $preset['jurisdiction']['tax_authority_name']);
        $this->assertSame(7.5, $standardVat['rate']);
        $this->assertStringContainsString('nrs.gov.ng', $preset['jurisdiction']['portal_url']);
    }

    public function test_us_pack_separates_federal_state_and_local_obligations(): void
    {
        $preset = TaxCountryCatalog::presetsFor('usa');
        $codes = collect($preset['tax_codes'])->keyBy('code');

        $this->assertSame(21, $codes['USA-FED-CIT-PROVISION']['rate']);
        $this->assertTrue($codes['USA-FED-CIT-PROVISION']['metadata']['requires_configuration']);
        $this->assertSame('state', $codes['USA-STATE-SALES']['metadata']['jurisdiction_level']);
        $this->assertSame('local', $codes['USA-LOCAL-SALES']['metadata']['jurisdiction_level']);
        $this->assertSame(0, $codes['USA-STATE-SALES']['rate']);
        $this->assertSame(24, $preset['withholding_rules'][0]['rate']);
    }

    public function test_country_pack_codes_do_not_overlap(): void
    {
        $nigeriaCodes = collect(TaxCountryCatalog::presetsFor('NGA')['tax_codes'])->pluck('code');
        $usCodes = collect(TaxCountryCatalog::presetsFor('USA')['tax_codes'])->pluck('code');

        $this->assertSame([], $nigeriaCodes->intersect($usCodes)->values()->all());
    }
}
