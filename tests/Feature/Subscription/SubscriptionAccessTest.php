<?php

namespace Tests\Feature\Subscription;

use App\Enums\SubscriptionAccessMode;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_active_subscription_resolves_to_full_access(): void
    {
        $context = $this->createTenantContext(['view parties']);
        Cache::flush();

        $mode = app(SubscriptionAccessService::class)->resolveForCompany($context['company']->fresh('subscription'));

        $this->assertSame(SubscriptionAccessMode::Full, $mode);
    }

    public function test_expired_subscription_without_grace_is_locked(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Locked Co', 'user_id' => $owner->id]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'expired',
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => now()->subDays(10)->toDateString(),
            'included_branches' => 1,
            'users_per_branch' => 1,
            'included_lr_credits' => 0,
            'included_trucks' => 0,
            'lr_credits_remaining' => 0,
        ]);
        Cache::flush();
        Cache::put('subscription.settings', [
            'grace_period_days' => 0,
            'gst_rate_percent' => 18.0,
        ], now()->addHour());

        $mode = app(SubscriptionAccessService::class)->resolveForCompany($company->fresh('subscription'));

        $this->assertSame(SubscriptionAccessMode::Locked, $mode);
    }

    public function test_grace_period_allows_safe_get_but_blocks_mutations(): void
    {
        $context = $this->createTenantContext(
            ['view parties', 'create parties'],
            'Grace',
            'expired',
            now()->subDay()->toDateString()
        );
        Cache::flush();
        Cache::put('subscription.settings', ['grace_period_days' => 7, 'gst_rate_percent' => 18.0], now()->addHour());

        $mode = app(SubscriptionAccessService::class)->resolveForCompany($context['company']->fresh('subscription'));
        $this->assertSame(SubscriptionAccessMode::GraceReadOnly, $mode);

        $this->actingAs($context['user'])
            ->get(route('parties.index'))
            ->assertOk();

        $this->actingAs($context['user'])
            ->post(route('parties.store'), ['name' => 'Blocked Party'])
            ->assertRedirect();
    }

    public function test_company_without_subscription_is_locked(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'No Sub Co', 'user_id' => $owner->id]);
        Cache::flush();

        $mode = app(SubscriptionAccessService::class)->resolveForCompany($company);

        $this->assertSame(SubscriptionAccessMode::Locked, $mode);
    }
}
