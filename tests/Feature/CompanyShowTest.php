<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_company_show(): void
    {
        $context = $this->createShowContext(['view companies']);

        $response = $this->actingAs($context['user'])
            ->get(route('companies.show', $context['company']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Companies/Show')
            ->where('company.id', $context['company']->id)
            ->where('company.name', 'Show Co')
            ->where('company.city.name', 'Mumbai')
            ->where('company.state.name', 'MH')
            ->where('company.country.name', 'India')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_company_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('companies.show', $context['company']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, company: Company}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);

        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
            'email' => 'showco@example.com',
            'address' => '1 Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'user' => $user,
            'company' => $company,
        ];
    }
}
