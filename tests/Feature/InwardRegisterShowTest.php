<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InwardRegisterShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_inward_register_show(): void
    {
        $context = $this->createShowContext(['view inward_registers']);

        $response = $this->actingAs($context['user'])
            ->get(route('inward-registers.show', $context['register']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('InwardRegisters/Show')
            ->where('register.id', $context['register']->id)
            ->where('register.inward_register_no', 'IR-SHOW-100')
            ->where('register.from_station.name', 'From Show')
            ->where('register.to_station.name', 'To Show')
            ->where('register.truck.truck_number', 'MH-SHOW-1')
            ->has('register.details', 1)
            ->where('register.details.0.consignor.name', 'Show Consignor')
            ->where('register.details.0.consignee.name', 'Show Consignee')
            ->where('register.details.0.good.name', 'Show Goods')
            ->where('register.details.0.agent.name', 'Show Agent')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_inward_register_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('inward-registers.show', $context['register']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, register: InwardRegister}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
        ]);

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

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $fromStation = Station::create(['name' => 'From Show', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'To Show', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Show Branch',
            'address' => '1 Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH-SHOW-1',
        ]);

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignor',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignee',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $good = Good::create(['owner_id' => $owner->id, 'name' => 'Show Goods']);
        $agent = Agent::create(['company_id' => $company->id, 'name' => 'Show Agent']);

        $register = InwardRegister::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inward_register_no' => 'IR-SHOW-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Show Driver',
            'total_packages' => 5,
            'total_delivery_freight' => 500,
            'total_crossing_freight' => 0,
        ]);

        InwardRegisterDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-1',
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'good_id' => $good->id,
            'no_of_packages' => 5,
            'weight' => 50,
            'freight' => 500,
            'agent_id' => $agent->id,
            'disposition' => 'delivery',
            'status' => 'pending',
        ]);

        return [
            'user' => $user,
            'register' => $register,
        ];
    }
}
