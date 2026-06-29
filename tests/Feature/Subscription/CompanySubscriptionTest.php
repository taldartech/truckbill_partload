<?php

namespace Tests\Feature\Subscription;

use App\Models\CompanySubscription;
use Carbon\Carbon;
use Tests\TestCase;

class CompanySubscriptionTest extends TestCase
{
    public function test_active_trial_subscription_within_ends_on(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'trial',
            'starts_on' => Carbon::today(),
            'ends_on' => Carbon::today()->addDays(10),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'addon_users' => 0,
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertSame(6, $subscription->maxCompanyUsers());
    }

    public function test_expired_subscription_is_not_active(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'expired',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_display_status_shows_expired_when_end_date_passed(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'active',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertSame('expired', $subscription->displayStatus());
        $this->assertSame('expired', $subscription->display_status);
    }
}
