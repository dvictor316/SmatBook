<?php

namespace Tests\Feature;

use App\Models\TaxCode;
use App\Models\TaxJurisdiction;
use App\Models\WithholdingRule;
use App\Support\TaxEngineBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxEngineBootstrapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reinstalling_a_tax_pack_restores_inactive_configuration(): void
    {
        $service = app(TaxEngineBootstrapService::class);
        $service->bootstrapDefaults('NGA', 901, 902);

        $jurisdiction = TaxJurisdiction::query()->where('company_id', 901)->firstOrFail();
        $taxCode = TaxCode::query()->where('tax_jurisdiction_id', $jurisdiction->id)->firstOrFail();
        $withholdingRule = WithholdingRule::query()->where('tax_jurisdiction_id', $jurisdiction->id)->firstOrFail();

        $jurisdiction->update(['is_active' => false]);
        $taxCode->update(['is_active' => false]);
        $withholdingRule->update(['is_active' => false]);

        $service->bootstrapDefaults('NGA', 901, 902);

        $this->assertTrue($jurisdiction->fresh()->is_active);
        $this->assertTrue($taxCode->fresh()->is_active);
        $this->assertTrue($withholdingRule->fresh()->is_active);
    }
}
