<?php

namespace Tests\Unit\Services;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\CompanySubscription;
use App\Services\AdminApiClient;
use App\Services\SubscriptionPricingService;
use App\Support\SubscriptionProration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class SubscriptionPricingServiceTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    private function pricing(): SubscriptionPricingService
    {
        return app(SubscriptionPricingService::class);
    }

    public function test_renewal_quote_charges_base_plan_plus_gst(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal']);

        $this->assertSame('renewal', $quote['purchase_type']);
        $this->assertSame(10000.0, $quote['subtotal']);
        $this->assertSame(0.0, $quote['promo_discount']);
        $this->assertSame(1800.0, $quote['gst_amount']);
        $this->assertSame(11800.0, $quote['total_amount']);
        $this->assertSame(18.0, $quote['gst_rate_percent']);
        $this->assertTrue($quote['can_checkout']);
        $this->assertSame([], $quote['blocking_errors']);
        $this->assertNull($quote['proration']);
        $this->assertSame([['label' => 'Annual base plan', 'amount' => 10000.0]], $quote['line_items']);
        $this->assertSame(1, $quote['current_usage']['branches']);
        $this->assertSame(['addon_branches' => 0, 'addon_users' => 0], $quote['minimum_addons_required']);
    }

    public function test_renewal_quote_adds_branch_and_user_addons_at_full_annual_price(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'addon_branches' => 2,
            'addon_users' => 4,
            'addon_trucks' => 1,
            'addon_lr_credits' => 100,
        ]);

        $this->assertSame(14000.0, $quote['subtotal']);
        $this->assertSame(2520.0, $quote['gst_amount']);
        $this->assertSame(16520.0, $quote['total_amount']);
        $this->assertSame('Branch add-ons (2)', $quote['line_items'][1]['label']);
        $this->assertSame(2000.0, $quote['line_items'][1]['amount']);
        $this->assertSame('User add-ons (4)', $quote['line_items'][2]['label']);
        $this->assertSame(2000.0, $quote['line_items'][2]['amount']);
        $this->assertSame([
            'addon_branches' => 2,
            'addon_users' => 4,
            'addon_trucks' => 1,
            'addon_lr_credits' => 100,
        ], $quote['quantities']);
    }

    public function test_renewal_quote_blocks_checkout_when_branches_exceed_capacity(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');

        $quote = $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal']);

        $this->assertFalse($quote['can_checkout']);
        $this->assertSame(
            ['Add at least 1 branch add-on(s), or delete extra branches before renewing.'],
            $quote['blocking_errors']
        );
        $this->assertSame(1, $quote['minimum_addons_required']['addon_branches']);

        $withAddon = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
        ]);

        $this->assertTrue($withAddon['can_checkout']);
    }

    public function test_renewal_quote_blocks_checkout_when_total_users_exceed_capacity(): void
    {
        $this->mockAdminApi($this->catalogPayload(['included_branches' => 1, 'users_per_branch' => 1]));
        $context = $this->createTenantContext();
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $second, 1);

        // 2 users, 2 branches but only 1 branch slot * 1 user.
        $quote = $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal']);

        $this->assertFalse($quote['can_checkout']);
        $this->assertContains('Add more user add-ons, or remove users before renewing.', $quote['blocking_errors']);
    }

    public function test_renewal_quote_blocks_checkout_when_branch_users_overflow_without_addons(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();
        $this->createUsersInBranch($context['company'], $context['branch'], 6);

        $quote = $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal']);

        $this->assertFalse($quote['can_checkout']);
        $this->assertContains(
            'Add at least 2 user add-on(s) for branches over the per-branch limit, or reassign or remove users.',
            $quote['blocking_errors']
        );

        $ok = $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal', 'addon_users' => 2]);
        $this->assertTrue($ok['can_checkout']);
    }

    public function test_addon_quote_is_prorated_for_remaining_subscription_days(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext([], 'A');
        $context['subscription']->update(['ends_on' => now()->addDays(99)->toDateString()]);
        $company = $context['company']->fresh('subscription');

        $quote = $this->pricing()->quote($company, [
            'purchase_type' => 'addon',
            'addon_branches' => 2,
            'addon_users' => 3,
        ]);

        $expectedBranch = SubscriptionProration::prorateAnnualAmount(2000.0, 100);
        $expectedUser = SubscriptionProration::prorateAnnualAmount(1500.0, 100);

        $this->assertSame(['remaining_days' => 100, 'annual_days' => 365], $quote['proration']);
        $this->assertSame('Branch add-ons (2, 100 days prorated)', $quote['line_items'][0]['label']);
        $this->assertSame($expectedBranch, $quote['line_items'][0]['amount']);
        $this->assertSame('User add-ons (3, 100 days prorated)', $quote['line_items'][1]['label']);
        $this->assertSame($expectedUser, $quote['line_items'][1]['amount']);
        $this->assertEqualsWithDelta($expectedBranch + $expectedUser, $quote['subtotal'], 0.001);
        $this->assertTrue($quote['can_checkout']);
        $this->assertLessThan(3500.0, $quote['subtotal']);
    }

    public function test_addon_quote_is_blocked_for_expired_subscription(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(5)->toDateString());

        $quote = $this->pricing()->quote($context['company']->fresh('subscription'), [
            'purchase_type' => 'addon',
            'addon_branches' => 1,
        ]);

        $this->assertFalse($quote['can_checkout']);
        $this->assertSame(['Add-ons are only available for active subscriptions.'], $quote['blocking_errors']);
        $this->assertSame(0, $quote['proration']['remaining_days']);
        $this->assertSame(0.0, $quote['subtotal']);
    }

    public function test_addon_quote_is_blocked_without_subscription(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        $quote = $this->pricing()->quote($context['company']->fresh('subscription'), [
            'purchase_type' => 'addon',
            'addon_users' => 1,
        ]);

        $this->assertFalse($quote['can_checkout']);
        $this->assertNull($quote['proration']);
        $this->assertSame(500.0, $quote['subtotal']);
        $this->assertSame(['Add-ons are only available for active subscriptions.'], $quote['blocking_errors']);
    }

    public function test_addon_quote_without_quantities_cannot_checkout_because_total_is_zero(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], ['purchase_type' => 'addon']);

        $this->assertSame([], $quote['blocking_errors']);
        $this->assertSame(0.0, $quote['total_amount']);
        $this->assertFalse($quote['can_checkout']);
    }

    public function test_percentage_promo_discounts_subtotal_before_gst(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')
            ->once()
            ->with('SAVE10')
            ->andReturn(['valid' => true, 'promo' => [
                'code' => 'SAVE10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ]]);
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'promo_code' => 'SAVE10',
            'promo_validated' => true,
        ]);

        $this->assertSame(1000.0, $quote['promo_discount']);
        $this->assertSame(1620.0, $quote['gst_amount']);
        $this->assertSame(10620.0, $quote['total_amount']);
        $this->assertSame('SAVE10', $quote['promo_snapshot']['code']);
        $this->assertSame(['label' => 'Promo discount', 'amount' => -1000.0], end($quote['line_items']));
    }

    public function test_fixed_promo_is_capped_at_subtotal(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')
            ->once()
            ->andReturn(['valid' => true, 'promo' => [
                'code' => 'BIG',
                'discount_type' => 'fixed',
                'discount_value' => 99999,
            ]]);
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'promo_code' => 'BIG',
            'promo_validated' => true,
        ]);

        $this->assertSame(10000.0, $quote['promo_discount']);
        $this->assertSame(0.0, $quote['total_amount']);
        $this->assertFalse($quote['can_checkout']);
    }

    public function test_fixed_promo_below_subtotal_applies_exact_value(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')
            ->once()
            ->andReturn(['valid' => true, 'promo' => [
                'code' => 'FLAT500',
                'discount_type' => 'fixed',
                'discount_value' => 500,
            ]]);
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'promo_code' => 'FLAT500',
            'promo_validated' => true,
        ]);

        $this->assertSame(500.0, $quote['promo_discount']);
        $this->assertSame(11210.0, $quote['total_amount']);
    }

    public function test_invalid_promo_is_ignored(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')->once()->andReturn(['valid' => false]);
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'promo_code' => 'NOPE',
            'promo_validated' => true,
        ]);

        $this->assertSame(0.0, $quote['promo_discount']);
        $this->assertNull($quote['promo_snapshot']);
        $this->assertSame(11800.0, $quote['total_amount']);
    }

    public function test_promo_is_not_looked_up_unless_marked_validated(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldNotReceive('validatePromo');
        $context = $this->createTenantContext();

        $quote = $this->pricing()->quote($context['company'], [
            'purchase_type' => 'renewal',
            'promo_code' => 'SAVE10',
        ]);

        $this->assertNull($quote['promo_snapshot']);
        $this->assertSame(11800.0, $quote['total_amount']);
    }

    public function test_quote_propagates_catalog_unavailable_exception(): void
    {
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);
        $context = $this->createTenantContext();

        $this->expectException(SubscriptionCatalogUnavailableException::class);

        $this->pricing()->quote($context['company'], ['purchase_type' => 'renewal']);
    }

    public function test_minimum_renewal_addons_delegates_to_usage_service(): void
    {
        $this->mockAdminApi();
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');

        $minimum = $this->pricing()->minimumRenewalAddons(
            $context['company'],
            $this->catalogPayload(['included_branches' => 1])['catalog'],
        );

        $this->assertSame(['addon_branches' => 1, 'addon_users' => 0], $minimum);
    }

    public function test_create_trial_subscription_uses_catalog_values_for_fifteen_days(): void
    {
        $this->mockAdminApi($this->catalogPayload(['included_branches' => 3, 'users_per_branch' => 7]));
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        $subscription = $this->pricing()->createTrialSubscription($context['company']);

        $this->assertSame('trial', $subscription->status);
        $this->assertSame(3, $subscription->included_branches);
        $this->assertSame(7, $subscription->users_per_branch);
        $this->assertSame(now()->toDateString(), $subscription->starts_on->toDateString());
        $this->assertSame(now()->addDays(15)->toDateString(), $subscription->ends_on->toDateString());
        $this->assertSame($subscription->ends_on->toDateString(), $subscription->trial_ends_on->toDateString());
        $this->assertSame(0, $subscription->included_lr_credits);
        $this->assertTrue($subscription->isActive());
    }

    public function test_create_trial_subscription_falls_back_to_defaults_when_catalog_is_down(): void
    {
        $client = Mockery::mock(AdminApiClient::class);
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);
        $this->app->instance(AdminApiClient::class, $client);
        $context = $this->createTenantContext();

        $subscription = $this->pricing()->createTrialSubscription($context['company']);

        $this->assertSame($context['subscription']->id, $subscription->id);
        $this->assertSame('trial', $subscription->status);
        $this->assertSame(2, $subscription->included_branches);
        $this->assertSame(5, $subscription->users_per_branch);
        $this->assertSame(1, CompanySubscription::query()->where('company_id', $context['company']->id)->count());
    }
}
