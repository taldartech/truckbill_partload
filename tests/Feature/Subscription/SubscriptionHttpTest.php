<?php

namespace Tests\Feature\Subscription;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class SubscriptionHttpTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    /**
     * @return array<string, mixed>
     */
    private function superAdminContext(string $suffix = 'A', string $status = 'active', ?string $endsOn = null): array
    {
        $context = $this->createTenantContext([], $suffix, $status, $endsOn);
        $this->makeSuperAdmin($context['owner']);

        return $context;
    }

    private function createPayment(int $companyId, string $status, float $total, string $orderId): SubscriptionPayment
    {
        return SubscriptionPayment::create([
            'company_id' => $companyId,
            'type' => 'renewal',
            'status' => $status,
            'razorpay_order_id' => $orderId,
            'quantities' => [],
            'subtotal' => $total,
            'promo_discount' => 0,
            'gst_amount' => 0,
            'total_amount' => $total,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('subscription.index'))->assertRedirect();
    }

    public function test_non_super_admin_is_denied_every_subscription_route(): void
    {
        $context = $this->createTenantContext();
        $this->mockAdminApi($this->catalogPayload());
        $companyId = $context['company']->id;

        $this->actingAs($context['user']);

        $this->get(route('subscription.index'))->assertForbidden();
        $this->get(route('subscription.show', ['company_id' => $companyId]))->assertForbidden();
        $this->get(route('subscription.history'))->assertForbidden();
        $this->get(route('subscription.checkout.form', ['company_id' => $companyId]))->assertForbidden();
        $this->postJson(route('subscription.quote'), ['company_id' => $companyId, 'purchase_type' => 'renewal'])
            ->assertForbidden();
        $this->postJson(route('subscription.promo.validate'), ['code' => 'X'])->assertForbidden();
        $this->postJson(route('subscription.checkout'), ['company_id' => $companyId, 'purchase_type' => 'renewal'])
            ->assertForbidden();
        $this->postJson(route('subscription.callback'), [])->assertForbidden();
    }

    public function test_index_lists_only_owned_companies_with_summary(): void
    {
        $context = $this->superAdminContext();
        $expired = $this->createTenantContext([], 'Expired', 'expired', now()->subDays(3)->toDateString());
        $expired['company']->update(['user_id' => $context['owner']->id]);
        $this->createTenantContext([], 'Foreign');

        $this->actingAs($context['owner'])
            ->get(route('subscription.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Subscription/Index')
                ->has('companies', 2)
                ->where('summary.active', 1)
                ->where('summary.expired', 1)
                ->where('summary.total', 2)
            );
    }

    public function test_show_renders_plan_usage_payments_and_upcoming(): void
    {
        $context = $this->superAdminContext();
        $context['subscription']->update(['addon_branches' => 1, 'addon_users' => 2]);
        $this->useUpcomingSubscriptionsWithoutDateColumns();
        $this->mockAdminApi($this->catalogPayload());

        $this->createPayment($context['company']->id, 'completed', 500, 'order_done');
        $pending = $this->createPayment($context['company']->id, 'pending', 900, 'order_pending');
        UpcomingSubscription::create([
            'company_id' => $context['company']->id,
            'payment_id' => $pending->id,
            'status' => 'pending',
            'included_branches' => 3,
            'users_per_branch' => 5,
            'addon_branches' => 1,
            'addon_users' => 4,
        ]);

        $this->actingAs($context['owner'])
            ->get(route('subscription.show', ['company_id' => $context['company']->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Subscription/Show')
                ->where('company.id', $context['company']->id)
                ->where('isExpired', false)
                ->where('catalogAvailable', true)
                ->where('plan.addon_branches', 1)
                ->where('plan.addon_users', 2)
                ->where('usageMetrics.0.label', 'Branches')
                ->where('usageMetrics.0.used', 1)
                ->where('usageMetrics.0.max', 6)
                ->where('usageMetrics.0.percent', 17)
                ->where('usageMetrics.1.max', 12)
                ->where('usageMetrics.2.max', 62)
                ->has('payments', 1)
                ->where('upcoming.branches', 4)
                ->where('upcoming.addon_users', 4)
            );
    }

    public function test_show_degrades_gracefully_when_catalog_is_unavailable(): void
    {
        $context = $this->superAdminContext('A', 'expired', now()->subDays(3)->toDateString());
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);

        $this->actingAs($context['owner'])
            ->get(route('subscription.show', ['company_id' => $context['company']->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Subscription/Show')
                ->where('catalogAvailable', false)
                ->where('isExpired', true)
                ->where('upcoming', null)
            );
    }

    public function test_show_handles_company_without_subscription(): void
    {
        $context = $this->superAdminContext();
        $context['subscription']->delete();
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.show', ['company_id' => $context['company']->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('subscription', null)
                ->where('isExpired', true)
                ->where('usageMetrics.0.max', 0)
                ->where('usageMetrics.0.percent', 0)
                ->where('plan.users_per_branch', 5)
            );
    }

    public function test_show_requires_an_owned_existing_company(): void
    {
        $context = $this->superAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.show', ['company_id' => $foreign['company']->id]))
            ->assertForbidden();

        $this->actingAs($context['owner'])
            ->get(route('subscription.show', ['company_id' => 999999]))
            ->assertNotFound();
    }

    public function test_history_lists_payments_for_owned_companies_only(): void
    {
        $context = $this->superAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');
        $this->createPayment($context['company']->id, 'completed', 100, 'order_1');
        $this->createPayment($context['company']->id, 'completed', 250.5, 'order_2');
        $this->createPayment($foreign['company']->id, 'completed', 999, 'order_foreign');

        $this->actingAs($context['owner'])
            ->get(route('subscription.history'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Subscription/History')
                ->has('payments.data', 2)
                ->where('totalPaid', 350.5)
                ->where('companyId', 0)
            );
    }

    public function test_history_can_filter_by_company(): void
    {
        $context = $this->superAdminContext();
        $second = $this->createTenantContext([], 'Second');
        $second['company']->update(['user_id' => $context['owner']->id]);
        $this->createPayment($context['company']->id, 'completed', 100, 'order_1');
        $this->createPayment($second['company']->id, 'completed', 300, 'order_2');

        $this->actingAs($context['owner'])
            ->get(route('subscription.history', ['company_id' => $second['company']->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('payments.data', 1)
                ->where('companyId', $second['company']->id)
                ->where('totalPaid', 300)
            );
    }

    public function test_quote_returns_renewal_pricing(): void
    {
        $context = $this->superAdminContext();
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.quote'), [
                'company_id' => $context['company']->id,
                'purchase_type' => 'renewal',
                'addon_branches' => 1,
                'addon_users' => 2,
                'addon_trucks' => 50,
                'addon_lr_credits' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('subtotal', 12000)
            ->assertJsonPath('total_amount', 14160)
            ->assertJsonPath('can_checkout', true)
            ->assertJsonPath('quantities.addon_trucks', 0)
            ->assertJsonPath('quantities.addon_lr_credits', 0);
    }

    public function test_quote_validates_input_and_ownership(): void
    {
        $context = $this->superAdminContext();
        $foreign = $this->createTenantContext([], 'Foreign');
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.quote'), [
                'company_id' => $context['company']->id,
                'purchase_type' => 'bogus',
                'addon_branches' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['purchase_type', 'addon_branches']);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.quote'), [
                'company_id' => $foreign['company']->id,
                'purchase_type' => 'renewal',
            ])
            ->assertForbidden();
    }

    public function test_quote_returns_503_when_catalog_is_unavailable(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.quote'), [
                'company_id' => $context['company']->id,
                'purchase_type' => 'renewal',
            ])
            ->assertStatus(503)
            ->assertJsonPath('can_checkout', false)
            ->assertJsonPath('blocking_errors.0', 'Unable to load subscription pricing. Please try again later.');
    }

    public function test_quote_applies_validated_promo(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')->once()->with('TEN')->andReturn(['valid' => true, 'promo' => [
            'code' => 'TEN',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]]);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.quote'), [
                'company_id' => $context['company']->id,
                'purchase_type' => 'renewal',
                'promo_code' => 'TEN',
                'promo_validated' => true,
            ])
            ->assertOk()
            ->assertJsonPath('promo_discount', 1000)
            ->assertJsonPath('total_amount', 10620);
    }

    public function test_validate_promo_returns_valid_result(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi();
        $client->shouldReceive('validatePromo')->once()->with('GOOD')->andReturn(['valid' => true, 'promo' => ['code' => 'GOOD']]);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.promo.validate'), ['code' => 'GOOD'])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_validate_promo_returns_422_for_invalid_code(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi();
        $client->shouldReceive('validatePromo')->once()->andReturn(['valid' => false, 'message' => 'Expired']);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.promo.validate'), ['code' => 'OLD'])
            ->assertUnprocessable()
            ->assertJsonPath('valid', false);
    }

    public function test_validate_promo_requires_code_and_handles_outage(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi();
        $client->shouldReceive('validatePromo')->once()->andThrow(new SubscriptionCatalogUnavailableException);

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.promo.validate'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->actingAs($context['owner'])
            ->postJson(route('subscription.promo.validate'), ['code' => 'ANY'])
            ->assertStatus(503)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('message', 'Unable to load subscription pricing. Please try again later.');
    }

    public function test_checkout_form_renders_renewal_with_usage_and_minimum_addons(): void
    {
        $context = $this->superAdminContext();
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.checkout.form', ['company_id' => $context['company']->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Subscription/Checkout')
                ->where('purchaseType', 'renewal')
                ->where('usage.branches', 1)
                ->where('minimumAddons.addon_branches', 0)
                ->where('remainingDays', null)
                ->where('isExpired', false)
            );
    }

    public function test_checkout_form_falls_back_to_renewal_for_unknown_purchase_type(): void
    {
        $context = $this->superAdminContext();
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.checkout.form', [
                'company_id' => $context['company']->id,
                'purchase_type' => 'weird',
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('purchaseType', 'renewal'));
    }

    public function test_checkout_form_addon_mode_exposes_remaining_days_without_usage(): void
    {
        $context = $this->superAdminContext();
        $context['subscription']->update(['ends_on' => now()->addDays(9)->toDateString()]);
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.checkout.form', [
                'company_id' => $context['company']->id,
                'purchase_type' => 'addon',
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('purchaseType', 'addon')
                ->where('remainingDays', 10)
                ->where('usage', null)
            );
    }

    public function test_checkout_form_rejects_addons_for_expired_subscription(): void
    {
        $context = $this->superAdminContext('A', 'expired', now()->subDays(2)->toDateString());
        $this->mockAdminApi($this->catalogPayload());

        $this->actingAs($context['owner'])
            ->get(route('subscription.checkout.form', [
                'company_id' => $context['company']->id,
                'purchase_type' => 'addon',
            ]))
            ->assertRedirect(route('subscription.show', ['company_id' => $context['company']->id]))
            ->assertSessionHas('error', 'Add-ons are only available for active subscriptions.');
    }

    public function test_checkout_form_redirects_when_catalog_is_unavailable(): void
    {
        $context = $this->superAdminContext();
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);

        $this->actingAs($context['owner'])
            ->get(route('subscription.checkout.form', ['company_id' => $context['company']->id]))
            ->assertRedirect(route('subscription.show', ['company_id' => $context['company']->id]))
            ->assertSessionHas('error', 'Unable to load pricing. Please try again later.');
    }
}
