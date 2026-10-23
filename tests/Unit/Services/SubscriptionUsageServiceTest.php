<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SubscriptionUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class SubscriptionUsageServiceTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    private SubscriptionUsageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SubscriptionUsageService;
    }

    public function test_usage_for_company_counts_branches_and_users(): void
    {
        $context = $this->createTenantContext();
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $second, 3);

        $usage = $this->service->usageForCompany($context['company']->fresh());

        $this->assertSame(2, $usage['branches']);
        $this->assertSame(3, $usage['max_users_in_any_branch']);
        $this->assertSame(4, $usage['total_users']);
        $this->assertSame(0, $usage['trucks']);
        $this->assertSame(1000, $usage['lr_credits_remaining']);
    }

    public function test_usage_ignores_users_without_branch_for_busiest_branch(): void
    {
        $context = $this->createTenantContext();
        User::factory()->count(4)->create([
            'company_id' => $context['company']->id,
            'branch_id' => null,
        ]);

        $usage = $this->service->usageForCompany($context['company']->fresh());

        $this->assertSame(1, $usage['max_users_in_any_branch']);
        $this->assertSame(5, $usage['total_users']);
    }

    public function test_usage_for_company_without_subscription_or_data_is_zeroed(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->delete();
        $context['branch']->forceDelete();
        $context['user']->delete();

        $usage = $this->service->usageForCompany($context['company']->fresh());

        $this->assertSame(0, $usage['branches']);
        $this->assertSame(0, $usage['max_users_in_any_branch']);
        $this->assertSame(0, $usage['total_users']);
        $this->assertSame(0, $usage['lr_credits_remaining']);
    }

    public function test_usage_is_scoped_to_the_company(): void
    {
        $context = $this->createTenantContext();
        $other = $this->createTenantContext([], 'B');

        $usage = $this->service->usageForCompany($context['company']->fresh());

        $this->assertSame(1, $usage['branches']);
        $this->assertSame(1, $usage['total_users']);
        $this->assertNotSame($context['company']->id, $other['company']->id);
    }

    public function test_overflow_users_used_sums_users_above_per_branch_limit(): void
    {
        $context = $this->createTenantContext();
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 3);
        $this->createUsersInBranch($context['company'], $second, 5);

        // Branch A has 4 users, branch B has 5 users.
        $this->assertSame(0, $this->service->overflowUsersUsed($context['company'], 5));
        $this->assertSame(5, $this->service->overflowUsersUsed($context['company'], 2));
        $this->assertSame(9, $this->service->overflowUsersUsed($context['company'], 0));
    }

    public function test_overflow_users_used_can_exclude_a_user(): void
    {
        $context = $this->createTenantContext();
        $this->createUsersInBranch($context['company'], $context['branch'], 2);

        $this->assertSame(1, $this->service->overflowUsersUsed($context['company'], 2));
        $this->assertSame(0, $this->service->overflowUsersUsed($context['company'], 2, $context['user']->id));
    }

    public function test_minimum_renewal_addons_is_zero_when_within_catalog_limits(): void
    {
        $context = $this->createTenantContext();

        $minimum = $this->service->minimumRenewalAddons($context['company'], $this->catalogPayload()['catalog']);

        $this->assertSame(['addon_branches' => 0, 'addon_users' => 0], $minimum);
    }

    public function test_minimum_renewal_addons_requires_branch_addons_for_extra_branches(): void
    {
        $context = $this->createTenantContext();
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Third Branch');
        $this->createExtraBranch($context['company'], $context['branch'], 'Fourth Branch');

        $minimum = $this->service->minimumRenewalAddons($context['company'], $this->catalogPayload()['catalog']);

        $this->assertSame(2, $minimum['addon_branches']);
        $this->assertSame(0, $minimum['addon_users']);
    }

    public function test_minimum_renewal_addons_requires_user_addons_for_overflow_and_total(): void
    {
        $context = $this->createTenantContext();
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 6);
        $this->createUsersInBranch($context['company'], $second, 1);

        // Branch A: 7 users with a catalog limit of 5 per branch => 2 overflow users.
        $minimum = $this->service->minimumRenewalAddons($context['company'], $this->catalogPayload()['catalog']);

        $this->assertSame(0, $minimum['addon_branches']);
        $this->assertSame(2, $minimum['addon_users']);
    }

    public function test_minimum_renewal_addons_uses_total_users_when_larger_than_overflow(): void
    {
        $context = $this->createTenantContext();
        $this->createUsersInBranch($context['company'], $context['branch'], 2);

        // 3 users total, but only 1 branch slot * 1 user per branch with no extra branches.
        $catalog = $this->catalogPayload(['included_branches' => 1, 'users_per_branch' => 1])['catalog'];
        $minimum = $this->service->minimumRenewalAddons($context['company'], $catalog);

        $this->assertSame(0, $minimum['addon_branches']);
        $this->assertSame(2, $minimum['addon_users']);
    }
}
