<?php

namespace Tests\Feature\Subscription;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyUpcomingSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_upcoming_command_runs_successfully(): void
    {
        $this->artisan('subscription:apply-upcoming')
            ->assertSuccessful();
    }
}
