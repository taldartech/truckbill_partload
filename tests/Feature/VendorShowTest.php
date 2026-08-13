<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VendorShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_vendor_show(): void
    {
        $context = $this->createShowContext(['view vendors']);

        $response = $this->actingAs($context['user'])
            ->get(route('vendors.show', $context['vendor']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vendors/Show')
            ->where('vendor.id', $context['vendor']->id)
            ->where('vendor.name', 'Show Vendor')
            ->where('vendor.state.name', 'MH')
            ->where('vendor.city.name', 'Mumbai')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_vendor_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('vendors.show', $context['vendor']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, vendor: Vendor}
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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $vendor = Vendor::create([
            'company_id' => $company->id,
            'name' => 'Show Vendor',
            'address' => '1 Show Street',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
            'email' => 'show-vendor@example.com',
            'gst_number' => '27AAAAA0000A1Z5',
        ]);

        return [
            'user' => $user,
            'vendor' => $vendor,
        ];
    }
}
