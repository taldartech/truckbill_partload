<?php

namespace Tests\Unit\Services;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\User;
use App\Services\SubscriptionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesTenantContext;
use Tests\Concerns\SubscriptionTestHelpers;
use Tests\TestCase;

class SubscriptionGuardTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;
    use SubscriptionTestHelpers;

    private SubscriptionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andThrow(new SubscriptionCatalogUnavailableException);

        $this->guard = app(SubscriptionGuard::class);
    }

    private function expectedRedirectMessage(HttpResponseException $exception): string
    {
        $response = $exception->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);

        return (string) $response->getSession()?->get('error');
    }

    public function test_assert_can_mutate_allows_active_subscription(): void
    {
        $context = $this->createTenantContext();

        $this->guard->assertCanMutate($context['company']);

        $this->addToAssertionCount(1);
    }

    public function test_assert_can_mutate_redirects_locked_company_to_dashboard(): void
    {
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        try {
            $this->guard->assertCanMutate($context['company']->fresh());
            $this->fail('Expected HttpResponseException.');
        } catch (HttpResponseException $exception) {
            $this->assertSame(route('dashboard'), $exception->getResponse()->getTargetUrl());
            $this->assertStringContainsString('active subscription is required', $this->expectedRedirectMessage($exception));
        }
    }

    public function test_assert_can_mutate_blocks_writes_during_grace_period(): void
    {
        $client = $this->mockAdminApi();
        $client->shouldReceive('getCatalog')->andReturn($this->catalogPayload([], ['grace_period_days' => 7]));
        $guard = app(SubscriptionGuard::class);
        $context = $this->createTenantContext([], 'A', 'active', now()->subDays(2)->toDateString());

        try {
            $guard->assertCanMutate($context['company']->fresh());
            $this->fail('Expected HttpResponseException.');
        } catch (HttpResponseException $exception) {
            $this->assertStringContainsString('read-only grace period', $this->expectedRedirectMessage($exception));
        }
    }

    public function test_assert_plan_active_without_subscription_redirects_locked(): void
    {
        $this->expectException(HttpResponseException::class);

        $this->guard->assertPlanActive(null);
    }

    public function test_assert_plan_active_passes_for_active_subscription(): void
    {
        $context = $this->createTenantContext();

        $this->guard->assertPlanActive($context['subscription']);

        $this->addToAssertionCount(1);
    }

    public function test_assert_plan_active_blocks_expired_subscription(): void
    {
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        $this->expectException(HttpResponseException::class);

        $this->guard->assertPlanActive($context['subscription']->fresh());
    }

    public function test_assert_can_create_branch_requires_a_subscription(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->delete();

        try {
            $this->guard->assertCanCreateBranch($context['company']->fresh());
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('no active subscription', $exception->errors()['company_id'][0]);
        }
    }

    public function test_assert_can_create_branch_allows_when_under_limit(): void
    {
        $context = $this->createTenantContext();

        $this->guard->assertCanCreateBranch($context['company']);

        $this->addToAssertionCount(1);
    }

    public function test_assert_can_create_branch_rejects_when_limit_reached(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 2]);
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');

        try {
            $this->guard->assertCanCreateBranch($context['company']->fresh());
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Branch limit reached (2/2)', $exception->errors()['company_id'][0]);
        }
    }

    public function test_branch_addons_raise_the_branch_limit(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 1, 'addon_branches' => 1]);

        $this->guard->assertCanCreateBranch($context['company']->fresh());

        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');

        $this->expectException(ValidationException::class);
        $this->guard->assertCanCreateBranch($context['company']->fresh());
    }

    public function test_assert_can_create_branch_ignores_soft_deleted_branches(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 2]);
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $second->delete();

        $this->guard->assertCanCreateBranch($context['company']->fresh());

        $this->addToAssertionCount(1);
    }

    public function test_assert_can_create_branch_blocks_expired_subscription(): void
    {
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        $this->expectException(HttpResponseException::class);

        $this->guard->assertCanCreateBranch($context['company']->fresh());
    }

    public function test_assign_user_rejects_when_company_user_limit_reached(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 1, 'users_per_branch' => 2]);
        $this->createUsersInBranch($context['company'], $context['branch'], 1);

        try {
            $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('Company user limit reached for your subscription.', $exception->errors()['branch_id'][0]);
        }
    }

    public function test_assign_user_allows_when_branch_has_room(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 1, 'users_per_branch' => 3]);

        $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);

        $this->addToAssertionCount(1);
    }

    public function test_assign_user_can_exclude_the_user_being_moved(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 1, 'users_per_branch' => 1]);

        // The only user fills the single seat; re-saving that same user must still pass.
        $this->guard->assertCanAssignUserToBranch(
            $context['company']->fresh(),
            $context['branch']->id,
            $context['user']->id,
        );

        $this->addToAssertionCount(1);
    }

    public function test_assign_user_rejects_full_branch_without_addons(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 2, 'users_per_branch' => 2]);
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 1);

        try {
            $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['branch_id'][0];
            $this->assertStringContainsString('maximum number of users (2 included)', $message);
            $this->assertStringNotContainsString('add-on)', $message);
        }
    }

    public function test_assign_user_rejects_full_branch_when_addon_users_are_consumed(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update([
            'included_branches' => 2,
            'users_per_branch' => 2,
            'addon_users' => 1,
        ]);
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 2);

        // Branch A holds 3 users: 2 included + 1 overflow, consuming the single add-on seat.
        try {
            $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'maximum number of users (2 included + 1 add-on)',
                $exception->errors()['branch_id'][0]
            );
        }
    }

    public function test_assign_user_allows_overflow_into_available_addon_seat(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update([
            'included_branches' => 2,
            'users_per_branch' => 2,
            'addon_users' => 1,
        ]);
        $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 1);

        // Branch A holds 2 users (full) but the add-on seat is still unused.
        $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);

        $this->addToAssertionCount(1);
    }

    public function test_assign_user_allows_other_branch_even_when_one_branch_is_full(): void
    {
        $context = $this->createTenantContext();
        $context['subscription']->update(['included_branches' => 2, 'users_per_branch' => 2]);
        $second = $this->createExtraBranch($context['company'], $context['branch'], 'Second Branch');
        $this->createUsersInBranch($context['company'], $context['branch'], 1);

        $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $second->id);

        $this->addToAssertionCount(1);
    }

    public function test_assign_user_blocks_expired_subscription(): void
    {
        $context = $this->createTenantContext([], 'A', 'expired', now()->subDays(10)->toDateString());

        $this->expectException(HttpResponseException::class);

        $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);
    }

    public function test_user_counts_are_scoped_to_the_company(): void
    {
        $context = $this->createTenantContext();
        $other = $this->createTenantContext([], 'B');
        $context['subscription']->update(['included_branches' => 1, 'users_per_branch' => 2]);
        User::factory()->count(3)->create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
        ]);

        $this->guard->assertCanAssignUserToBranch($context['company']->fresh(), $context['branch']->id);

        $this->addToAssertionCount(1);
    }
}
