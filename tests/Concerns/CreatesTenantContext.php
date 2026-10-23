<?php

namespace Tests\Concerns;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesTenantContext
{
    /**
     * @param  list<string>  $permissions
     * @return array{
     *     owner: User,
     *     company: Company,
     *     branch: Branch,
     *     user: User,
     *     country: Country,
     *     state: State,
     *     city: City,
     *     station: Station,
     *     subscription: CompanySubscription
     * }
     */
    protected function createTenantContext(
        array $permissions = [],
        string $suffix = 'A',
        string $subscriptionStatus = 'active',
        ?string $endsOn = null,
    ): array {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => "Test Co {$suffix}",
            'user_id' => $owner->id,
        ]);

        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => $subscriptionStatus,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => $endsOn ?? now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $country = Country::query()->firstOrCreate(['code' => 'IN'], ['name' => 'India']);
        $state = State::query()->firstOrCreate(
            ['code' => 'RJ'],
            ['country_id' => $country->id, 'name' => 'Rajasthan']
        );
        $city = City::query()->firstOrCreate(
            ['name' => 'Udaipur', 'state_id' => $state->id],
            ['country_id' => $country->id]
        );
        $station = Station::create([
            'name' => "Station {$suffix}",
            'owner_id' => $owner->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => "Branch {$suffix}",
            'address' => '1 Test Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'Manager',
            'contact_number' => '9000000000',
            'email' => strtolower("branch{$suffix}@example.com"),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return compact(
            'owner',
            'company',
            'branch',
            'user',
            'country',
            'state',
            'city',
            'station',
            'subscription'
        );
    }

    protected function giveRole(User $user, string $roleName): Role
    {
        $role = Role::findOrCreate($roleName);

        $user->assignRole($role);

        return $role;
    }
}
