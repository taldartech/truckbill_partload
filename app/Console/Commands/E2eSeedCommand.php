<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Support\RolePermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class E2eSeedCommand extends Command
{
    protected $signature = 'e2e:seed
                            {--email=e2e@truckbill.test : Login email for Playwright}
                            {--password=password : Login password for Playwright}';

    protected $description = 'Seed a full-permission operational tenant for Playwright portal e2e';

    public function handle(EnsureCompanyAccounting $ensureAccounting): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolePermissions::adminPermissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        $owner = User::query()->firstOrCreate(
            ['email' => 'e2e-owner@truckbill.test'],
            [
                'name' => 'E2E Owner',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $company = Company::query()->firstOrCreate(
            ['name' => 'E2E Transport Co'],
            ['user_id' => $owner->id]
        );
        $company->forceFill(['user_id' => $owner->id])->save();

        CompanySubscription::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'status' => 'active',
                'starts_on' => now()->subMonth()->toDateString(),
                'ends_on' => now()->addYear()->toDateString(),
                'included_branches' => 10,
                'users_per_branch' => 25,
                'included_lr_credits' => 10000,
                'included_trucks' => 100,
                'lr_credits_remaining' => 10000,
            ]
        );

        $ensureAccounting->handle($company);

        $country = Country::query()->firstOrCreate(['code' => 'IN'], ['name' => 'India']);
        $state = State::query()->firstOrCreate(
            ['code' => 'RJ'],
            ['country_id' => $country->id, 'name' => 'Rajasthan']
        );
        $city = City::query()->firstOrCreate(
            ['name' => 'Udaipur', 'state_id' => $state->id],
            ['country_id' => $country->id]
        );
        $station = Station::query()->firstOrCreate(
            ['name' => 'E2E Station', 'owner_id' => $owner->id],
            [
                'state_id' => $state->id,
                'city_id' => $city->id,
                'country_id' => $country->id,
            ]
        );

        Station::query()->firstOrCreate(
            ['name' => 'E2E Destination', 'owner_id' => $owner->id],
            [
                'state_id' => $state->id,
                'city_id' => $city->id,
                'country_id' => $country->id,
            ]
        );

        $branch = Branch::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'E2E Branch'],
            [
                'address' => '1 E2E Street',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'pin_code' => '313001',
                'station_id' => $station->id,
                'contact_person' => 'E2E Manager',
                'contact_number' => '9000000001',
                'email' => 'e2e-branch@truckbill.test',
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'E2E Operator',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]
        );

        $user->syncPermissions(RolePermissions::adminPermissions());

        $this->info('E2E tenant ready.');
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");
        $this->line("Company: {$company->name} (#{$company->id})");
        $this->line("Branch: {$branch->name} (#{$branch->id})");

        return self::SUCCESS;
    }
}
