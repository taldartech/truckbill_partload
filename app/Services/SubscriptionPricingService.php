<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Support\Money;
use App\Support\SubscriptionProration;
use Carbon\Carbon;

class SubscriptionPricingService
{
    public function __construct(
        private readonly AdminApiClient $adminApi,
        private readonly SubscriptionUsageService $usageService
    ) {}

    /**
     * @param  array<string, mixed>  $catalog
     * @return array{addon_branches: int, addon_users: int}
     */
    public function minimumRenewalAddons(Company $company, array $catalog): array
    {
        return $this->usageService->minimumRenewalAddons($company, $catalog);
    }

    public function quote(Company $company, array $input): array
    {
        $catalogPayload = $this->adminApi->getCatalog();
        $catalog = $catalogPayload['catalog'];
        $gstRate = (float) $catalogPayload['gst_rate_percent'];

        $purchaseType = $input['purchase_type'];
        $addonBranches = (int) ($input['addon_branches'] ?? 0);
        $addonUsers = (int) ($input['addon_users'] ?? 0);
        $addonTrucks = (int) ($input['addon_trucks'] ?? 0);
        $addonLrCredits = (int) ($input['addon_lr_credits'] ?? 0);

        $subscription = $company->subscription;
        $usage = $this->usageService->usageForCompany($company);
        $minimumAddons = $this->usageService->minimumRenewalAddons($company, $catalog);
        $blockingErrors = [];
        $lineItems = [];
        $remainingDays = $purchaseType === 'addon' && $subscription
            ? $subscription->remainingDaysInclusive()
            : null;

        if ($purchaseType === 'renewal') {
            $lineItems[] = [
                'label' => 'Annual base plan',
                'amount' => (float) $catalog['base_price'],
            ];
            $blockingErrors = array_merge(
                $blockingErrors,
                $this->renewalCapacityErrors($catalog, $usage, $addonBranches, $addonUsers, $company),
            );
        } elseif (! $subscription?->isActive()) {
            $blockingErrors[] = 'Add-ons are only available for active subscriptions.';
        }

        if ($addonBranches > 0) {
            $lineItems[] = $this->addOnLineItem(
                $purchaseType,
                $remainingDays,
                $addonBranches,
                (float) $catalog['addon_branch_price'],
                'Branch add-ons',
            );
        }
        if ($addonUsers > 0) {
            $lineItems[] = $this->addOnLineItem(
                $purchaseType,
                $remainingDays,
                $addonUsers,
                (float) $catalog['addon_user_price'],
                'User add-ons',
            );
        }

        $subtotal = Money::round(collect($lineItems)->sum('amount'));
        $promoDiscount = 0.0;
        $promoSnapshot = null;

        if (! empty($input['promo_code']) && ($input['promo_validated'] ?? false)) {
            $promoResult = $this->adminApi->validatePromo($input['promo_code']);
            if ($promoResult['valid'] ?? false) {
                $promo = $promoResult['promo'];
                $promoSnapshot = $promo;
                $promoDiscount = $promo['discount_type'] === 'percentage'
                    ? Money::round($subtotal * ((float) $promo['discount_value'] / 100))
                    : Money::round(min($subtotal, (float) $promo['discount_value']));
                $lineItems[] = ['label' => 'Promo discount', 'amount' => -$promoDiscount];
            }
        }

        $taxable = Money::round(max(0, $subtotal - $promoDiscount));
        $gstAmount = Money::round($taxable * ($gstRate / 100));
        $total = Money::round($taxable + $gstAmount);

        return [
            'purchase_type' => $purchaseType,
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'promo_discount' => $promoDiscount,
            'gst_amount' => $gstAmount,
            'total_amount' => $total,
            'gst_rate_percent' => $gstRate,
            'current_usage' => $usage,
            'minimum_addons_required' => $minimumAddons,
            'can_checkout' => empty($blockingErrors) && $total > 0,
            'blocking_errors' => $blockingErrors,
            'catalog_snapshot' => $catalog,
            'promo_snapshot' => $promoSnapshot,
            'quantities' => [
                'addon_branches' => $addonBranches,
                'addon_users' => $addonUsers,
                'addon_trucks' => $addonTrucks,
                'addon_lr_credits' => $addonLrCredits,
            ],
            'proration' => $remainingDays !== null ? [
                'remaining_days' => $remainingDays,
                'annual_days' => SubscriptionProration::ANNUAL_DAYS,
            ] : null,
        ];
    }

    /**
     * @return array{label: string, amount: float}
     */
    private function addOnLineItem(
        string $purchaseType,
        ?int $remainingDays,
        int $quantity,
        float $annualUnitPrice,
        string $labelPrefix,
    ): array {
        $annualAmount = Money::round($quantity * $annualUnitPrice);

        if ($purchaseType === 'addon' && $remainingDays !== null) {
            $amount = SubscriptionProration::prorateAnnualAmount($annualAmount, $remainingDays);
            $label = "{$labelPrefix} ({$quantity}, {$remainingDays} days prorated)";

            return ['label' => $label, 'amount' => $amount];
        }

        return ['label' => "{$labelPrefix} ({$quantity})", 'amount' => $annualAmount];
    }

    public function createTrialSubscription(Company $company): CompanySubscription
    {
        $catalog = $this->trialCatalogDefaults();

        try {
            $catalogPayload = $this->adminApi->getCatalog();
            $catalog = array_merge($catalog, $catalogPayload['catalog'] ?? []);
        } catch (\Throwable) {
            // Fall back to local defaults when the admin catalog API is unavailable.
        }

        $startsOn = now()->toDateString();
        $endsOn = Carbon::parse($startsOn)->addDays(15)->toDateString();

        return CompanySubscription::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'status' => 'trial',
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'trial_ends_on' => $endsOn,
                'included_branches' => (int) ($catalog['included_branches'] ?? 2),
                'users_per_branch' => (int) ($catalog['users_per_branch'] ?? 5),
                'included_lr_credits' => 0,
                'included_trucks' => 0,
                'lr_credits_remaining' => 0,
            ],
        );
    }

    /**
     * @return array{included_branches: int, users_per_branch: int}
     */
    private function trialCatalogDefaults(): array
    {
        return [
            'included_branches' => 2,
            'users_per_branch' => 5,
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @param  array<string, int>  $usage
     * @return list<string>
     */
    private function renewalCapacityErrors(
        array $catalog,
        array $usage,
        int $addonBranches,
        int $addonUsers,
        Company $company,
    ): array {
        $errors = [];
        $includedBranches = (int) $catalog['included_branches'];
        $perBranch = (int) $catalog['users_per_branch'];
        $branchSlots = $includedBranches + $addonBranches;

        if ($branchSlots < $usage['branches']) {
            $required = $usage['branches'] - $includedBranches;
            $errors[] = "Add at least {$required} branch add-on(s), or delete extra branches before renewing.";
        }

        $maxTotal = ($branchSlots * $perBranch) + $addonUsers;
        if ($maxTotal < $usage['total_users']) {
            $errors[] = 'Add more user add-ons, or remove users before renewing.';
        }

        $overflowRequired = $this->usageService->overflowUsersUsed($company, $perBranch);
        if ($addonUsers < $overflowRequired) {
            $errors[] = "Add at least {$overflowRequired} user add-on(s) for branches over the per-branch limit, or reassign or remove users.";
        }

        return $errors;
    }
}
