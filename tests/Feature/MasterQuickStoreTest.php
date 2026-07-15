<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
