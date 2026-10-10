<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AgentBulkUploadTest extends TestCase
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

    public function test_agent_template_download(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $response = $this->actingAs($context['user'])
            ->get(route('agents.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('agents_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_agent_bulk_upload_success_with_existing_city(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);

        $csv = "Agent Name,Address,State,City,Mobile,Email,GST Number,Transporter ID\n"
            ."Shree Ram Agency,12 Transport Nagar,Rajasthan,Udaipur,9829012345,ram@transport.com,08AAAAA0000A1Z5,08AAAAA0000A1Z5\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

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

        $this->assertDatabaseHas('agents', [
            'company_id' => $context['company']->id,
            'name' => 'Shree Ram Agency',
            'address' => '12 Transport Nagar',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9829012345',
            'email' => 'ram@transport.com',
            'gst_no' => '08AAAAA0000A1Z5',
            'transport_id' => '08AAAAA0000A1Z5',
        ]);
    }

    public function test_agent_bulk_upload_fails_when_city_does_not_exist_and_does_not_create_city(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Test Agent,123 Main St,Rajasthan,NonExistentCityXYZ,9829012345\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $initialCityCount = City::count();

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

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

        // Assert agent was NOT created
        $this->assertDatabaseMissing('agents', ['name' => 'Test Agent']);
    }

    public function test_agent_bulk_upload_fails_when_state_does_not_exist(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Test Agent,123 Main St,NonExistentState,Jaipur,9829012345\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

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
        $this->assertEquals('State', $json['errors'][0]['field']);
        $this->assertStringContainsString('was not found in state records', $json['errors'][0]['message']);
        $this->assertDatabaseMissing('agents', ['name' => 'Test Agent']);
    }

    public function test_agent_bulk_upload_validates_mobile_format(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Gujarat', 'code' => 'GJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Ahmedabad']);

        // Invalid mobile: 12345 (not 10 digits and doesn't start with 6-9)
        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Invalid Mobile Agent,45 Narol,Gujarat,Ahmedabad,12345\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'failed' => 1,
            ],
        ]);

        $this->assertDatabaseMissing('agents', ['name' => 'Invalid Mobile Agent']);
    }

    public function test_agent_bulk_upload_validates_gst_format(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Gujarat', 'code' => 'GJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Ahmedabad']);

        // Invalid GST: invalid pattern
        $csv = "Agent Name,Address,State,City,Mobile,GST Number\n"
            ."Bad GST Agent,45 Narol,Gujarat,Ahmedabad,9898012345,INVALIDGST123\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'failed' => 1,
            ],
        ]);

        $this->assertDatabaseMissing('agents', ['name' => 'Bad GST Agent']);
    }

    public function test_agent_bulk_upload_requires_create_agents_permission(): void
    {
        $context = $this->createAuthContext(); // No permissions

        $file = UploadedFile::fake()->createWithContent('agents.csv', "Agent Name\nTest");

        $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_agent_bulk_upload_allows_duplicate_names_with_different_mobiles(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Same Agent,12 Transport Nagar,Rajasthan,Udaipur,9829012345\n"
            ."Same Agent,15 Transport Nagar,Rajasthan,Udaipur,9829054321\n";

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total' => 2,
                'successful' => 2,
                'failed' => 0,
            ],
        ]);

        $this->assertEquals(2, Agent::where('name', 'Same Agent')->count());
    }

    public function test_agent_bulk_upload_detects_duplicate_mobile(): void
    {
        $context = $this->createAuthContext(['create agents']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);

        Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Existing Agent',
            'address' => 'Old Address',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9829012345',
        ]);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Agent One,Address 1,Rajasthan,Udaipur,9829012345\n" // duplicate in DB
            ."Agent Two,Address 2,Rajasthan,Udaipur,9829099999\n"
            ."Agent Three,Address 3,Rajasthan,Udaipur,9829099999\n"; // duplicate within batch

        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();
        $this->assertFalse($json['success']);
        $this->assertEquals(2, $json['summary']['failed']);
        $this->assertEquals(1, $json['summary']['successful']);
    }
}

