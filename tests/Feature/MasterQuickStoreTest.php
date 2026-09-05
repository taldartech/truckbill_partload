<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MasterQuickStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_quick_store_creates_country(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/countries/quick', [
            'name' => 'Testland',
            'code' => 'TL',
        ]);

        $response->assertCreated()
            ->assertJsonPath('country.name', 'Testland')
            ->assertJsonPath('country.code', 'TL');

        $this->assertDatabaseHas('countries', [
            'name' => 'Testland',
            'code' => 'TL',
        ]);
    }

    public function test_country_quick_store_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/countries/quick', [
            'name' => '',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_state_quick_store_creates_state_for_country(): void
    {
        $user = User::factory()->create();
        $country = Country::create([
            'name' => 'India',
            'code' => 'IN',
        ]);

        $response = $this->actingAs($user)->postJson('/states/quick', [
            'country_id' => $country->id,
            'name' => 'Demo State',
            'code' => 'DS',
        ]);

        $response->assertCreated()
            ->assertJsonPath('state.name', 'Demo State')
            ->assertJsonPath('state.country_id', $country->id);

        $this->assertDatabaseHas('states', [
            'name' => 'Demo State',
            'country_id' => $country->id,
        ]);
    }

    public function test_city_quick_store_sets_country_from_state(): void
    {
        $user = User::factory()->create();
        $country = Country::create([
            'name' => 'India',
            'code' => 'IN',
        ]);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'code' => 'MH',
        ]);

        $response = $this->actingAs($user)->postJson('/cities/quick', [
            'state_id' => $state->id,
            'name' => 'Demo City',
        ]);

        $response->assertCreated()
            ->assertJsonPath('city.name', 'Demo City')
            ->assertJsonPath('city.state_id', $state->id)
            ->assertJsonPath('city.country_id', $country->id);
    }

    public function test_good_quick_store_requires_create_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/goods/quick', [
            'name' => 'Cartons',
        ]);

        $response->assertForbidden();
    }

    public function test_truck_owner_quick_store_creates_truck_owner(): void
    {
        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Quick Co',
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

        Permission::findOrCreate('create truck_owners');

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->givePermissionTo('create truck_owners');

        $response = $this->actingAs($user)->postJson('/truck-owners/quick', [
            'name' => 'Quick Truck Owner',
            'address' => '1 Quick Street',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
            'email' => 'quick-owner@example.com',
            'gst_number' => '27AAAAA0000A1Z5',
        ]);

        $response->assertCreated()
            ->assertJsonPath('truck_owner.name', 'Quick Truck Owner');

        $this->assertDatabaseHas('truck_owners', [
            'company_id' => $company->id,
            'name' => 'Quick Truck Owner',
            'mobile' => '9876543210',
        ]);
    }
}
