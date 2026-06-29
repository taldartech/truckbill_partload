<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Support\SubscriptionExpiredResponse;
use Illuminate\Validation\ValidationException;

class SubscriptionGuard
{
    public function __construct(
        private readonly SubscriptionUsageService $usageService,
    ) {}
    public function assertPlanActive(?CompanySubscription $subscription): void
    {
        if ($subscription === null || ! $subscription->isActive()) {
            SubscriptionExpiredResponse::abortUnlessActive(request());
        }
    }

    public function assertCanCreateBranch(Company $company): void
    {
        $subscription = $company->subscription;
        $this->assertPlanActive($subscription);

        $count = Branch::query()->where('company_id', $company->id)->count();
        $max = $subscription->maxBranches();

        if ($count >= $max) {
            throw ValidationException::withMessages([
                'company_id' => "Branch limit reached ({$count}/{$max}). "
                    .'Purchase a branch add-on from Subscription → Add-ons to create more branches.',
            ]);
        }
    }

    public function assertCanAssignUserToBranch(Company $company, int $branchId, ?int $excludingUserId = null): void
    {
        $subscription = $company->subscription;
        $this->assertPlanActive($subscription);

        $perBranch = $subscription->maxUsersInBranch();
        $addonUsers = (int) $subscription->addon_users;
        $totalUsers = $this->countCompanyUsers($company, $excludingUserId);
        $usersInBranch = $this->countUsersInBranch($company, $branchId, $excludingUserId);
        $effectiveMax = $this->effectiveUserCapacity($company, $subscription);

        if ($totalUsers >= $effectiveMax) {
            throw ValidationException::withMessages([
                'branch_id' => 'Company user limit reached for your subscription.',
            ]);
        }

        if ($usersInBranch < $perBranch) {
            return;
        }

        $overflowUsed = $this->usageService->overflowUsersUsed($company, $perBranch, $excludingUserId);

        if ($overflowUsed >= $addonUsers) {
            throw ValidationException::withMessages([
                'branch_id' => "This branch already has the maximum number of users ({$perBranch} included"
                    .($addonUsers > 0 ? " + {$addonUsers} add-on" : '')
                    .'). Assign the user to another branch or purchase more user add-ons.',
            ]);
        }
    }

    public function assertHasLrCredits(Company $company, int $required = 1): void
    {
        $subscription = $company->subscription;
        $this->assertPlanActive($subscription);

        if (($subscription->lr_credits_remaining ?? 0) < $required) {
            abort(422, 'Insufficient LR credits.');
        }
    }

    public function decrementLrCredits(Company $company, int $amount = 1): void
    {
        $subscription = $company->subscription;
        if ($subscription === null) {
            return;
        }

        $subscription->decrement('lr_credits_remaining', $amount);
    }

    private function effectiveUserCapacity(Company $company, CompanySubscription $subscription): int
    {
        $branchCount = Branch::query()->where('company_id', $company->id)->count();
        $perBranch = $subscription->maxUsersInBranch();

        return min(
            $subscription->maxCompanyUsers(),
            ($branchCount * $perBranch) + (int) $subscription->addon_users,
        );
    }

    private function countCompanyUsers(Company $company, ?int $excludingUserId = null): int
    {
        return User::query()
            ->where('company_id', $company->id)
            ->when($excludingUserId !== null, fn ($query) => $query->where('id', '!=', $excludingUserId))
            ->count();
    }

    private function countUsersInBranch(Company $company, int $branchId, ?int $excludingUserId = null): int
    {
        return User::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branchId)
            ->when($excludingUserId !== null, fn ($query) => $query->where('id', '!=', $excludingUserId))
            ->count();
    }
}
