<?php

namespace Tests\Feature\Deployment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnvironmentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_key_is_configured_in_testing(): void
    {
        $this->assertNotEmpty(config('app.key'));
        $this->assertSame('testing', config('app.env'));
    }

    public function test_critical_tables_exist_after_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('companies'));
        $this->assertTrue(Schema::hasTable('branches'));
        $this->assertTrue(Schema::hasTable('bilties'));
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('company_subscriptions'));
        $this->assertTrue(Schema::hasTable('accounting_vouchers'));
    }

    public function test_schedule_list_command_succeeds(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
    }

    public function test_route_list_includes_login_and_onboarding_api(): void
    {
        Artisan::call('route:list', ['--json' => true]);
        /** @var list<array{uri?: string}> $routes */
        $routes = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $uris = collect($routes)->pluck('uri')->filter()->all();

        $this->assertContains('login', $uris);
        $this->assertTrue(collect($uris)->contains(fn (string $uri) => str_starts_with($uri, 'api/onboarding')));
        $this->assertTrue(collect($uris)->contains(fn (string $uri) => str_starts_with($uri, 'api/admin/subscriptions')));
    }
}
