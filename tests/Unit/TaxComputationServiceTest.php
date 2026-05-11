<?php

namespace Tests\Unit;

use App\Support\TaxComputationService;
use PHPUnit\Framework\TestCase;

class TaxComputationServiceTest extends TestCase
{
    public function test_exclusive_vat_breakdown_is_computed_correctly(): void
    {
        $service = new TaxComputationService();

        $result = $service->computeBreakdown(100000, [[
            'code' => 'NGA-VAT-STD',
            'name' => 'Nigeria VAT',
            'type' => 'vat',
            'rate' => 7.5,
            'calculation_method' => 'exclusive',
            'recoverability_rate' => 100,
            'is_active' => true,
        ]]);

        $this->assertSame(100000.0, $result['taxable_amount']);
        $this->assertSame(7500.0, $result['total_tax']);
        $this->assertSame(107500.0, $result['gross_amount']);
        $this->assertSame(7500.0, $result['recoverable_tax']);
    }

    public function test_inclusive_vat_breakdown_extracts_tax_from_gross_amount(): void
    {
        $service = new TaxComputationService();

        $line = $service->calculateTaxLine(107500, [
            'code' => 'NGA-VAT-INC',
            'name' => 'Nigeria VAT Inclusive',
            'type' => 'vat',
            'rate' => 7.5,
            'calculation_method' => 'inclusive',
            'recoverability_rate' => 100,
        ]);

        $this->assertSame(100000.0, $line['base_amount']);
        $this->assertSame(7500.0, $line['tax_amount']);
    }

    public function test_withholding_calculation_respects_rate_and_threshold(): void
    {
        $service = new TaxComputationService();

        $result = $service->calculateWithholding(500000, [
            'name' => 'Contracts WHT',
            'service_type' => 'contracts',
            'counterparty_type' => 'vendor',
            'rate' => 5,
            'threshold_amount' => 10000,
            'certificate_prefix' => 'WHT-CONTRACT',
        ]);

        $this->assertSame(25000.0, $result['withholding_amount']);
        $this->assertSame(475000.0, $result['net_settlement_amount']);
        $this->assertStringStartsWith('WHT-CONTRACT-', $result['certificate_reference']);
    }

    public function test_nigeria_paye_calculation_returns_monthly_and_annual_tax(): void
    {
        $service = new TaxComputationService();

        $result = $service->calculateNigeriaPaye(500000, [
            'pension_employee_annual' => 480000,
            'nhf_annual' => 150000,
        ]);

        $this->assertGreaterThan(0, $result['taxable_income_annual']);
        $this->assertGreaterThan(0, $result['annual_tax']);
        $this->assertEquals(round($result['annual_tax'] / 12, 2), $result['monthly_tax']);
        $this->assertNotEmpty($result['bands']);
    }
}
