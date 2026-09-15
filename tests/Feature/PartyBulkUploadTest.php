<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Group;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PartyBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthContext(array $permissions = []): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Test Transport Co',
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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'owner' => $owner,
            'company' => $company,
            'user' => $user,
        ];
    }

    public function test_party_template_download(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $response = $this->actingAs($context['user'])
            ->get(route('parties.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('parties_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_party_bulk_upload_success_with_existing_city_and_existing_group(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);
        $group = Group::create([
            'company_id' => $context['company']->id,
            'name' => 'Corporate Clients',
        ]);

        $csv = "Party Name,Address,State,City,Mobile,Email,GST Number,Group,Bilty Charge\n"
            ."ABC Enterprises,12 Industrial Area Phase 1,Rajasthan,Udaipur,9829012345,contact@abcenterprises.com,08AAAAA0000A1Z5,Corporate Clients,50.00\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 1,
                'successful' => 1,
                'failed' => 0,
                'skipped' => 0,
            ],
            'errors' => [],
        ]);

        $this->assertDatabaseHas('parties', [
            'company_id' => $context['company']->id,
            'group_id' => $group->id,
            'name' => 'ABC Enterprises',
            'address' => '12 Industrial Area Phase 1',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9829012345',
            'email' => 'contact@abcenterprises.com',
            'gst_number' => '08AAAAA0000A1Z5',
            'bilty_charge' => 50.00,
        ]);
    }

    public function test_party_bulk_upload_success_with_null_group(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Gujarat', 'code' => 'GJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Ahmedabad']);

        $csv = "Party Name,Address,State,City,Mobile,Group\n"
            ."XYZ Traders,45 Commercial Market,Gujarat,Ahmedabad,9898012345,\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 1,
                'successful' => 1,
                'failed' => 0,
            ],
        ]);

        $this->assertDatabaseHas('parties', [
            'company_id' => $context['company']->id,
            'group_id' => null,
            'name' => 'XYZ Traders',
            'address' => '45 Commercial Market',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9898012345',
        ]);
    }

    public function test_party_bulk_upload_fails_when_city_does_not_exist_and_does_not_create_city(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);

        $csv = "Party Name,Address,State,City,Mobile\n"
            ."Test Party,123 Main St,Rajasthan,NonExistentCityXYZ,9829012345\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $initialCityCount = City::count();

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'total' => 1,
                'successful' => 0,
                'failed' => 1,
            ],
        ]);

        $json = $response->json();
        $this->assertCount(1, $json['errors']);
        $this->assertEquals('City', $json['errors'][0]['field']);
        $this->assertStringContainsString('was not found in city records', $json['errors'][0]['message']);

        // Strict assertion: DO NOT CREATE CITY IF NOT EXIST
        $this->assertDatabaseMissing('cities', ['name' => 'NonExistentCityXYZ']);
        $this->assertEquals($initialCityCount, City::count());

        // Assert party was NOT created
        $this->assertDatabaseMissing('parties', ['name' => 'Test Party']);
    }

    public function test_party_bulk_upload_fails_when_group_does_not_exist_and_does_not_create_group(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);

        $csv = "Party Name,Address,State,City,Mobile,Group\n"
            ."Test Party,123 Main St,Rajasthan,Udaipur,9829012345,NonExistentGroupABC\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $initialGroupCount = Group::count();

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'total' => 1,
                'successful' => 0,
                'failed' => 1,
            ],
        ]);

        $json = $response->json();
        $this->assertCount(1, $json['errors']);
        $this->assertEquals('Group', $json['errors'][0]['field']);
        $this->assertStringContainsString('was not found in group records', $json['errors'][0]['message']);

        // Strict assertion: DO NOT CREATE GROUP IF NOT EXIST
        $this->assertDatabaseMissing('groups', ['name' => 'NonExistentGroupABC']);
        $this->assertEquals($initialGroupCount, Group::count());

        // Assert party was NOT created
        $this->assertDatabaseMissing('parties', ['name' => 'Test Party']);
    }

    public function test_party_bulk_upload_validates_mobile_format(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Gujarat', 'code' => 'GJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Ahmedabad']);

        // Invalid mobile: 12345
        $csv = "Party Name,Address,State,City,Mobile\n"
            ."Invalid Mobile Party,45 Narol,Gujarat,Ahmedabad,12345\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'failed' => 1,
            ],
        ]);

        $this->assertDatabaseMissing('parties', ['name' => 'Invalid Mobile Party']);
    }

    public function test_party_bulk_upload_validates_gst_format(): void
    {
        $context = $this->createAuthContext(['create parties']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Gujarat', 'code' => 'GJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Ahmedabad']);

        // Invalid GST: invalid pattern
        $csv = "Party Name,Address,State,City,Mobile,GST Number\n"
            ."Bad GST Party,45 Narol,Gujarat,Ahmedabad,9898012345,INVALIDGST123\n";

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'failed' => 1,
            ],
        ]);

        $this->assertDatabaseMissing('parties', ['name' => 'Bad GST Party']);
    }

    public function test_party_bulk_upload_requires_create_parties_permission(): void
    {
        $context = $this->createAuthContext(); // No permissions

        $file = UploadedFile::fake()->createWithContent('parties.csv', "Party Name\nTest");

        $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file])
            ->assertForbidden();
    }
}
