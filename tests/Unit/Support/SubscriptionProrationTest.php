<?php

namespace Tests\Unit\Support;

use App\Support\SubscriptionProration;
use Carbon\Carbon;
use Tests\TestCase;

class SubscriptionProrationTest extends TestCase
{
    public function test_remaining_days_includes_today_and_end_date(): void
    {
        Carbon::setTestNow('2026-01-01');

        $this->assertSame(170, SubscriptionProration::remainingDaysInclusive('2026-06-19'));
        $this->assertSame(1, SubscriptionProration::remainingDaysInclusive('2026-01-01'));

        Carbon::setTestNow();
    }

    public function test_prorates_annual_amount_by_remaining_days(): void
    {
        $annual = 36500.0;
        $expected = round(($annual / 365) * 170, 2);

        $this->assertSame($expected, SubscriptionProration::prorateAnnualAmount($annual, 170));
    }

    public function test_prorated_amount_never_exceeds_annual_price(): void
    {
        $annual = 1000.0;

        $this->assertSame($annual, SubscriptionProration::prorateAnnualAmount($annual, 365));
        $this->assertSame($annual, SubscriptionProration::prorateAnnualAmount($annual, 400));
    }
}
