<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BranchSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_create_auto_creates_settings(): void
    {
        $branch = $this->createBranch();

        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $branch->id,
        ]);

        $settings = $branch->fresh('settings')->settings;
        $this->assertNotNull($settings);
        $this->assertTrue($settings->resolvedBiltyPrintConfig()['copies']['consignor']['enabled']);
    }

    public function test_branch_settings_update_persists_initials_and_print_config(): void
    {
        $user = $this->createUserWithBranchPermission('edit branches');
        $branch = $this->createBranch();

        $response = $this->actingAs($user)->put("/branches/{$branch->id}/settings", [
            'bilty_initial' => 'BLT/',
            'challan_initial' => 'CH/',
            'main_challan_initial' => 'MCH/',
            'invoice_initial' => 'INV/',
            'inward_register_initial' => 'IR/',
            'bilty_print_config' => [
                'note' => 'Test note',
                'copies' => [
                    'consignor' => [
                        'enabled' => true,
                        'show_amount_by_bob' => [
                            'to_pay' => true,
                            'paid' => true,
                            'tbb' => true,
                            'foc' => false,
                        ],
                    ],
                    'consignee' => [
                        'enabled' => true,
                        'show_amount_by_bob' => [
                            'to_pay' => false,
                            'paid' => false,
                            'tbb' => false,
                            'foc' => false,
                        ],
                    ],
                    'driver' => [
                        'enabled' => false,
                        'show_amount_by_bob' => [
                            'to_pay' => false,
                            'paid' => false,
                            'tbb' => false,
                            'foc' => false,
                        ],
                    ],
                    'office' => [
                        'enabled' => true,
                        'show_amount_by_bob' => [
                            'to_pay' => true,
                            'paid' => false,
                            'tbb' => false,
                            'foc' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('branches.index'));

        $settings = $branch->fresh('settings')->settings;
        $this->assertSame('BLT/', $settings->bilty_initial);
        $this->assertSame('Test note', $settings->resolvedBiltyPrintConfig()['note']);
        $this->assertTrue($settings->resolvedBiltyPrintConfig()['copies']['consignor']['show_amount_by_bob']['tbb']);
    }

    public function test_branch_accessor_reads_document_initial_from_settings(): void
    {
        $branch = $this->createBranch();
        $branch->settings()->update(['bilty_initial' => 'SGT/']);

        $this->assertSame('SGT/', $branch->fresh()->bilty_initial);
    }

    private function createBranch(): Branch
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Test Co', 'user_id' => $user->id]);
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $station = Station::create(['name' => 'Mumbai', 'owner_id' => $user->id]);

        return Branch::create([
            'company_id' => $company->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Test',
            'contact_number' => '9876543210',
            'email' => 'branch@example.com',
        ]);
    }

    private function createUserWithBranchPermission(string $permission): User
    {
        Permission::findOrCreate($permission);

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
