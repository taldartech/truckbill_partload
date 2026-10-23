<?php

namespace Tests\Feature\Subscription;

use App\Enums\UserRole;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class EnsureActiveSubscriptionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_request_passes_through_when_active_company_no_longer_exists(): void
    {
        Role::findOrCreate(UserRole::MasterAdmin->value, 'web');
        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);
        $this->actingAs($master);
        session(['active_company_id' => 999999]);

        $response = app(EnsureActiveSubscription::class)->handle(
            Request::create('/dashboard'),
            fn (): Response => response('passed')
        );

        $this->assertSame('passed', $response->getContent());
    }

    public function test_locked_subscription_redirects_to_dashboard(): void
    {
        $context = $this->createTenantContext(
            ['view parties'],
            'Locked',
            'expired',
            now()->subMonth()->toDateString()
        );
        Cache::flush();
        Cache::put('subscription.settings', ['grace_period_days' => 0, 'gst_rate_percent' => 18.0], now()->addHour());

        $this->actingAs($context['user'])
            ->get(route('parties.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }
}
