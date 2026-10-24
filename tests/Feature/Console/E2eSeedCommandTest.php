<?php

namespace Tests\Feature\Console;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Station;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class E2eSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_e2e_seed_creates_operational_tenant_and_is_idempotent(): void
    {
        $email = 'e2e-coverage@truckbill.test';
        $password = 'secret-pass';

        $this->artisan('e2e:seed', [
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutputToContain('E2E tenant ready.')
            ->expectsOutputToContain("Email: {$email}")
            ->expectsOutputToContain("Password: {$password}")
            ->expectsOutputToContain('Company: E2E Transport Co')
            ->expectsOutputToContain('Branch: E2E Branch')
            ->assertSuccessful();

        $owner = User::query()->where('email', 'e2e-owner@truckbill.test')->first();
        $this->assertNotNull($owner);

        $company = Company::query()->where('name', 'E2E Transport Co')->first();
        $this->assertNotNull($company);
        $this->assertSame($owner->id, $company->user_id);

        $subscription = CompanySubscription::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('active', $subscription->status);

        $this->assertSame(1, Station::query()->where('name', 'E2E Station')->where('owner_id', $owner->id)->count());
        $this->assertSame(1, Station::query()->where('name', 'E2E Destination')->where('owner_id', $owner->id)->count());

        $branch = Branch::query()->where('company_id', $company->id)->where('name', 'E2E Branch')->first();
        $this->assertNotNull($branch);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('E2E Operator', $user->name);
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertEqualsCanonicalizing(
            RolePermissions::adminPermissions(),
            $user->getPermissionNames()->all()
        );

        $companyCount = Company::query()->where('name', 'E2E Transport Co')->count();
        $userCount = User::query()->where('email', $email)->count();

        $this->artisan('e2e:seed', [
            '--email' => $email,
            '--password' => $password,
        ])->assertSuccessful();

        $this->assertSame($companyCount, Company::query()->where('name', 'E2E Transport Co')->count());
        $this->assertSame($userCount, User::query()->where('email', $email)->count());
        $this->assertSame(1, Branch::query()->where('company_id', $company->id)->where('name', 'E2E Branch')->count());
    }
}
