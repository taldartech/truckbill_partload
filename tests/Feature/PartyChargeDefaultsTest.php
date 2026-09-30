<?php

namespace Tests\Feature;

use App\Enums\ChargeType;
use App\Enums\UserRole;
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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyChargeDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_fixed_and_percentage_charges_via_quick_add(): void
    {
        $context = $this->context(UserRole::Admin);

        $response = $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Charged Party',
            'bilty_charge' => 50,
            'bilty_charge_type' => 'fixed',
            'surcharge' => 2.5,
            'surcharge_type' => 'percentage',
            'handling_charge' => 10,
            'handling_charge_type' => 'percentage',
        ]);

        $response->assertCreated()
            ->assertJsonPath('party.bilty_charge_type', 'fixed')
            ->assertJsonPath('party.surcharge_type', 'percentage')
            ->assertJsonPath('party.handling_charge_type', 'percentage');

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Charged Party')->firstOrFail();
        $this->assertSame('50.00', $party->bilty_charge);
        $this->assertSame(ChargeType::Fixed, $party->bilty_charge_type);
        $this->assertSame('2.50', $party->surcharge);
        $this->assertSame(ChargeType::Percentage, $party->surcharge_type);
        $this->assertSame('10.00', $party->handling_charge);
        $this->assertSame(ChargeType::Percentage, $party->handling_charge_type);
    }

    public function test_super_admin_can_set_charges_via_full_form(): void
    {
        $context = $this->context(UserRole::SuperAdmin);

        $this->actingAs($context['user'])
            ->withSession([
                'active_company_id' => $context['company']->id,
                'active_branch_id' => $context['branch']->id,
            ])
            ->post(route('parties.store'), [
                ...$this->payload($context),
                'name' => 'Super Party',
                'handling_charge' => 15,
                'handling_charge_type' => 'percentage',
            ])
            ->assertSessionHasNoErrors();

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Super Party')->firstOrFail();
        $this->assertSame('15.00', $party->handling_charge);
        $this->assertSame(ChargeType::Percentage, $party->handling_charge_type);
    }

    public function test_charge_type_defaults_to_fixed_when_not_sent(): void
    {
        $context = $this->context(UserRole::Admin);

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Default Type Party',
            'surcharge' => 30,
        ])->assertCreated();

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Default Type Party')->firstOrFail();
        $this->assertSame('30.00', $party->surcharge);
        $this->assertSame(ChargeType::Fixed, $party->surcharge_type);
    }

    public function test_non_admin_charges_are_ignored_on_quick_add(): void
    {
        $context = $this->context(UserRole::Moderator);

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Moderator Party',
            'bilty_charge' => 99,
            'surcharge' => 5,
            'surcharge_type' => 'percentage',
        ])->assertCreated();

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Moderator Party')->firstOrFail();
        $this->assertNull($party->bilty_charge);
        $this->assertNull($party->surcharge);
        $this->assertSame(ChargeType::Fixed, $party->surcharge_type);
    }

    public function test_non_admin_update_keeps_existing_charges(): void
    {
        $context = $this->context(UserRole::Moderator, ['edit parties']);
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Existing Party',
            'bilty_charge' => 40,
            'bilty_charge_type' => 'fixed',
            'surcharge' => 3,
            'surcharge_type' => 'percentage',
        ]);

        $this->actingAs($context['user'])
            ->put(route('parties.update', $party), [
                ...$this->payload($context),
                'name' => 'Existing Party Renamed',
                'bilty_charge' => 0,
                'surcharge' => '',
                'surcharge_type' => 'fixed',
            ])
            ->assertSessionHasNoErrors();

        $party->refresh();
        $this->assertSame('Existing Party Renamed', $party->name);
        $this->assertSame('40.00', $party->bilty_charge);
        $this->assertSame('3.00', $party->surcharge);
        $this->assertSame(ChargeType::Percentage, $party->surcharge_type);
    }

    public function test_percentage_charge_cannot_exceed_one_hundred(): void
    {
        $context = $this->context(UserRole::Admin);

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Too Much Party',
            'handling_charge' => 150,
            'handling_charge_type' => 'percentage',
        ])->assertStatus(422)->assertJsonValidationErrors(['handling_charge']);

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Fixed Large Party',
            'handling_charge' => 150,
            'handling_charge_type' => 'fixed',
        ])->assertCreated();
    }

    public function test_invalid_charge_type_is_rejected(): void
    {
        $context = $this->context(UserRole::Admin);

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->payload($context),
            'name' => 'Bad Type Party',
            'surcharge' => 5,
            'surcharge_type' => 'per_kg',
        ])->assertStatus(422)->assertJsonValidationErrors(['surcharge_type']);
    }

    public function test_bilty_form_exposes_party_charge_defaults(): void
    {
        $context = $this->context(UserRole::Admin, ['edit bilties']);
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Bilty Party',
            'surcharge' => 4,
            'surcharge_type' => 'percentage',
        ]);
        $bilty = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => 'CHG-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['branch']->station_id,
            'to_station_id' => $context['branch']->station_id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'payment_type' => 'To Pay',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
            'created_by' => $context['user']->id,
        ]);

        $this->actingAs($context['user'])
            ->get(route('bilties.edit', $bilty))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Bilties/Edit')
                ->where('parties.0.name', 'Bilty Party')
                ->where('parties.0.surcharge', '4.00')
                ->where('parties.0.surcharge_type', 'percentage')
                ->where('parties.0.handling_charge_type', 'fixed')
                ->etc()
            );
    }

    /**
     * @param  array{state: State, city: City}  $context
     * @return array{address: string, state_id: int, city_id: int, mobile: string}
     */
    private function payload(array $context): array
    {
        return [
            'address' => '1 Market Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ];
    }

    /**
     * @param  list<string>  $extraPermissions
     * @return array{user: User, company: Company, branch: Branch, state: State, city: City}
     */
    private function context(UserRole $role, array $extraPermissions = []): array
    {
        $permissions = ['create parties', ...$extraPermissions];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate($role->value, 'web');

        $owner = $role === UserRole::SuperAdmin
            ? User::factory()->create(['company_id' => null, 'branch_id' => null])
            : User::factory()->create();

        $company = Company::create(['name' => 'Charge Co', 'user_id' => $owner->id]);
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

        if ($role === UserRole::SuperAdmin) {
            $user = $owner;
        } else {
            $user = User::factory()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]);
            $user->givePermissionTo($permissions);
        }

        $user->assignRole($role->value);

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
            'state' => $state,
            'city' => $city,
        ];
    }
}
