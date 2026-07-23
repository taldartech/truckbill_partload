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
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VoucherIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_vouchers_index_filters_by_search_and_type(): void
    {
        $context = $this->createVoucherListContext();
        $user = $context['user'];
        $match = $context['match'];

        $response = $this->actingAs($user)->get(route('vouchers.index', [
            'search' => 'Office Rent',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'voucher_type' => 'Payment',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vouchers/Index')
            ->where('filters.search', 'Office Rent')
            ->where('filters.voucher_type', 'Payment')
            ->has('vouchers.data', 1)
            ->where('vouchers.data.0.id', $match->id)
            ->etc()
        );
    }

    public function test_vouchers_index_defaults_to_current_month_range(): void
    {
        $context = $this->createVoucherListContext();

        $response = $this->actingAs($context['user'])->get(route('vouchers.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vouchers/Index')
            ->where('filters.from_date', now()->startOfMonth()->toDateString())
            ->where('filters.to_date', now()->endOfMonth()->toDateString())
            ->has('vouchers.data', 1)
            ->where('vouchers.data.0.id', $context['match']->id)
            ->etc()
        );
    }

    /**
     * @return array{user: User, match: Voucher, other: Voucher}
     */
    private function createVoucherListContext(): array
    {
        Permission::findOrCreate('view vouchers');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Voucher Filter Co', 'user_id' => $owner->id]);

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
            'name' => 'Voucher Branch',
            'address' => '1 Voucher Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'Cashier',
            'contact_number' => '9555555555',
            'email' => 'voucher@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view vouchers');

        $match = Voucher::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->toDateString(),
            'voucher_number' => 'VCH-1001',
            'voucher_type' => 'Payment',
            'account_head' => 'Office Rent',
            'debit_credit' => 'Dr',
            'amount' => 1500,
        ]);

        $other = Voucher::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->subMonths(2)->toDateString(),
            'voucher_number' => 'VCH-2002',
            'voucher_type' => 'Receipt',
            'account_head' => 'Freight Income',
            'debit_credit' => 'Cr',
            'amount' => 2500,
        ]);

        return [
            'user' => $user,
            'match' => $match,
            'other' => $other,
        ];
    }
}
