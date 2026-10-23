<?php

namespace Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

#[Group('performance')]
class ReportResponseBudgetTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_trial_balance_responds_within_budget(): void
    {
        $context = $this->createTenantContext(['view accounting_reports']);

        $started = hrtime(true);

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.trial-balance', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(5000, $elapsedMs, 'Trial balance exceeded 5s budget in test environment.');
    }
}
