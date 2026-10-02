<?php

namespace Tests\Unit;

use App\Models\TaxAuthorityConnection;
use App\Models\TaxFiling;
use App\Models\TaxFilingLine;
use App\Support\TaxAuthorityFilingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TaxAuthorityFilingPayloadTest extends TestCase
{
    public function test_it_builds_a_stable_authority_payload_from_an_approved_workpaper(): void
    {
        $filing = new TaxFiling([
            'filing_type' => 'vat',
            'filing_frequency' => 'monthly',
            'currency_code' => 'NGN',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'total_taxable' => 100000,
            'total_tax' => 7500,
            'tax_due' => 5000,
            'tax_credit' => 2500,
            'tax_refund' => 0,
            'adjustments_total' => 0,
        ]);
        $filing->id = 42;
        $filing->updated_at = Carbon::parse('2026-10-01 12:00:00');
        $filing->setRelation('lines', new Collection([
            new TaxFilingLine([
                'line_key' => 'output_vat',
                'label' => 'Output VAT',
                'tax_type' => 'vat',
                'taxable_base' => 100000,
                'tax_amount' => 7500,
                'credit_amount' => 0,
                'net_amount' => 7500,
            ]),
        ]));
        $connection = new TaxAuthorityConnection([
            'taxpayer_id' => '01234567-0001',
            'organization_id' => 'ORG-100',
        ]);

        $payload = (new TaxAuthorityFilingService)->payloadFor($filing, $connection);

        $this->assertSame('smartprobook-tax-v1', $payload['schema_version']);
        $this->assertSame('VAT', $payload['kind']);
        $this->assertSame('01234567-0001', $payload['business']['tin']);
        $this->assertSame('2026-09-01', $payload['period']['start']);
        $this->assertSame(5000.0, $payload['totals']['due']);
        $this->assertSame('output_vat', $payload['lines'][0]['key']);
        $this->assertSame('42', $payload['source']['filing_id']);
    }
}
