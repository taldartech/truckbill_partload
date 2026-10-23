<?php

namespace Tests\Feature\Reliability;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\AccountingSourceLink;
use App\Services\AdminApiClient;
use App\Services\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class DuplicateRequestAndCatalogFailureTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_subscription_settings_fall_back_when_catalog_unavailable(): void
    {
        Cache::flush();

        $client = Mockery::mock(AdminApiClient::class);
        $client->shouldReceive('getCatalog')
            ->once()
            ->andThrow(new SubscriptionCatalogUnavailableException('down'));

        $this->app->instance(AdminApiClient::class, $client);

        $settings = app(SubscriptionAccessService::class)->cachedSettings();

        $this->assertSame(0, $settings['grace_period_days']);
        $this->assertSame(18.0, $settings['gst_rate_percent']);
    }

    public function test_accounting_reconcile_dry_run_does_not_mutate_links(): void
    {
        $context = $this->createTenantContext(['view accounting_settings']);

        $before = AccountingSourceLink::query()->count();

        $this->artisan('accounting:reconcile', [
            '--company' => $context['company']->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($before, AccountingSourceLink::query()->count());
    }
}
