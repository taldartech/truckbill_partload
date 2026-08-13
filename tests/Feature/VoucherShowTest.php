<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VoucherShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_voucher_show(): void
    {
        $context = $this->createShowContext(['view vouchers']);

        $response = $this->actingAs($context['user'])
            ->get(route('vouchers.show', $context['voucher']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vouchers/Show')
            ->where('voucher.id', $context['voucher']->id)
            ->where('voucher.voucher_number', 'SHOW-VCH-100')
            ->where('voucher.entity_name', 'Show Party')
            ->where('voucher.entity_type_label', 'Party')
            ->where('voucher.bank_account.name', 'Show Bank Account')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_voucher_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('vouchers.show', $context['voucher']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, voucher: Voucher}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Voucher Show Co',
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
        $station = Station::create(['name' => 'Show Station', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Voucher Show Branch',
            'address' => '1 Voucher Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'voucher-show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $party = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Party',
        ]);

        $bankAccount = BankAccount::create([
            'company_id' => $company->id,
            'name' => 'Show Bank Account',
            'bank_name' => 'Show Bank',
            'account_number' => '1234567890',
        ]);

        $voucher = Voucher::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'voucher_number' => 'SHOW-VCH-100',
            'date' => now()->toDateString(),
            'voucher_type' => 'Payment',
            'accountable_type' => Party::class,
            'accountable_id' => $party->id,
            'amount' => 1500,
            'mode' => 'bank',
            'bank_account_id' => $bankAccount->id,
            'remark' => 'Show remark',
        ]);

        return [
            'user' => $user,
            'voucher' => $voucher,
        ];
    }
}
