<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BiltyIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilties_index_filters_by_search_date_and_parties(): void
    {
        $context = $this->createBiltyListContext();
        $user = $context['user'];
        $match = $context['match'];
        $other = $context['other'];

        $response = $this->actingAs($user)->get(route('bilties.index', [
            'search' => 'BLT-100',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'consignor_id' => $match->consignor_id,
            'consignee_id' => $match->consignee_id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Bilties/Index')
            ->where('filters.search', 'BLT-100')
            ->where('filters.consignor_id', (string) $match->consignor_id)
            ->where('filters.consignee_id', (string) $match->consignee_id)
            ->has('bilties.data', 1)
            ->where('bilties.data.0.id', $match->id)
            ->where('bilties.data.0.bilty_number', $match->bilty_number)
            ->etc()
        );

        $this->assertNotEquals($match->id, $other->id);
    }

    public function test_bilties_index_defaults_to_current_month_range(): void
    {
        $context = $this->createBiltyListContext();

        $response = $this->actingAs($context['user'])->get(route('bilties.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Bilties/Index')
            ->where('filters.from_date', now()->startOfMonth()->toDateString())
            ->where('filters.to_date', now()->endOfMonth()->toDateString())
            ->has('bilties.data', 1)
            ->where('bilties.data.0.id', $context['match']->id)
            ->etc()
        );
    }

    /**
     * @return array{user: User, match: Bilty, other: Bilty}
     */
    private function createBiltyListContext(): array
    {
        Permission::findOrCreate('view bilties');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Filter Co', 'user_id' => $owner->id]);

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
        $fromStation = Station::create(['name' => 'UDR', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'JPR', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Filter Branch',
            'address' => '1 Filter Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Filter',
            'contact_number' => '9888888888',
            'email' => 'filter@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view bilties');

        $consignorA = Party::create(['company_id' => $company->id, 'name' => 'Consignor A']);
        $consigneeA = Party::create(['company_id' => $company->id, 'name' => 'Consignee A']);
        $consignorB = Party::create(['company_id' => $company->id, 'name' => 'Consignor B']);
        $consigneeB = Party::create(['company_id' => $company->id, 'name' => 'Consignee B']);

        $match = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'BLT-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignorA->id,
            'consignee_id' => $consigneeA->id,
            'amount' => 100,
            'status' => 'Pending',
        ]);

        $other = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'BLT-200',
            'date' => now()->subMonths(2)->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignorB->id,
            'consignee_id' => $consigneeB->id,
            'amount' => 200,
            'status' => 'Pending',
        ]);

        return [
            'user' => $user,
            'match' => $match,
            'other' => $other,
        ];
    }
}
