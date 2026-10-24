<?php

namespace Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

/**
 * Budgets mirror the existing trial-balance 5s local/CI budget documented in
 * docs/qa/coverage-matrix.md / perf/README.md — not invented SLOs.
 */
#[Group('performance')]
class PortalListBudgetTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_parties_index_responds_within_budget(): void
    {
        $context = $this->createTenantContext(['view parties']);

        $started = hrtime(true);

        $this->actingAs($context['user'])
            ->get(route('parties.index'))
            ->assertOk();

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(5000, $elapsedMs, 'Parties index exceeded 5s budget in test environment.');
    }

    public function test_bilties_index_responds_within_budget(): void
    {
        $context = $this->createTenantContext(['view bilties']);

        $started = hrtime(true);

        $this->actingAs($context['user'])
            ->get(route('bilties.index'))
            ->assertOk();

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(5000, $elapsedMs, 'Bilties index exceeded 5s budget in test environment.');
    }
}
