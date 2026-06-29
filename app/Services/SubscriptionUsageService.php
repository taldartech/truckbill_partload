<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;

class SubscriptionUsageService
{
    public function usageForCompany(Company $company): array
    {
        $branchCount = Branch::query()
            ->where('company_id', $company->id)
            ->count();

        $usersPerBranch = User::query()
            ->where('company_id', $company->id)
            ->whereNotNull('branch_id')
            ->selectRaw('branch_id, count(*) as cnt')
            ->groupBy('branch_id')
            ->pluck('cnt');

        return [
            'branches' => $branchCount,
            'max_users_in_any_branch' => (int) ($usersPerBranch->max() ?? 0),
            'total_users' => User::query()->where('company_id', $company->id)->count(),
            'trucks' => 0,
            'lr_credits_remaining' => $company->subscription?->lr_credits_remaining ?? 0,
        ];
    }

    public function overflowUsersUsed(Company $company, int $perBranch, ?int $excludingUserId = null): int
    {
        return (int) User::query()
            ->where('company_id', $company->id)
            ->whereNotNull('branch_id')
            ->when($excludingUserId !== null, fn ($query) => $query->where('id', '!=', $excludingUserId))
            ->selectRaw('branch_id, count(*) as cnt')
            ->groupBy('branch_id')
            ->pluck('cnt')
            ->sum(fn ($count) => max(0, (int) $count - $perBranch));
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array{addon_branches: int, addon_users: int}
     */
    public function minimumRenewalAddons(Company $company, array $catalog): array
    {
        $usage = $this->usageForCompany($company);
        $perBranch = (int) $catalog['users_per_branch'];
        $includedBranches = (int) $catalog['included_branches'];

        $minBranches = max(0, $usage['branches'] - $includedBranches);
        $branchSlots = $includedBranches + $minBranches;
        $minUsersForTotal = max(0, $usage['total_users'] - ($branchSlots * $perBranch));
        $minUsersForOverflow = $this->overflowUsersUsed($company, $perBranch);

        return [
            'addon_branches' => $minBranches,
            'addon_users' => max($minUsersForTotal, $minUsersForOverflow),
        ];
    }
}
