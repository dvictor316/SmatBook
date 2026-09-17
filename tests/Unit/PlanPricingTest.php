<?php

namespace Tests\Unit;

use App\Models\Plan;
use PHPUnit\Framework\TestCase;

class PlanPricingTest extends TestCase
{
    public function test_annual_plan_catalog_matches_the_approved_pricing(): void
    {
        $catalog = Plan::marketingCardCatalog();

        $this->assertSame([20000, 20000, 1, 1], $this->planSummary($catalog['starter']));
        $this->assertSame([80000, 80000, 2, 2], $this->planSummary($catalog['basic']));
        $this->assertSame([100000, 150000, 3, 5], $this->planSummary($catalog['pro']));
        $this->assertSame([200000, 300000, 4, 10], $this->planSummary($catalog['enterprise']));
    }

    public function test_annual_add_on_prices_and_branch_eligibility_are_enforced(): void
    {
        foreach (['Starter', 'Basic Core', 'Pro Engine', 'Institutional'] as $planName) {
            $this->assertSame(30000.0, Plan::additionalUserPriceForName($planName));
        }

        $this->assertNull(Plan::additionalBranchPriceForName('Starter POS'));
        $this->assertNull(Plan::additionalBranchPriceForName('Basic Core'));
        $this->assertSame(50000.0, Plan::additionalBranchPriceForName('Pro Engine'));
        $this->assertSame(50000.0, Plan::additionalBranchPriceForName('Institutional'));
    }

    private function planSummary(array $plan): array
    {
        return [
            $plan['from_price'],
            $plan['team_price'],
            $plan['solo_users'],
            $plan['team_users'],
        ];
    }
}
