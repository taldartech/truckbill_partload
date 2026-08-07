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

        $checkoutMeta = $this->razorpayCheckoutMeta($company, $actorUserId, $quote);
        $description = $this->razorpayDescription($quote);

        $api = new Api(config('subscription.razorpay_key'), config('subscription.razorpay_secret'));
        $orderPayload = [
            'receipt' => 'sub_'.$company->id.'_'.time(),
            'amount' => Money::toPaise($quote['total_amount']),
            'currency' => 'INR',
        ];
        if ($checkoutMeta['notes'] !== []) {
            $orderPayload['notes'] = $checkoutMeta['notes'];
        }
        $order = $api->order->create($orderPayload);

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
            'description' => $description,
            'prefill' => $checkoutMeta['prefill'],
            'notes' => $checkoutMeta['notes'],
        ];
    }

    /**
     * @param  array<string, mixed>  $quote
     * @return array{notes: array<string, string>, prefill: array<string, string>}
     */
    private function razorpayCheckoutMeta(Company $company, int $actorUserId, array $quote): array
    {
        $actor = \App\Models\User::query()->find($actorUserId);
        $contact = $this->normalizeIndianMobile($company->contact_number);
        $pan = $company->pan_number ?? $company->pan_no ?? null;

        $notes = array_filter([
            'company_name' => $company->name,
            'mobile' => $company->contact_number,
            'gst_number' => $company->gst_number,
            'pan_number' => $pan,
            'contact_person' => $company->contact_person,
            'gst_type' => $company->gst_type,
            'gst_scheme' => $company->gst_scheme,
            'address' => filled($company->address) ? (string) $company->address : null,
            'product' => $this->productDisplayName(),
            'purchase_type' => (string) ($quote['purchase_type'] ?? ''),
            'promo_code' => $this->promoCodeFromQuote($quote),
            'promo_discount' => ((float) ($quote['promo_discount'] ?? 0)) > 0
                ? $this->formatRupee((float) $quote['promo_discount'])
                : null,
            'plan_details' => $this->formatPlanDetails($quote),
            'charges_breakdown' => $this->formatChargesBreakdown($quote),
        ], fn ($value) => filled($value));

        $notes = array_map(
            static fn ($value): string => \Illuminate\Support\Str::limit((string) $value, 256, ''),
            $notes,
        );

        $prefill = array_filter([
            'name' => $company->contact_person ?: $company->name,
            'email' => $company->email ?: $actor?->email,
            'contact' => $contact,
        ], fn ($value) => filled($value));

        return [
            'notes' => $notes,
            'prefill' => array_map(static fn ($value): string => (string) $value, $prefill),
        ];
    }

    /**
     * @param  array<string, mixed>  $quote
     */
    private function razorpayDescription(array $quote): string
    {
        $type = (string) ($quote['purchase_type'] ?? 'subscription');

        return $this->productDisplayName().' '.$type;
    }

    private function productDisplayName(): string
    {
        return match ((string) config('subscription.product')) {
            'broker' => 'TruckBill Broker',
            'ownfleet' => 'TruckBill Ownfleet',
            'parchun' => 'TruckBill Parchun',
            default => 'TruckBill',
        };
    }

    /**
     * @param  array<string, mixed>  $quote
     */
    private function formatPlanDetails(array $quote): string
    {
        $catalog = is_array($quote['catalog_snapshot'] ?? null) ? $quote['catalog_snapshot'] : [];
        $quantities = is_array($quote['quantities'] ?? null) ? $quote['quantities'] : [];
        $parts = [];

        if (isset($catalog['included_branches']) || isset($quantities['addon_branches'])) {
            $parts[] = 'Branches '.(int) ($catalog['included_branches'] ?? 0).'+'.(int) ($quantities['addon_branches'] ?? 0);
        }
        if (isset($catalog['users_per_branch']) || isset($quantities['addon_users'])) {
            $parts[] = 'Users/branch '.(int) ($catalog['users_per_branch'] ?? 0).'+'.(int) ($quantities['addon_users'] ?? 0).' users';
        }
        if ((int) ($catalog['included_lr_credits'] ?? 0) > 0 || (int) ($quantities['addon_lr_credits'] ?? 0) > 0) {
            $parts[] = 'LR credits '.(int) ($catalog['included_lr_credits'] ?? 0).'+'.(int) ($quantities['addon_lr_credits'] ?? 0);
        }
        if ((int) ($catalog['included_trucks'] ?? 0) > 0 || (int) ($quantities['addon_trucks'] ?? 0) > 0) {
            $parts[] = 'Trucks '.(int) ($catalog['included_trucks'] ?? 0).'+'.(int) ($quantities['addon_trucks'] ?? 0);
        }
        if (! empty($quote['proration']['remaining_days'])) {
            $parts[] = 'Prorated '.$quote['proration']['remaining_days'].' days';
        }

        return implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $quote
     */
    private function formatChargesBreakdown(array $quote): string
    {
        $parts = [];
        $promoCode = $this->promoCodeFromQuote($quote);

        foreach ($quote['line_items'] ?? [] as $item) {
            if (! is_array($item) || ! isset($item['label'])) {
                continue;
            }

            $label = (string) $item['label'];
            if ($promoCode !== null && stripos($label, 'promo') !== false) {
                $label .= ' ('.$promoCode.')';
            }

            $parts[] = $label.': '.$this->formatRupee((float) ($item['amount'] ?? 0));
        }

        if ($promoCode !== null && ((float) ($quote['promo_discount'] ?? 0)) > 0) {
            $hasPromoLine = collect($parts)->contains(
                fn (string $part) => stripos($part, 'promo') !== false
            );
            if (! $hasPromoLine) {
                $parts[] = 'Promo discount ('.$promoCode.'): -'.$this->formatRupee((float) $quote['promo_discount']);
            }
        }

        $gstRate = $quote['gst_rate_percent'] ?? null;
        $gstLabel = $gstRate !== null ? 'GST ('.$gstRate.'%)' : 'GST';
        $parts[] = $gstLabel.': '.$this->formatRupee((float) ($quote['gst_amount'] ?? 0));
        $parts[] = 'Total: '.$this->formatRupee((float) ($quote['total_amount'] ?? 0));

        return implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $quote
     */
    private function promoCodeFromQuote(array $quote): ?string
    {
        $snapshot = $quote['promo_snapshot'] ?? null;
        if (is_array($snapshot) && filled($snapshot['code'] ?? null)) {
            return (string) $snapshot['code'];
        }

        return null;
    }

    private function formatRupee(float $amount): string
    {
        return 'INR '.number_format($amount, 2, '.', '');
    }

    private function normalizeIndianMobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile) ?: '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
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

            return $this->fulfillCompletedPayment($payment, $actorUserId, 'payment_completed');
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function recordOfflinePurchase(Company $company, array $input, ?int $actorUserId = null): SubscriptionPayment
    {
        $quoteInput = [
            'purchase_type' => $input['purchase_type'],
            'addon_branches' => (int) ($input['addon_branches'] ?? 0),
            'addon_users' => (int) ($input['addon_users'] ?? 0),
            'addon_trucks' => 0,
            'addon_lr_credits' => 0,
            'promo_code' => null,
            'promo_validated' => false,
        ];

        $quote = $this->pricingService->quote($company, $quoteInput);

        if (! ($input['force'] ?? false) && ! $quote['can_checkout']) {
            throw new \InvalidArgumentException(implode(' ', $quote['blocking_errors'] ?: ['Checkout is not available.']));
        }

        return DB::transaction(function () use ($company, $input, $quote, $actorUserId) {
            $payment = SubscriptionPayment::create([
                'company_id' => $company->id,
                'type' => $input['purchase_type'],
                'status' => 'completed',
                'promo_code' => null,
                'quantities' => $quote['quantities'],
                'subtotal' => $quote['subtotal'],
                'promo_discount' => $quote['promo_discount'],
                'gst_amount' => $quote['gst_amount'],
                'total_amount' => $quote['total_amount'],
                'catalog_snapshot' => $quote['catalog_snapshot'],
                'usage_snapshot_at_purchase' => $quote['current_usage'],
                'razorpay_response' => [
                    'offline' => true,
                    'reference' => $input['reference'] ?? null,
                    'notes' => $input['notes'] ?? null,
                ],
            ]);

            return $this->fulfillCompletedPayment($payment, $actorUserId, 'offline_payment_recorded', [
                'reference' => $input['reference'] ?? null,
                'admin_user_id' => $input['admin_user_id'] ?? null,
            ]);
        });
    }

    /**
     * Admin-only chargeable trial extension. Updates trial/access end dates and records a payment.
     *
     * @param  array{trial_ends_on: string, amount: float|int|string, reference?: string|null, notes?: string|null, admin_user_id?: int|null}  $input
     * @return array{payment: SubscriptionPayment, subscription: CompanySubscription}
     */
    public function recordTrialExtension(Company $company, array $input, ?int $actorUserId = null): array
    {
        $trialEndsOn = Carbon::parse($input['trial_ends_on'])->startOfDay();
        $today = now()->startOfDay();

        if ($trialEndsOn->lt($today)) {
            throw new \InvalidArgumentException('Trial end date must be today or a future date.');
        }

        $amount = round((float) $input['amount'], 2);

        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount charged must be zero or greater.');
        }

        return DB::transaction(function () use ($company, $input, $trialEndsOn, $amount, $actorUserId) {
            $subscription = $company->subscription ?? CompanySubscription::create([
                'company_id' => $company->id,
                'status' => 'trial',
                'starts_on' => now()->toDateString(),
                'ends_on' => $trialEndsOn->toDateString(),
                'trial_ends_on' => $trialEndsOn->toDateString(),
                'included_branches' => 2,
                'users_per_branch' => 3,
                'included_lr_credits' => 0,
                'included_trucks' => 0,
                'lr_credits_remaining' => 0,
            ]);

            $previousEndsOn = $subscription->ends_on?->toDateString();
            $previousTrialEndsOn = $subscription->trial_ends_on?->toDateString();

            $subscription->update([
                'status' => 'trial',
                'starts_on' => $subscription->starts_on?->toDateString() ?? now()->toDateString(),
                'ends_on' => $trialEndsOn->toDateString(),
                'trial_ends_on' => $trialEndsOn->toDateString(),
            ]);

            $payment = SubscriptionPayment::create([
                'company_id' => $company->id,
                'type' => 'trial_extension',
                'status' => 'completed',
                'promo_code' => null,
                'quantities' => [],
                'subtotal' => $amount,
                'promo_discount' => 0,
                'gst_amount' => 0,
                'total_amount' => $amount,
                'catalog_snapshot' => null,
                'usage_snapshot_at_purchase' => $this->usageService->usageForCompany($company),
                'razorpay_response' => [
                    'offline' => true,
                    'trial_extension' => true,
                    'reference' => $input['reference'] ?? null,
                    'notes' => $input['notes'] ?? null,
                    'trial_ends_on' => $trialEndsOn->toDateString(),
                    'amount_charged' => $amount,
                ],
            ]);

            SubscriptionEvent::create([
                'company_id' => $company->id,
                'event_type' => 'trial_extended',
                'payment_id' => $payment->id,
                'actor_user_id' => $actorUserId,
                'metadata' => array_filter([
                    'trial_ends_on' => $trialEndsOn->toDateString(),
                    'amount' => $amount,
                    'previous_ends_on' => $previousEndsOn,
                    'previous_trial_ends_on' => $previousTrialEndsOn,
                    'reference' => $input['reference'] ?? null,
                    'notes' => $input['notes'] ?? null,
                    'admin_user_id' => $input['admin_user_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ]);

            return [
                'payment' => $payment->fresh(),
                'subscription' => $subscription->fresh(),
            ];
        });
    }

    private function fulfillCompletedPayment(
        SubscriptionPayment $payment,
        ?int $actorUserId,
        string $eventType,
        array $eventMetadata = [],
    ): SubscriptionPayment {
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
            'event_type' => $eventType,
            'payment_id' => $payment->id,
            'actor_user_id' => $actorUserId,
            'metadata' => array_merge(['type' => $payment->type], $eventMetadata),
        ]);

        return $payment->fresh();
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
