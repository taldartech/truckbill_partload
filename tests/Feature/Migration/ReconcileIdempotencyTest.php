<?php

namespace Tests\Feature\Migration;

use App\Services\Accounting\AccountingReconciler;
use App\Services\Accounting\EnsureCompanyAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ReconcileIdempotencyTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_reconcile_dry_run_can_be_rerun_safely(): void
    {
        $context = $this->createTenantContext();
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $reconciler = app(AccountingReconciler::class);

        $first = $reconciler->run($context['company']->id, dryRun: true);
        $second = $reconciler->run($context['company']->id, dryRun: true);

        $this->assertIsArray($first);
        $this->assertIsArray($second);
        $this->assertSame($first['errors'] ?? 0, $second['errors'] ?? 0);
    }
}
