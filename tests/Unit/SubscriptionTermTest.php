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

    public function test_initial_annual_paid_term_includes_the_free_trial_month(): void
    {
        $start = Carbon::parse('2026-01-15 10:00:00');

        $end = Subscription::initialPaidTermEndDate($start, 'Yearly');

        $this->assertTrue($end->equalTo('2027-02-15 10:00:00'));
        $this->assertTrue($start->equalTo('2026-01-15 10:00:00'));
    }

    public function test_legacy_monthly_term_is_not_extended(): void
    {
        $start = Carbon::parse('2026-01-15 10:00:00');

        $end = Subscription::initialPaidTermEndDate($start, 'monthly');

        $this->assertTrue($end->equalTo('2026-02-15 10:00:00'));
    }
}
