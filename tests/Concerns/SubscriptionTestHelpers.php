<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\AdminApiClient;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;

trait SubscriptionTestHelpers
{
    /**
     * @return array{catalog: array<string, int|float>, gst_rate_percent: float, settings: array<string, int>}
     */
    protected function catalogPayload(array $catalogOverrides = [], array $settings = []): array
    {
        return [
            'catalog' => array_merge([
                'base_price' => 10000,
                'included_branches' => 2,
                'users_per_branch' => 5,
                'addon_branch_price' => 1000,
                'addon_user_price' => 500,
            ], $catalogOverrides),
            'gst_rate_percent' => 18.0,
            'settings' => array_merge(['grace_period_days' => 0], $settings),
        ];
    }

    /**
     * Bind a Mockery fake of the admin API client so no real HTTP call is made.
     */
    protected function mockAdminApi(?array $payload = null): MockInterface
    {
        $client = Mockery::mock(AdminApiClient::class);

        if ($payload !== null) {
            $client->shouldReceive('getCatalog')->andReturn($payload);
        }

        $this->app->instance(AdminApiClient::class, $client);

        return $client;
    }

    /**
     * The 2026_06_16 migration drops upcoming_subscriptions.starts_on/ends_on but runs before the
     * 2026_06_19 migration that creates the table, so fresh databases keep the NOT NULL columns.
     * Align the test schema with the intended (production) shape so the app code can be exercised.
     */
    protected function useUpcomingSubscriptionsWithoutDateColumns(): void
    {
        Schema::table('upcoming_subscriptions', function ($table): void {
            foreach (['starts_on', 'ends_on'] as $column) {
                if (Schema::hasColumn('upcoming_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function makeSuperAdmin(User $user): User
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }

    protected function createExtraBranch(Company $company, Branch $template, string $name): Branch
    {
        return Branch::create([
            'company_id' => $company->id,
            'name' => $name,
            'address' => '2 Test Street',
            'country_id' => $template->country_id,
            'state_id' => $template->state_id,
            'city_id' => $template->city_id,
            'pin_code' => '313002',
            'station_id' => $template->station_id,
            'contact_person' => 'Manager',
            'contact_number' => '9000000001',
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
        ]);
    }

    protected function createUsersInBranch(Company $company, Branch $branch, int $count): void
    {
        User::factory()->count($count)->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
    }
}
