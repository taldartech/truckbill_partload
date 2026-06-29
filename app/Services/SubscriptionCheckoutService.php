<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class SubscriptionCheckoutService
{
    public function __construct(
        private readonly AdminApiClient $adminApi,
        private readonly SubscriptionPricingService $pricingService,
        private readonly SubscriptionUsageService $usageService
    ) {}

    public function createCheckoutOrder(Company $company, array $input, int $actorUserId): array
    {
        $quote = $this->pricingService->quote($company, $input);

        if (! $quote['can_checkout']) {
            throw new \InvalidArgumentException(implode(' ', $quote['blocking_errors'] ?: ['Checkout is not available.']));
        }

        $api = new Api(config('subscription.razorpay_key'), config('subscription.razorpay_secret'));
        $order = $api->order->create([
            'receipt' => 'sub_'.$company->id.'_'.time(),
            'amount' => Money::toPaise($quote['total_amount']),
            'currency' => 'INR',
        ]);

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => $input['purchase_type'],
            'status' => 'pending',
            'razorpay_order_id' => $order['id'],
            'promo_code' => $input['promo_code'] ?? null,
            'quantities' => $quote['quantities'],
            'subtotal' => $quote['subtotal'],
            'promo_discount' => $quote['promo_discount'],
            'gst_amount' => $quote['gst_amount'],
            'total_amount' => $quote['total_amount'],
            'catalog_snapshot' => $quote['catalog_snapshot'],
            'usage_snapshot_at_purchase' => $quote['current_usage'],
        ]);

        SubscriptionEvent::create([
            'company_id' => $company->id,
            'event_type' => 'checkout_created',
            'payment_id' => $payment->id,
            'actor_user_id' => $actorUserId,
            'metadata' => ['order_id' => $order['id']],
        ]);

        return [
            'payment_id' => $payment->id,
            'razorpay_order_id' => $order['id'],
            'razorpay_key' => config('subscription.razorpay_key'),
            'amount' => (float) $quote['total_amount'],
            'razorpay_amount_paise' => Money::toPaise($quote['total_amount']),
        ];
    }

    public function handleCallback(array $payload, int $actorUserId): SubscriptionPayment
    {
        $payment = SubscriptionPayment::query()
            ->where('razorpay_order_id', $payload['razorpay_order_id'])
            ->firstOrFail();

        if ($payment->status === 'completed') {
            return $payment;
        }

        $api = new Api(config('subscription.razorpay_key'), config('subscription.razorpay_secret'));
        $api->utility->verifyPaymentSignature($payload);

        return DB::transaction(function () use ($payment, $payload, $actorUserId) {
            $payment->update([
                'status' => 'completed',
                'razorpay_payment_id' => $payload['razorpay_payment_id'],
                'razorpay_signature' => $payload['razorpay_signature'],
            ]);

            $company = $payment->company;
            $catalog = $payment->catalog_snapshot ?? [];

            $subscription = $company->subscription ?? CompanySubscription::create([
                'company_id' => $company->id,
                'status' => 'expired',
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->toDateString(),
                'included_branches' => (int) ($catalog['included_branches'] ?? 0),
                'users_per_branch' => (int) ($catalog['users_per_branch'] ?? 0),
            ]);

            $quantities = $payment->quantities ?? [];

            if ($payment->type === 'addon') {
                $subscription->update([
                    'addon_branches' => $subscription->addon_branches + (int) ($quantities['addon_branches'] ?? 0),
                    'addon_users' => $subscription->addon_users + (int) ($quantities['addon_users'] ?? 0),
                ]);
            } else {
                $isActive = $subscription->isActive();

                $snapshot = [
                    'included_branches' => (int) $catalog['included_branches'],
                    'users_per_branch' => (int) $catalog['users_per_branch'],
                    'included_lr_credits' => 0,
                    'included_trucks' => 0,
                    'addon_branches' => (int) ($quantities['addon_branches'] ?? 0),
                    'addon_users' => (int) ($quantities['addon_users'] ?? 0),
                    'addon_trucks' => 0,
                    'addon_lr_credits' => 0,
                ];

                if ($isActive) {
                    UpcomingSubscription::create([
                        'company_id' => $company->id,
                        'payment_id' => $payment->id,
                        'status' => 'pending',
                        'catalog_snapshot' => $catalog,
                        'promo_snapshot' => $payment->promo_code ? ['code' => $payment->promo_code] : null,
                        ...$snapshot,
                    ]);
                } else {
                    $term = $this->renewalTermFollowing(
                        $subscription->isActive()
                            ? $subscription->ends_on
                            : now()->subDay()->toDateString(),
                    );

                    $subscription->update([
                        'status' => 'active',
                        'starts_on' => $term['starts_on'],
                        'ends_on' => $term['ends_on'],
                        'trial_ends_on' => null,
                        'included_branches' => $snapshot['included_branches'],
                        'users_per_branch' => $snapshot['users_per_branch'],
                        'included_lr_credits' => 0,
                        'included_trucks' => 0,
                        'addon_branches' => $snapshot['addon_branches'],
                        'addon_users' => $snapshot['addon_users'],
                        'addon_trucks' => 0,
                        'addon_lr_credits' => 0,
                        'lr_credits_remaining' => 0,
                        'last_renewed_at' => now(),
                    ]);
                }
            }

            if ($payment->promo_code) {
                try {
                    $this->adminApi->incrementPromoUse($payment->promo_code);
                } catch (\Throwable) {
                    // Promo increment failure should not block payment completion.
                }
            }

            SubscriptionEvent::create([
                'company_id' => $company->id,
                'event_type' => 'payment_completed',
                'payment_id' => $payment->id,
                'actor_user_id' => $actorUserId,
                'metadata' => ['type' => $payment->type],
            ]);

            return $payment->fresh();
        });
    }

    public function applyUpcomingForCompany(int $companyId): bool
    {
        $today = now()->toDateString();

        $subscription = CompanySubscription::query()
            ->where('company_id', $companyId)
            ->first();

        if ($subscription === null || $subscription->ends_on->toDateString() > $today) {
            return false;
        }

        $upcoming = UpcomingSubscription::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if ($upcoming === null) {
            if ($subscription->status !== 'expired') {
                $subscription->update(['status' => 'expired']);
            }

            return false;
        }

        $term = $this->renewalTermFollowing($subscription->ends_on);

        $subscription->update([
            'status' => 'active',
            'starts_on' => $term['starts_on'],
            'ends_on' => $term['ends_on'],
            'trial_ends_on' => null,
            'included_branches' => $upcoming->included_branches,
            'users_per_branch' => $upcoming->users_per_branch,
            'included_lr_credits' => 0,
            'included_trucks' => 0,
            'addon_branches' => $upcoming->addon_branches,
            'addon_users' => $upcoming->addon_users,
            'addon_trucks' => 0,
            'addon_lr_credits' => 0,
            'lr_credits_remaining' => 0,
            'last_renewed_at' => now(),
        ]);

        $upcoming->update(['status' => 'applied']);

        return true;
    }

    public function applyDueUpcomingSubscriptions(): int
    {
        $applied = 0;
        $today = now()->toDateString();

        $subscriptions = CompanySubscription::query()
            ->where('ends_on', '<=', $today)
            ->whereIn('status', ['active', 'trial', 'expired'])
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($this->applyUpcomingForCompany($subscription->company_id)) {
                $applied++;
            }
        }

        return $applied;
    }

    /**
     * @return array{starts_on: string, ends_on: string}
     */
    public function renewalTermFollowing(Carbon|string $previousEndsOn): array
    {
        $startsOn = Carbon::parse($previousEndsOn)->addDay()->toDateString();
        $endsOn = Carbon::parse($startsOn)->addYear()->subDay()->toDateString();

        return [
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ];
    }
}
