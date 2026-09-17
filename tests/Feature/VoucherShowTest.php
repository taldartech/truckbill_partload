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

class VoucherShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_is_redirected_to_accounting_vouchers(): void
    {
        $context = $this->createContext(['view vouchers']);

        $this->actingAs($context['user'])
            ->get(route('vouchers.show', 1))
            ->assertRedirect(route('accounting.vouchers.index'));
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User}
     */
    private function createContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Voucher Co', 'user_id' => $owner->id]);
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
        $state = State::create(['country_id' => $country->id, 'name' => 'RJ', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);
        $station = Station::create(['name' => 'UDR', 'owner_id' => $owner->id]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main',
            'address' => '1 St',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'a@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo($permissions);

        return compact('user');
    }
}
