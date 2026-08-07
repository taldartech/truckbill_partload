<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BranchHeadOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_includes_head_office_branch_id_for_company(): void
    {
        $context = $this->createBranchFormContext('create branches');
        $headOffice = $context['head_office'];

        $response = $this->actingAs($context['user'])
            ->withSession([
                'active_company_id' => $context['company']->id,
                'active_branch_id' => $headOffice->id,
            ])
            ->get(route('branches.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Branches/Create')
            ->has('companies', 1)
            ->where('companies.0.head_office_branch_id', $headOffice->id)
        );
    }

    public function test_edit_page_includes_head_office_branch_id_for_company(): void
    {
        $context = $this->createBranchFormContext('edit branches');
        $otherBranch = $context['other_branch'];

        $response = $this->actingAs($context['user'])
            ->withSession([
                'active_company_id' => $context['company']->id,
                'active_branch_id' => $context['head_office']->id,
            ])
            ->get(route('branches.edit', $otherBranch));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Branches/Edit')
            ->where('companies.0.head_office_branch_id', $context['head_office']->id)
        );
    }

    public function test_cannot_create_second_head_office_for_same_company(): void
    {
        $context = $this->createBranchFormContext('create branches');

        $response = $this->actingAs($context['user'])
            ->withSession([
                'active_company_id' => $context['company']->id,
                'active_branch_id' => $context['head_office']->id,
            ])
            ->from(route('branches.create'))
            ->post(route('branches.store'), $this->validBranchPayload([
                'company_id' => $context['company']->id,
                'station_id' => $context['station']->id,
                'country_id' => $context['country']->id,
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'name' => 'Second HO Attempt',
                'is_head_office' => true,
            ]));

        $response->assertRedirect(route('branches.create'));
        $response->assertSessionHasErrors(['is_head_office']);
        $this->assertDatabaseMissing('branches', [
            'name' => 'Second HO Attempt',
            'is_head_office' => true,
        ]);
    }

    public function test_existing_head_office_branch_can_remain_head_office_on_update(): void
    {
        $context = $this->createBranchFormContext('edit branches');
        $headOffice = $context['head_office'];

        $response = $this->actingAs($context['user'])
            ->withSession([
                'active_company_id' => $context['company']->id,
                'active_branch_id' => $headOffice->id,
            ])
            ->put(route('branches.update', $headOffice), $this->validBranchPayload([
                'company_id' => $context['company']->id,
                'station_id' => $context['station']->id,
                'country_id' => $context['country']->id,
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'name' => 'Updated Head Office',
                'is_head_office' => true,
            ]));

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'id' => $headOffice->id,
            'name' => 'Updated Head Office',
            'is_head_office' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validBranchPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Branch',
            'address' => '123 Test Street',
            'pin_code' => '400001',
            'is_head_office' => false,
            'has_gst' => false,
            'contact_person' => 'Test Person',
            'contact_number' => '9876543210',
            'email' => 'branch-'.uniqid().'@example.com',
            'use_company_logo' => true,
        ], $overrides);
    }

    /**
     * @return array{
     *     user: User,
     *     company: Company,
     *     head_office: Branch,
     *     other_branch: Branch,
     *     station: Station,
     *     country: Country,
     *     state: State,
     *     city: City
     * }
     */
    private function createBranchFormContext(string $permission): array
    {
        Permission::findOrCreate($permission);

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'HO Co',
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
        $station = Station::create(['name' => 'Mumbai Central', 'owner_id' => $owner->id]);

        $headOffice = Branch::create([
            'company_id' => $company->id,
            'name' => 'Head Office',
            'address' => 'HO Address',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'is_head_office' => true,
            'contact_person' => 'HO Admin',
            'contact_number' => '9876543210',
            'email' => 'ho@example.com',
        ]);

        $otherBranch = Branch::create([
            'company_id' => $company->id,
            'name' => 'City Branch',
            'address' => 'City Address',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400002',
            'station_id' => $station->id,
            'is_head_office' => false,
            'contact_person' => 'City Admin',
            'contact_number' => '9876543211',
            'email' => 'city@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $headOffice->id,
        ]);
        $user->givePermissionTo($permission);

        return [
            'user' => $user,
            'company' => $company,
            'head_office' => $headOffice,
            'other_branch' => $otherBranch,
            'station' => $station,
            'country' => $country,
            'state' => $state,
            'city' => $city,
        ];
    }
}
