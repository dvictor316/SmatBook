<?php

namespace Tests\Unit;

use App\Models\Subscription;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SubscriptionTermTest extends TestCase
{
    public function test_free_trial_lasts_one_month(): void
    {
        $start = Carbon::parse('2026-01-15 10:00:00');
        $payload = Subscription::trialPayload($start);

        $this->assertSame('Trial', $payload['status']);
        $this->assertSame('free', $payload['payment_status']);
        $this->assertTrue($payload['end_date']->equalTo('2026-02-15 10:00:00'));
    }

    public function test_annual_paid_term_starts_after_trial_and_lasts_twelve_months(): void
    {
        $start = Carbon::parse('2026-01-15 10:00:00');

        $end = Subscription::initialPaidTermEndDate($start, 'Yearly');

        $this->assertTrue($end->equalTo('2027-01-15 10:00:00'));
        $this->assertTrue($start->equalTo('2026-01-15 10:00:00'));
    }

    public function test_trial_followed_by_annual_payment_provides_thirteen_months_total_access(): void
    {
        $registrationDate = Carbon::parse('2026-01-15 10:00:00');
        $trial = Subscription::trialPayload($registrationDate);

        $paidEnd = Subscription::initialPaidTermEndDate($trial['end_date'], 'yearly');

        $this->assertTrue($paidEnd->equalTo('2027-02-15 10:00:00'));
    }

    public function test_each_plan_resolves_to_a_supported_renewal_key(): void
    {
        $plans = [
            'Starter Yearly' => 'starter',
            'Basic Yearly' => 'basic',
            'Professional Solo Yearly' => 'pro-solo',
            'Professional Yearly' => 'pro',
            'Enterprise Solo Yearly' => 'enterprise-solo',
            'Enterprise Yearly' => 'enterprise',
            'Hotel Yearly' => 'hotel',
        ];

        foreach ($plans as $planName => $expectedKey) {
            $subscription = new Subscription(['plan_name' => $planName]);
            $this->assertSame($expectedKey, $subscription->renewalPlanKey());
        }
    }

    public function test_legacy_monthly_term_is_not_extended(): void
    {
        $start = Carbon::parse('2026-01-15 10:00:00');

        $end = Subscription::initialPaidTermEndDate($start, 'monthly');

        $this->assertTrue($end->equalTo('2026-02-15 10:00:00'));
    }
}
