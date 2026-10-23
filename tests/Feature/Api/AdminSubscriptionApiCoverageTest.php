<?php

namespace Tests\Feature\Api;

use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class AdminSubscriptionApiCoverageTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    private const SECRET = 'test-onboarding-secret';

    private const BASE = '/api/admin/subscriptions';

    protected function setUp(): void
    {
        parent::setUp();

        config(['onboarding.signing_secret' => self::SECRET]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signed(string $method, string $uri, array $payload = [], ?string $timestamp = null, ?string $secret = null): TestResponse
    {
        $body = $payload === [] ? '' : json_encode($payload);
        $timestamp ??= (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? self::SECRET);

        return $this->call($method, $uri, [], [], [], [
            'HTTP_X-Timestamp' => $timestamp,
            'HTTP_X-Signature' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    // ---------------------------------------------------------------- signature middleware

    public function test_requests_are_rejected_with_missing_or_invalid_signatures(): void
    {
        $this->getJson(self::BASE)->assertUnauthorized()->assertJsonPath('message', 'Missing signature headers.');

        $this->signed('GET', self::BASE, [], 'abc')->assertUnauthorized()->assertJsonPath('message', 'Invalid timestamp.');

        $this->signed('GET', self::BASE, [], (string) (time() - 3600))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Request timestamp expired.');

        $this->signed('GET', self::BASE, [], null, 'wrong-secret')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid signature.');
    }

    public function test_requests_fail_with_503_when_signing_secret_is_not_configured(): void
    {
        config(['onboarding.signing_secret' => null]);

        $this->signed('GET', self::BASE)->assertStatus(503);
    }

    public function test_signature_covers_the_request_body(): void
    {
        $context = $this->createTenantContext();
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode(['status' => 'active']), self::SECRET);

        $this->call('PATCH', self::BASE.'/'.$context['company']->id, [], [], [], [
            'HTTP_X-Timestamp' => $timestamp,
            'HTTP_X-Signature' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['status' => 'cancelled']))->assertUnauthorized();
    }

    // ---------------------------------------------------------------- index / search / show

    public function test_index_lists_companies_with_status_end_date_and_usage(): void
    {
        $context = $this->createTenantContext();
        $this->createTenantContext([], 'Bravo', 'expired', now()->subDays(5)->toDateString());

        $response = $this->signed('GET', self::BASE)->assertOk();

        $response->assertJsonPath('total', 2);
        $first = collect($response->json('data'))->firstWhere('id', $context['company']->id);
        $this->assertSame('active', $first['status']);
        $this->assertSame($context['subscription']->ends_on->toDateString(), $first['ends_on']);
        $this->assertSame(1, $first['usage']['branches']);
        $this->assertSame(1, $first['usage']['total_users']);
    }

    public function test_index_handles_company_without_subscription(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        $this->signed('GET', self::BASE)
            ->assertOk()
            ->assertJsonPath('data.0.status', null)
            ->assertJsonPath('data.0.ends_on', null);
    }

    public function test_index_filters_by_search_term_and_paginates(): void
    {
        $alpha = $this->createTenantContext([], 'Alpha');
        $beta = $this->createTenantContext([], 'Beta');
        $beta['company']->update(['contact_person' => 'Zed Person']);
        $this->createTenantContext([], 'Gamma');

        $this->signed('GET', self::BASE.'?q=Alpha')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $alpha['company']->id);

        $this->signed('GET', self::BASE.'?q=Zed')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $beta['company']->id);

        $this->signed('GET', self::BASE.'?per_page=2')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_search_endpoint_behaves_like_index(): void
    {
        $this->createTenantContext([], 'Alpha');
        $this->createTenantContext([], 'Beta');

        $this->signed('GET', self::BASE.'/search?q=Beta')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Test Co Beta');
    }

    public function test_show_returns_subscription_usage_payments_and_pending_upcoming(): void
    {
        $this->useUpcomingSubscriptionsWithoutDateColumns();
        $context = $this->createTenantContext();
        $payment = SubscriptionPayment::create([
            'company_id' => $context['company']->id,
            'type' => 'renewal',
            'status' => 'completed',
            'razorpay_order_id' => 'order_1',
            'quantities' => [],
            'subtotal' => 1,
            'promo_discount' => 0,
            'gst_amount' => 0,
            'total_amount' => 1,
        ]);
        UpcomingSubscription::create([
            'company_id' => $context['company']->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
            'included_branches' => 3,
            'users_per_branch' => 4,
        ]);
        UpcomingSubscription::create([
            'company_id' => $context['company']->id,
            'payment_id' => $payment->id,
            'status' => 'applied',
            'included_branches' => 9,
            'users_per_branch' => 9,
        ]);

        $this->signed('GET', self::BASE.'/'.$context['company']->id)
            ->assertOk()
            ->assertJsonPath('company.id', $context['company']->id)
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('usage.branches', 1)
            ->assertJsonCount(1, 'payments')
            ->assertJsonCount(1, 'upcoming')
            ->assertJsonPath('upcoming.0.included_branches', 3);
    }

    public function test_show_returns_404_for_unknown_company(): void
    {
        $this->signed('GET', self::BASE.'/999999')->assertNotFound();
    }

    // ---------------------------------------------------------------- quote

    public function test_quote_returns_pricing_for_company(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/quote', [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'addon_users' => 2,
            'addon_trucks' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('subtotal', 12000)
            ->assertJsonPath('total_amount', 14160)
            ->assertJsonPath('quantities.addon_trucks', 3)
            ->assertJsonPath('can_checkout', true);
    }

    public function test_quote_marks_promo_as_validated_when_code_supplied(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')->once()->with('FLAT')->andReturn(['valid' => true, 'promo' => [
            'code' => 'FLAT',
            'discount_type' => 'fixed',
            'discount_value' => 2000,
        ]]);
        $context = $this->createTenantContext();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/quote', [
            'purchase_type' => 'renewal',
            'promo_code' => 'FLAT',
        ])
            ->assertOk()
            ->assertJsonPath('promo_discount', 2000)
            ->assertJsonPath('total_amount', 9440);
    }

    public function test_quote_validates_payload_and_company(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/quote', [
            'purchase_type' => 'lifetime',
            'addon_branches' => -3,
            'promo_code' => str_repeat('x', 60),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['purchase_type', 'addon_branches', 'promo_code']);

        $this->signed('POST', self::BASE.'/999999/quote', ['purchase_type' => 'renewal'])->assertNotFound();
    }

    // ---------------------------------------------------------------- offline payment

    public function test_offline_payment_renews_expired_subscription(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/offline-payment', [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'reference' => 'NEFT-99',
            'notes' => 'Paid by cheque',
        ])
            ->assertOk()
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('payment.razorpay_response.offline', true)
            ->assertJsonPath('payment.razorpay_response.reference', 'NEFT-99')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.addon_branches', 1);

        $this->assertSame(1, SubscriptionEvent::query()->where('event_type', 'offline_payment_recorded')->count());
        $this->assertNull(SubscriptionEvent::query()->first()->actor_user_id);
    }

    public function test_offline_payment_increments_promo_usage(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')->once()->andReturn(['valid' => true, 'promo' => [
            'code' => 'TEN',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]]);
        $client->shouldReceive('incrementPromoUse')->once()->with('TEN');
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/offline-payment', [
            'purchase_type' => 'renewal',
            'promo_code' => 'TEN',
        ])
            ->assertOk()
            ->assertJsonPath('payment.promo_code', 'TEN')
            ->assertJsonPath('payment.total_amount', '10620.00');
    }

    public function test_offline_payment_rejects_invalid_promo(): void
    {
        $client = $this->mockAdminApi($this->catalogPayload());
        $client->shouldReceive('validatePromo')->once()->andReturn(['valid' => false]);
        $context = $this->createTenantContext();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/offline-payment', [
            'purchase_type' => 'renewal',
            'promo_code' => 'BAD',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Promo code is invalid or no longer applicable.');

        $this->assertSame(0, SubscriptionPayment::query()->count());
    }

    public function test_offline_payment_rejects_blocked_quote_unless_forced(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');
        $uri = self::BASE.'/'.$context['company']->id.'/offline-payment';

        $this->signed('POST', $uri, ['purchase_type' => 'renewal'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Add at least 1 branch add-on(s), or delete extra branches before renewing.');

        $this->signed('POST', $uri, ['purchase_type' => 'renewal', 'force' => true])
            ->assertOk()
            ->assertJsonPath('subscription.status', 'active');
    }

    public function test_offline_payment_validates_payload(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/offline-payment', [
            'purchase_type' => 'renewal',
            'reference' => str_repeat('r', 101),
            'notes' => str_repeat('n', 501),
            'force' => 'maybe',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reference', 'notes', 'force']);

        $this->signed('POST', self::BASE.'/999999/offline-payment', ['purchase_type' => 'renewal'])->assertNotFound();
    }

    // ---------------------------------------------------------------- extend trial

    public function test_extend_trial_updates_dates_and_logs_payment(): void
    {
        $this->mockAdminApi($this->catalogPayload());
        $context = $this->createTenantContext([], 'A', 'trial', now()->addDays(3)->toDateString());
        $newEnd = now()->addDays(20)->toDateString();

        $this->signed('POST', self::BASE.'/'.$context['company']->id.'/extend-trial', [
            'trial_ends_on' => $newEnd,
            'amount' => 250,
            'reference' => 'CASH',
        ])
            ->assertOk()
            ->assertJsonPath('subscription.status', 'trial')
            ->assertJsonPath('payment.type', 'trial_extension')
            ->assertJsonPath('payment.total_amount', '250.00');

        $this->assertSame($newEnd, $context['subscription']->fresh()->trial_ends_on->toDateString());
        $this->assertSame(1, SubscriptionEvent::query()->where('event_type', 'trial_extended')->count());
    }

    public function test_extend_trial_validates_input(): void
    {
        $context = $this->createTenantContext();
        $uri = self::BASE.'/'.$context['company']->id.'/extend-trial';

        $this->signed('POST', $uri, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['trial_ends_on', 'amount']);

        $this->signed('POST', $uri, [
            'trial_ends_on' => now()->subDay()->toDateString(),
            'amount' => -5,
        ])->assertUnprocessable()->assertJsonValidationErrors(['trial_ends_on', 'amount']);

        $this->signed('POST', self::BASE.'/999999/extend-trial', [
            'trial_ends_on' => now()->addDay()->toDateString(),
            'amount' => 0,
        ])->assertNotFound();
    }

    // ---------------------------------------------------------------- update

    public function test_update_applies_only_changed_fields_and_logs_override(): void
    {
        $context = $this->createTenantContext();
        $newEnd = now()->addDays(400)->toDateString();

        $this->signed('PATCH', self::BASE.'/'.$context['company']->id, [
            'status' => 'active',
            'ends_on' => $newEnd,
            'included_branches' => 8,
            'addon_users' => 3,
            'notes' => 'Goodwill extension',
        ])
            ->assertOk()
            ->assertJsonPath('subscription.included_branches', 8)
            ->assertJsonPath('subscription.addon_users', 3);

        $subscription = $context['subscription']->fresh();
        $this->assertSame($newEnd, $subscription->ends_on->toDateString());

        $event = SubscriptionEvent::query()->where('event_type', 'admin_override')->firstOrFail();
        $this->assertSame('Goodwill extension', $event->metadata['notes']);
        $this->assertEqualsCanonicalizing(['ends_on', 'included_branches', 'addon_users'], array_keys($event->metadata['changes']));
    }

    public function test_update_can_clear_trial_end_date(): void
    {
        $context = $this->createTenantContext([], 'A', 'trial');
        $context['subscription']->update(['trial_ends_on' => now()->addDays(5)->toDateString()]);

        $this->signed('PATCH', self::BASE.'/'.$context['company']->id, ['trial_ends_on' => null])->assertOk();

        $this->assertNull($context['subscription']->fresh()->trial_ends_on);
    }

    public function test_update_returns_422_when_nothing_changes(): void
    {
        $context = $this->createTenantContext();

        $this->signed('PATCH', self::BASE.'/'.$context['company']->id, [
            'status' => 'active',
            'included_branches' => 5,
            'trial_ends_on' => null,
            'ends_on' => $context['subscription']->ends_on->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No changes to save.');

        $this->assertSame(0, SubscriptionEvent::query()->count());
    }

    public function test_update_creates_missing_subscription_before_applying_changes(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        $this->signed('PATCH', self::BASE.'/'.$context['company']->id, ['included_branches' => 4])
            ->assertOk()
            ->assertJsonPath('subscription.included_branches', 4)
            ->assertJsonPath('subscription.status', 'trial');

        $this->assertSame(1, CompanySubscription::query()->where('company_id', $context['company']->id)->count());
    }

    public function test_update_validates_input_and_company(): void
    {
        $context = $this->createTenantContext();

        $this->signed('PATCH', self::BASE.'/'.$context['company']->id, [
            'status' => 'paused',
            'included_branches' => 0,
            'users_per_branch' => 0,
            'addon_users' => -1,
            'ends_on' => 'not-a-date',
            'notes' => str_repeat('n', 501),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'included_branches', 'users_per_branch', 'addon_users', 'ends_on', 'notes']);

        $this->signed('PATCH', self::BASE.'/999999', ['included_branches' => 2])->assertNotFound();
    }
}
