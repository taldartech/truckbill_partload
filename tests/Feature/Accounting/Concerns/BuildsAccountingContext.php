<?php

namespace Tests\Feature\Accounting\Concerns;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use Spatie\Permission\Models\Permission;

trait BuildsAccountingContext
{
    /**
     * @param  list<string>  $permissions
     * @return array{company: Company, branch: Branch, user: User, owner: User, state: State, city: City, fromStation: Station, toStation: Station}
     */
    protected function accountingContext(array $permissions = [], string $suffix = 'A'): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => "Accounting Co {$suffix}", 'user_id' => $owner->id]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);
        app(EnsureCompanyAccounting::class)->handle($company);

        $country = Country::query()->firstOrCreate(['code' => 'IN'], ['name' => 'India']);
        $state = State::query()->firstOrCreate(['code' => 'RJ'], ['country_id' => $country->id, 'name' => 'Rajasthan']);
        $city = City::query()->firstOrCreate(['name' => 'Udaipur'], ['country_id' => $country->id, 'state_id' => $state->id]);
        $fromStation = Station::create(['name' => "From {$suffix}", 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => "To {$suffix}", 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => "Main {$suffix}",
            'address' => '1 Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Manager',
            'contact_number' => '9000000000',
            'email' => strtolower("branch{$suffix}@example.com"),
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'company' => $company,
            'branch' => $branch,
            'user' => $user,
            'owner' => $owner,
            'state' => $state,
            'city' => $city,
            'fromStation' => $fromStation,
            'toStation' => $toStation,
        ];
    }

    protected function systemAccountId(Company|int $company, string $systemKey): int
    {
        return app(ChartOfAccountsService::class)->systemAccount($company instanceof Company ? $company->id : $company, $systemKey)->id;
    }
}
