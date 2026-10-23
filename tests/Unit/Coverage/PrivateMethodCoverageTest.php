<?php

namespace Tests\Unit\Coverage;

use App\Enums\UserRole;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StationController;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateMethodCoverageTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_branch_controller_authorize_helpers(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        Role::findOrCreate(UserRole::MasterAdmin->value);

        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        Company::create(['name' => 'Owned Co', 'user_id' => $owner->id]);

        $other = User::factory()->create();
        $foreignCompany = Company::create(['name' => 'Other Co', 'user_id' => $other->id]);
        $foreignBranch = Branch::withoutGlobalScopes()->create([
            'company_id' => $foreignCompany->id,
            'name' => 'Foreign',
            'address' => 'x',
            'pin_code' => '313001',
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'x@example.com',
        ]);

        $this->actingAs($owner)->withSession(['active_company_id' => null]);

        $controller = app(BranchController::class);
        $authorizeCompany = new ReflectionMethod($controller, 'authorizeCompanyId');

        try {
            $authorizeCompany->invoke($controller, null);
            $this->fail('Expected 422');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        try {
            $authorizeCompany->invoke($controller, $foreignCompany->id);
            $this->fail('Expected 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $authorizeBranch = new ReflectionMethod($controller, 'authorizeBranch');
        try {
            $authorizeBranch->invoke($controller, $foreignBranch);
            $this->fail('Expected 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $ensure = new ReflectionMethod($controller, 'ensureUniqueHeadOffice');
        $ensure->invoke($controller, ['is_head_office' => false], null);
        $ensure->invoke($controller, ['is_head_office' => true], null);

        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);
        $this->actingAs($master);
        $available = new ReflectionMethod($controller, 'availableCompanies');
        $this->assertNotEmpty($available->invoke($controller));

        $this->actingAs($owner)->withSession(['active_company_id' => null]);
        $asSuper = $available->invoke($controller);
        $this->assertInstanceOf(Collection::class, $asSuper);
    }

    public function test_station_sync_default_agent_without_company_is_noop(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value);
        $owner = User::factory()->create();
        $owner->assignRole(UserRole::SuperAdmin->value);
        $station = Station::create([
            'owner_id' => $owner->id,
            'name' => 'Sync Station',
        ]);

        $this->actingAs($owner)->withSession([]);

        $controller = app(StationController::class);
        $method = new ReflectionMethod($controller, 'syncDefaultAgent');
        $method->invoke($controller, $station, 1);
        $this->assertTrue(true);
    }

    public function test_station_sync_default_agent_rejects_foreign_agent(): void
    {
        $context = $this->createTenantContext(['edit stations']);
        $station = Station::create([
            'owner_id' => $context['owner']->id,
            'name' => 'Sync Station 2',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);
        $foreignCompany = Company::create(['name' => 'Agent Co', 'user_id' => $context['owner']->id]);
        $foreign = Agent::create(['company_id' => $foreignCompany->id, 'name' => 'Wrong Co Agent']);

        $this->actingAs($context['user']);

        $controller = app(StationController::class);
        $method = new ReflectionMethod($controller, 'syncDefaultAgent');

        $this->expectException(ValidationException::class);
        $method->invoke($controller, $station, $foreign->id);
    }
}
