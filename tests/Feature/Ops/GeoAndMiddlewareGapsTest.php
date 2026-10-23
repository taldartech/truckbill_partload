<?php

namespace Tests\Feature\Ops;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class GeoAndMiddlewareGapsTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_quick_form_data_endpoints_list_reference_data(): void
    {
        $context = $this->createTenantContext();

        $this->actingAs($context['user'])
            ->getJson(route('cities.quick-form-data'))
            ->assertOk()
            ->assertJsonPath('states.0.name', $context['state']->name)
            ->assertJsonPath('states.0.country_id', $context['country']->id);

        $this->actingAs($context['user'])
            ->getJson(route('states.quick-form-data'))
            ->assertOk()
            ->assertJsonPath('countries.0.name', $context['country']->name);
    }

    public function test_password_edit_page_exposes_current_user(): void
    {
        $context = $this->createTenantContext();

        $this->actingAs($context['user'])
            ->get(route('password.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Password/Edit')
                ->where('user.email', $context['user']->email)
                ->etc()
            );
    }

    public function test_json_requests_get_branch_context_errors(): void
    {
        $tenant = $this->createTenantContext();

        Role::findOrCreate(UserRole::MasterAdmin->value, 'web');
        $master = User::factory()->create();
        $master->assignRole(UserRole::MasterAdmin->value);

        $this->actingAs($master)
            ->getJson(route('bilties.index'))
            ->assertForbidden()
            ->assertJson(['message' => 'Portal admins cannot access company operations.']);

        $this->actingAs($master)
            ->get(route('bilties.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Portal admins cannot access company operations.');

        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $tenant['owner']->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($tenant['owner'])
            ->getJson(route('bilties.index'))
            ->assertStatus(409)
            ->assertJson(['message' => 'Please select a branch to continue.']);
    }
}
