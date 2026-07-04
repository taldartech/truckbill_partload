<?php

namespace App\Services;

use App\Enums\SubscriptionAccessMode;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class SubscriptionAccessService
{
    /** @var array<string, mixed> */
    private const DEFAULT_SETTINGS = [
        'grace_period_days' => 0,
        'gst_rate_percent' => 18.0,
    ];

    public function __construct(
        private readonly AdminApiClient $adminApi,
        private readonly SubscriptionCheckoutService $checkoutService,
    ) {}

    public function resolveForCompany(Company $company): SubscriptionAccessMode
    {
        $this->checkoutService->applyUpcomingForCompany($company->id);
        $company->load('subscription');

        $subscription = $company->subscription;

        if ($subscription === null) {
            return SubscriptionAccessMode::Locked;
        }

        if ($subscription->isActive()) {
            return SubscriptionAccessMode::Full;
        }

        $graceDays = (int) ($this->cachedSettings()['grace_period_days'] ?? 0);
        $graceEnd = $subscription->ends_on->copy()->addDays($graceDays)->toDateString();

        if (now()->toDateString() <= $graceEnd) {
            return SubscriptionAccessMode::GraceReadOnly;
        }

        return SubscriptionAccessMode::Locked;
    }

    /** @return array<string, mixed> */
    public function accessPayloadForCompany(Company $company): array
    {
        $mode = $this->resolveForCompany($company);
        $subscription = $company->subscription;
        $graceEndsAt = null;

        if ($mode === SubscriptionAccessMode::GraceReadOnly && $subscription !== null) {
            $graceDays = (int) ($this->cachedSettings()['grace_period_days'] ?? 0);
            $graceEndsAt = $subscription->ends_on->copy()->addDays($graceDays)->toDateString();
        }

        return [
            'access_mode' => $mode->value,
            'can_write' => $mode === SubscriptionAccessMode::Full,
            'can_export' => $mode === SubscriptionAccessMode::Full,
            'can_print' => $mode === SubscriptionAccessMode::Full,
            'grace_ends_at' => $graceEndsAt,
            'is_in_grace_period' => $mode === SubscriptionAccessMode::GraceReadOnly,
        ];
    }

    /** @return array<string, mixed> */
    public function cachedSettings(): array
    {
        return Cache::remember('subscription.settings', now()->addDay(), function (): array {
            try {
                $payload = $this->adminApi->getCatalog();

                return array_merge(self::DEFAULT_SETTINGS, $payload['settings'] ?? []);
            } catch (\Throwable) {
                return self::DEFAULT_SETTINGS;
            }
        });
    }
}
