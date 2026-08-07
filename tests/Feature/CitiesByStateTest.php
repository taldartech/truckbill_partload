<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CitiesByStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_cities_by_state_is_a_setup_route(): void
    {
        $this->assertTrue(app(TenantContext::class)->isSetupRoute('cities.by-state'));
        $this->assertTrue(app(TenantContext::class)->isSetupRoute('countries.quick-store'));
        $this->assertTrue(app(TenantContext::class)->isSetupRoute('states.quick-form-data'));
    }

    public function test_super_admin_can_fetch_cities_by_state_without_branch_context(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MP', 'code' => 'MP']);
        City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Indore']);
        City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Bhopal']);

        $response = $this->actingAs($user)
            ->getJson(route('cities.by-state', $state));

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Indore']);
        $response->assertJsonFragment(['name' => 'Bhopal']);
    }

    public function test_json_requests_that_require_branch_selection_return_json_instead_of_html(): void
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);

        $response = $this->actingAs($user)
            ->getJson(route('bilties.index'));

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Please select a branch to continue.',
        ]);
    }
}
