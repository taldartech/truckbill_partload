<?php

namespace Tests\Feature\Subscription;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery\MockInterface;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\SignatureVerificationError;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\FakeRazorpayTransport;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    private const RAZORPAY_SECRET = 'rzp_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.razorpay_key' => 'rzp_test_key',
            'subscription.razorpay_secret' => self::RAZORPAY_SECRET,
        ]);

        FakeRazorpayTransport::install();
    }

    protected function tearDown(): void
    {
        FakeRazorpayTransport::uninstall();

        parent::tearDown();
    }

    private function checkout(): SubscriptionCheckoutService
    {
        return app(SubscriptionCheckoutService::class);
    }

    private function mockCatalogClient(): MockInterface
    {
        return $this->mockAdminApi($this->catalogPayload());
    }

    private function signature(string $orderId, string $paymentId): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, self::RAZORPAY_SECRET);
    }

    /**
     * @return array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}
     */
    private function callbackPayload(string $orderId, string $paymentId = 'pay_TEST1'): array
    {
        return [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $this->signature($orderId, $paymentId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lastRazorpayPayload(): array
    {
        $data = FakeRazorpayTransport::$requests[0]['data'];

        return is_string($data) ? json_decode($data, true) : $data;
    }

    // ---------------------------------------------------------------- createCheckoutOrder

    public function test_create_checkout_order_creates_razorpay_order_and_pending_payment(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $context['company']->forceFill([
            'contact_person' => 'Ramesh Kumar',
            'contact_number' => '+91 98765-43210',
            'email' => 'billing@example.com',
            'gst_number' => '08ABCDE1234F1Z5',
            'pan_no' => 'ABCDE1234F',
            'address' => 'Station Road, Udaipur',
        ])->save();

        $order = $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'addon_users' => 2,
        ], $context['owner']->id);

        $this->assertCount(1, FakeRazorpayTransport::$requests);
        $this->assertStringContainsString('/orders', FakeRazorpayTransport::$requests[0]['url']);

        $payload = $this->lastRazorpayPayload();
        $this->assertSame(1416000, (int) $payload['amount']);
        $this->assertSame('INR', $payload['currency']);
        $this->assertSame('sub_'.$context['company']->id, substr($payload['receipt'], 0, strlen('sub_'.$context['company']->id)));
        $this->assertSame($context['company']->name, $payload['notes']['company_name']);
        $this->assertSame('TruckBill Parchun', $payload['notes']['product']);
        $this->assertSame('renewal', $payload['notes']['purchase_type']);
        $this->assertSame('ABCDE1234F', $payload['notes']['pan_number']);
        $this->assertStringContainsString('Branches 2+1', $payload['notes']['plan_details']);
        $this->assertStringContainsString('Total: INR 14160.00', $payload['notes']['charges_breakdown']);
        $this->assertStringContainsString('GST (18%): INR 2160.00', $payload['notes']['charges_breakdown']);

        $this->assertSame('order_TEST123', $order['razorpay_order_id']);
        $this->assertSame('rzp_test_key', $order['razorpay_key']);
        $this->assertSame(14160.0, $order['amount']);
        $this->assertSame(1416000, $order['razorpay_amount_paise']);
        $this->assertSame('TruckBill Parchun renewal', $order['description']);
        $this->assertSame([
            'name' => 'Ramesh Kumar',
            'email' => 'billing@example.com',
            'contact' => '9876543210',
        ], $order['prefill']);

        $payment = SubscriptionPayment::query()->findOrFail($order['payment_id']);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('renewal', $payment->type);
        $this->assertSame('order_TEST123', $payment->razorpay_order_id);
        $this->assertSame(14160.0, (float) $payment->total_amount);
        $this->assertSame(2, $payment->quantities['addon_users']);
        $this->assertSame(1, $payment->usage_snapshot_at_purchase['branches']);

        $event = SubscriptionEvent::query()->where('event_type', 'checkout_created')->firstOrFail();
        $this->assertSame($context['owner']->id, $event->actor_user_id);
        $this->assertSame($payment->id, $event->payment_id);
        $this->assertSame('order_TEST123', $event->metadata['order_id']);
    }

    public function test_create_checkout_order_falls_back_to_actor_email_and_company_name_for_prefill(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();

        $order = $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
            'purchase_type' => 'renewal',
        ], $context['owner']->id);

        $this->assertSame([
            'name' => $context['company']->name,
            'email' => $context['owner']->email,
        ], $order['prefill']);
    }

    public function test_create_checkout_order_includes_promo_in_notes_and_payment(): void
    {
        $client = $this->mockCatalogClient();
        $client->shouldReceive('validatePromo')->with('TEN')->andReturn(['valid' => true, 'promo' => [
            'code' => 'TEN',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]]);
        $context = $this->createTenantContext();

        $order = $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
            'purchase_type' => 'renewal',
            'promo_code' => 'TEN',
            'promo_validated' => true,
        ], $context['owner']->id);

        $payload = $this->lastRazorpayPayload();
        $this->assertSame('TEN', $payload['notes']['promo_code']);
        $this->assertSame('INR 1000.00', $payload['notes']['promo_discount']);
        $this->assertStringContainsString('Promo discount (TEN): INR -1000.00', $payload['notes']['charges_breakdown']);
        $this->assertSame(1062000, $order['razorpay_amount_paise']);
        $this->assertSame('TEN', SubscriptionPayment::query()->findOrFail($order['payment_id'])->promo_code);
    }

    public function test_create_checkout_order_describes_prorated_addons(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $context['subscription']->update(['ends_on' => now()->addDays(49)->toDateString()]);

        $order = $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
            'purchase_type' => 'addon',
            'addon_branches' => 1,
        ], $context['owner']->id);

        $this->assertSame('TruckBill Parchun addon', $order['description']);
        $this->assertStringContainsString('Prorated 50 days', $this->lastRazorpayPayload()['notes']['plan_details']);
    }

    public function test_create_checkout_order_rejects_blocked_quotes_without_calling_razorpay(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');

        try {
            $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
                'purchase_type' => 'renewal',
            ], $context['owner']->id);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Add at least 1 branch add-on(s)', $exception->getMessage());
        }

        $this->assertSame([], FakeRazorpayTransport::$requests);
        $this->assertSame(0, SubscriptionPayment::query()->count());
    }

    public function test_create_checkout_order_rejects_zero_value_addon_quotes(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Checkout is not available.');

        $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
            'purchase_type' => 'addon',
        ], $context['owner']->id);
    }

    public function test_create_checkout_order_does_not_persist_anything_when_razorpay_rejects_order(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        FakeRazorpayTransport::$status = 400;
        FakeRazorpayTransport::$body = ['error' => [
            'code' => 'BAD_REQUEST_ERROR',
            'description' => 'Amount exceeds maximum',
        ]];

        try {
            $this->checkout()->createCheckoutOrder($context['company']->fresh('subscription'), [
                'purchase_type' => 'renewal',
            ], $context['owner']->id);
            $this->fail('Expected BadRequestError.');
        } catch (BadRequestError $exception) {
            $this->assertSame('Amount exceeds maximum', $exception->getMessage());
        }

        $this->assertSame(0, SubscriptionPayment::query()->count());
        $this->assertSame(0, SubscriptionEvent::query()->count());
    }

    // ---------------------------------------------------------------- handleCallback

    private function pendingRenewalPayment(array $context, ?string $promoCode = null): SubscriptionPayment
    {
        return SubscriptionPayment::create([
            'company_id' => $context['company']->id,
            'type' => 'renewal',
            'status' => 'pending',
            'razorpay_order_id' => 'order_RENEW',
            'promo_code' => $promoCode,
            'quantities' => ['addon_branches' => 1, 'addon_users' => 3],
            'subtotal' => 10000,
            'promo_discount' => 0,
            'gst_amount' => 1800,
            'total_amount' => 11800,
            'catalog_snapshot' => $this->catalogPayload(['included_branches' => 4, 'users_per_branch' => 6])['catalog'],
        ]);
    }

    public function test_callback_activates_expired_subscription_for_a_new_term(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $payment = $this->pendingRenewalPayment($context);

        $result = $this->checkout()->handleCallback($this->callbackPayload('order_RENEW'), $context['owner']->id);

        $this->assertSame('completed', $result->status);
        $this->assertSame('pay_TEST1', $result->razorpay_payment_id);
        $this->assertSame($payment->id, $result->id);

        $subscription = $context['subscription']->fresh();
        $this->assertSame('active', $subscription->status);
        $this->assertSame(now()->toDateString(), $subscription->starts_on->toDateString());
        $this->assertSame(now()->addYear()->subDay()->toDateString(), $subscription->ends_on->toDateString());
        $this->assertSame(4, $subscription->included_branches);
        $this->assertSame(6, $subscription->users_per_branch);
        $this->assertSame(1, $subscription->addon_branches);
        $this->assertSame(3, $subscription->addon_users);
        $this->assertNull($subscription->trial_ends_on);
        $this->assertNotNull($subscription->last_renewed_at);

        $event = SubscriptionEvent::query()->where('event_type', 'payment_completed')->firstOrFail();
        $this->assertSame('renewal', $event->metadata['type']);
        $this->assertSame($context['owner']->id, $event->actor_user_id);
    }

    public function test_callback_creates_subscription_for_company_without_one(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $context['subscription']->delete();
        $this->pendingRenewalPayment($context);

        $this->checkout()->handleCallback($this->callbackPayload('order_RENEW'), $context['owner']->id);

        $subscription = CompanySubscription::query()->where('company_id', $context['company']->id)->firstOrFail();
        $this->assertSame('active', $subscription->status);
        $this->assertSame(4, $subscription->included_branches);
    }

    public function test_callback_queues_upcoming_subscription_when_current_plan_is_still_active(): void
    {
        $client = $this->mockCatalogClient();
        $client->shouldReceive('incrementPromoUse')->once()->with('TEN');
        $this->useUpcomingSubscriptionsWithoutDateColumns();
        $context = $this->createTenantContext();
        $originalEndsOn = $context['subscription']->ends_on->toDateString();
        $payment = $this->pendingRenewalPayment($context, 'TEN');

        $this->checkout()->handleCallback($this->callbackPayload('order_RENEW'), $context['owner']->id);

        $this->assertSame($originalEndsOn, $context['subscription']->fresh()->ends_on->toDateString());
        $upcoming = UpcomingSubscription::query()->where('company_id', $context['company']->id)->firstOrFail();
        $this->assertSame('pending', $upcoming->status);
        $this->assertSame($payment->id, $upcoming->payment_id);
        $this->assertSame(4, $upcoming->included_branches);
        $this->assertSame(1, $upcoming->addon_branches);
        $this->assertSame(3, $upcoming->addon_users);
        $this->assertSame(['code' => 'TEN'], $upcoming->promo_snapshot);
    }

    public function test_callback_for_addon_payment_increases_current_addons(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $context['subscription']->update(['addon_branches' => 1, 'addon_users' => 1]);
        SubscriptionPayment::create([
            'company_id' => $context['company']->id,
            'type' => 'addon',
            'status' => 'pending',
            'razorpay_order_id' => 'order_ADDON',
            'quantities' => ['addon_branches' => 2, 'addon_users' => 4],
            'subtotal' => 100,
            'promo_discount' => 0,
            'gst_amount' => 18,
            'total_amount' => 118,
        ]);

        $this->checkout()->handleCallback($this->callbackPayload('order_ADDON'), $context['owner']->id);

        $subscription = $context['subscription']->fresh();
        $this->assertSame(3, $subscription->addon_branches);
        $this->assertSame(5, $subscription->addon_users);
        $this->assertSame(0, UpcomingSubscription::query()->count());
    }

    public function test_callback_increments_promo_usage_and_ignores_increment_failures(): void
    {
        $client = $this->mockCatalogClient();
        $client->shouldReceive('incrementPromoUse')
            ->once()
            ->with('TEN')
            ->andThrow(new SubscriptionCatalogUnavailableException);
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $this->pendingRenewalPayment($context, 'TEN');

        $result = $this->checkout()->handleCallback($this->callbackPayload('order_RENEW'), $context['owner']->id);

        $this->assertSame('completed', $result->status);
        $this->assertSame('active', $context['subscription']->fresh()->status);
    }

    public function test_callback_is_idempotent_for_completed_payments(): void
    {
        $client = $this->mockCatalogClient();
        $client->shouldNotReceive('incrementPromoUse');
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $payment = $this->pendingRenewalPayment($context, 'TEN');
        $payment->update(['status' => 'completed']);

        $result = $this->checkout()->handleCallback([
            'razorpay_order_id' => 'order_RENEW',
            'razorpay_payment_id' => 'pay_X',
            'razorpay_signature' => 'not-even-checked',
        ], $context['owner']->id);

        $this->assertSame('completed', $result->status);
        $this->assertSame('expired', $context['subscription']->fresh()->status);
        $this->assertSame(0, SubscriptionEvent::query()->count());
    }

    public function test_callback_rejects_invalid_signature_and_leaves_payment_pending(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $payment = $this->pendingRenewalPayment($context);

        try {
            $this->checkout()->handleCallback([
                'razorpay_order_id' => 'order_RENEW',
                'razorpay_payment_id' => 'pay_TEST1',
                'razorpay_signature' => 'forged',
            ], $context['owner']->id);
            $this->fail('Expected SignatureVerificationError.');
        } catch (SignatureVerificationError) {
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertSame('expired', $context['subscription']->fresh()->status);
        }
    }

    public function test_callback_for_unknown_order_fails(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();

        $this->expectException(ModelNotFoundException::class);

        $this->checkout()->handleCallback($this->callbackPayload('order_MISSING'), $context['owner']->id);
    }

    // ---------------------------------------------------------------- HTTP endpoints

    private function superAdmin(array $context): void
    {
        $this->makeSuperAdmin($context['owner']);
        $this->actingAs($context['owner']);
    }

    public function test_checkout_endpoint_returns_razorpay_order_for_super_admin(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->superAdmin($context);

        $this->postJson(route('subscription.checkout'), [
            'company_id' => $context['company']->id,
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'addon_trucks' => 99,
            'addon_lr_credits' => 99,
        ])
            ->assertOk()
            ->assertJsonPath('razorpay_order_id', 'order_TEST123')
            ->assertJsonPath('razorpay_key', 'rzp_test_key')
            ->assertJsonPath('amount', 12980)
            ->assertJsonStructure(['payment_id', 'description', 'prefill', 'notes']);

        $payment = SubscriptionPayment::query()->firstOrFail();
        $this->assertSame(0, $payment->quantities['addon_trucks']);
        $this->assertSame(0, $payment->quantities['addon_lr_credits']);
    }

    public function test_checkout_endpoint_returns_422_for_blocked_quote(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');
        $this->superAdmin($context);

        $this->postJson(route('subscription.checkout'), [
            'company_id' => $context['company']->id,
            'purchase_type' => 'renewal',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Add at least 1 branch add-on(s), or delete extra branches before renewing.');

        $this->assertSame([], FakeRazorpayTransport::$requests);
    }

    public function test_checkout_endpoint_returns_503_when_catalog_is_unavailable(): void
    {
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);
        $context = $this->createTenantContext();
        $this->superAdmin($context);

        $this->postJson(route('subscription.checkout'), [
            'company_id' => $context['company']->id,
            'purchase_type' => 'renewal',
        ])->assertStatus(503);
    }

    public function test_checkout_endpoint_validates_input_and_company_ownership(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $foreign = $this->createTenantContext([], 'Foreign');
        $this->superAdmin($context);

        $this->postJson(route('subscription.checkout'), [
            'company_id' => $context['company']->id,
            'purchase_type' => 'nope',
        ])->assertUnprocessable()->assertJsonValidationErrors('purchase_type');

        $this->postJson(route('subscription.checkout'), [
            'company_id' => $foreign['company']->id,
            'purchase_type' => 'renewal',
        ])->assertForbidden();
    }

    public function test_callback_endpoint_completes_payment_for_super_admin(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $this->pendingRenewalPayment($context);
        $this->superAdmin($context);

        $this->postJson(route('subscription.callback'), $this->callbackPayload('order_RENEW'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('payment.razorpay_payment_id', 'pay_TEST1');

        $this->assertSame('active', $context['subscription']->fresh()->status);
    }

    public function test_callback_endpoint_validates_required_fields(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->superAdmin($context);

        $this->postJson(route('subscription.callback'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature']);
    }

    public function test_callback_endpoint_returns_404_for_unknown_order(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->superAdmin($context);

        $this->postJson(route('subscription.callback'), $this->callbackPayload('order_MISSING'))
            ->assertNotFound();
    }

    public function test_callback_endpoint_does_not_complete_payment_with_bad_signature(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());
        $payment = $this->pendingRenewalPayment($context);
        $this->superAdmin($context);

        $this->postJson(route('subscription.callback'), [
            'razorpay_order_id' => 'order_RENEW',
            'razorpay_payment_id' => 'pay_TEST1',
            'razorpay_signature' => 'forged',
        ])->assertStatus(500);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---------------------------------------------------------------- offline purchases & trial extension

    public function test_record_offline_purchase_activates_expired_subscription(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(20)->toDateString());

        $payment = $this->checkout()->recordOfflinePurchase($context['company']->fresh('subscription'), [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'reference' => 'UTR123',
            'notes' => 'Bank transfer',
            'admin_user_id' => 7,
        ], $context['owner']->id);

        $this->assertSame('completed', $payment->status);
        $this->assertTrue($payment->razorpay_response['offline']);
        $this->assertSame('UTR123', $payment->razorpay_response['reference']);
        $this->assertSame('active', $context['subscription']->fresh()->status);

        $event = SubscriptionEvent::query()->where('event_type', 'offline_payment_recorded')->firstOrFail();
        $this->assertSame('UTR123', $event->metadata['reference']);
        $this->assertSame(7, $event->metadata['admin_user_id']);
    }

    public function test_record_offline_purchase_rejects_invalid_promo(): void
    {
        $client = $this->mockCatalogClient();
        $client->shouldReceive('validatePromo')->once()->andReturn(['valid' => false]);
        $context = $this->createTenantContext();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Promo code is invalid or no longer applicable.');

        $this->checkout()->recordOfflinePurchase($context['company']->fresh('subscription'), [
            'purchase_type' => 'renewal',
            'promo_code' => ' BAD ',
        ]);
    }

    public function test_record_offline_purchase_respects_blocking_errors_unless_forced(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');
        $this->useUpcomingSubscriptionsWithoutDateColumns();
        $company = $context['company']->fresh('subscription');

        try {
            $this->checkout()->recordOfflinePurchase($company, ['purchase_type' => 'renewal']);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('branch add-on', $exception->getMessage());
        }

        $forced = $this->checkout()->recordOfflinePurchase($company, ['purchase_type' => 'renewal', 'force' => true]);

        $this->assertSame('completed', $forced->status);
        $this->assertSame(1, UpcomingSubscription::query()->count());
    }

    public function test_record_trial_extension_updates_existing_subscription_and_records_payment(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext([], 'A', 'trial', now()->addDays(2)->toDateString());
        $newEnd = now()->addDays(30)->toDateString();

        $result = $this->checkout()->recordTrialExtension($context['company']->fresh('subscription'), [
            'trial_ends_on' => $newEnd,
            'amount' => '499.499',
            'reference' => 'CASH-1',
            'admin_user_id' => 3,
        ], $context['owner']->id);

        $this->assertSame('trial', $result['subscription']->status);
        $this->assertSame($newEnd, $result['subscription']->ends_on->toDateString());
        $this->assertSame($newEnd, $result['subscription']->trial_ends_on->toDateString());
        $this->assertSame('trial_extension', $result['payment']->type);
        $this->assertSame(499.5, (float) $result['payment']->total_amount);
        $this->assertTrue($result['payment']->razorpay_response['trial_extension']);

        $event = SubscriptionEvent::query()->where('event_type', 'trial_extended')->firstOrFail();
        $this->assertSame('CASH-1', $event->metadata['reference']);
        $this->assertSame(now()->addDays(2)->toDateString(), $event->metadata['previous_ends_on']);
        $this->assertArrayNotHasKey('notes', $event->metadata);
    }

    public function test_record_trial_extension_creates_subscription_when_missing(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        $result = $this->checkout()->recordTrialExtension($context['company']->fresh('subscription'), [
            'trial_ends_on' => now()->toDateString(),
            'amount' => 0,
        ]);

        $this->assertSame('trial', $result['subscription']->status);
        $this->assertSame(2, $result['subscription']->included_branches);
        $this->assertSame(now()->toDateString(), $result['subscription']->ends_on->toDateString());
        $this->assertSame(0.0, (float) $result['payment']->total_amount);
    }

    public function test_record_trial_extension_validates_date_and_amount(): void
    {
        $this->mockCatalogClient();
        $context = $this->createTenantContext();
        $company = $context['company']->fresh('subscription');

        try {
            $this->checkout()->recordTrialExtension($company, [
                'trial_ends_on' => now()->subDay()->toDateString(),
                'amount' => 10,
            ]);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Trial end date must be today or a future date.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount charged must be zero or greater.');

        $this->checkout()->recordTrialExtension($company, [
            'trial_ends_on' => now()->addDay()->toDateString(),
            'amount' => -1,
        ]);
    }

    // ---------------------------------------------------------------- upcoming subscriptions

    private function queueUpcoming(Company $company, array $overrides = []): UpcomingSubscription
    {
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'razorpay_order_id' => 'order_'.uniqid(),
            'quantities' => [],
            'subtotal' => 1,
            'promo_discount' => 0,
            'gst_amount' => 0,
            'total_amount' => 1,
        ]);

        return UpcomingSubscription::create(array_merge([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
            'included_branches' => 7,
            'users_per_branch' => 8,
            'addon_branches' => 1,
            'addon_users' => 2,
        ], $overrides));
    }

    public function test_renewal_term_following_spans_one_year_from_next_day(): void
    {
        $term = $this->checkout()->renewalTermFollowing('2026-03-31');

        $this->assertSame(['starts_on' => '2026-04-01', 'ends_on' => '2027-03-31'], $term);
    }

    public function test_apply_upcoming_for_company_covers_every_branch(): void
    {
        $this->useUpcomingSubscriptionsWithoutDateColumns();
        $service = $this->checkout();

        $this->assertFalse($service->applyUpcomingForCompany(999999));

        $running = $this->createTenantContext();
        $this->assertFalse($service->applyUpcomingForCompany($running['company']->id));

        $lapsed = $this->createTenantContext([], 'Lapsed', 'active', now()->subDay()->toDateString());
        $this->assertFalse($service->applyUpcomingForCompany($lapsed['company']->id));
        $this->assertSame('expired', $lapsed['subscription']->fresh()->status);

        $renewing = $this->createTenantContext([], 'Renewing', 'active', now()->subDay()->toDateString());
        $upcoming = $this->queueUpcoming($renewing['company']);

        $this->assertTrue($service->applyUpcomingForCompany($renewing['company']->id));

        $subscription = $renewing['subscription']->fresh();
        $this->assertSame('active', $subscription->status);
        $this->assertSame(now()->toDateString(), $subscription->starts_on->toDateString());
        $this->assertSame(now()->addYear()->subDay()->toDateString(), $subscription->ends_on->toDateString());
        $this->assertSame(7, $subscription->included_branches);
        $this->assertSame(2, $subscription->addon_users);
        $this->assertSame('applied', $upcoming->fresh()->status);
    }

    public function test_apply_due_upcoming_subscriptions_returns_number_applied(): void
    {
        $this->useUpcomingSubscriptionsWithoutDateColumns();

        $due = $this->createTenantContext([], 'Due', 'active', now()->subDay()->toDateString());
        $this->queueUpcoming($due['company']);
        $this->createTenantContext([], 'Future');
        $this->createTenantContext([], 'NoQueue', 'expired', now()->subDays(4)->toDateString());

        $this->assertSame(1, $this->checkout()->applyDueUpcomingSubscriptions());
        $this->assertSame(0, $this->checkout()->applyDueUpcomingSubscriptions());
    }
}
