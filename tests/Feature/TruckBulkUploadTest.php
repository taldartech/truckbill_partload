<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TruckBulkUploadTest extends TestCase
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

    public function test_truck_template_download(): void
    {
        $context = $this->createAuthContext(['create trucks']);

        $response = $this->actingAs($context['user'])
            ->get(route('trucks.bulk-upload.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('trucks_bulk_upload_template.csv', $response->headers->get('content-disposition'));
    }

    public function test_truck_bulk_upload_success(): void
    {
        $context = $this->createAuthContext(['create trucks']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Maharashtra', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $station = Station::create([
            'owner_id' => $context['owner']->id,
            'name' => 'Mumbai Central',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        $truckType = TruckType::create([
            'owner_id' => $context['owner']->id,
            'name' => '14 Ft Open Body',
        ]);
        $agent = Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Sharma Transport',
        ]);

        $csv = "Truck Number,Truck Type,Capacity,Status,State,City,Station,Truck Owner,Driver Name,RC Number,RC Expiry Date\n"
            ."mh04ab1234,14 Ft Open Body,15 Tons,Available,Maharashtra,Mumbai,Mumbai Central,Sharma Transport,Ramesh Driver,RC123456,2028-12-31\n";

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file]);

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

        $this->assertDatabaseHas('trucks', [
            'company_id' => $context['company']->id,
            'truck_number' => 'MH04AB1234',
            'truck_type_id' => $truckType->id,
            'agent_id' => $agent->id,
            'current_state_id' => $state->id,
            'current_city_id' => $city->id,
            'current_station_id' => $station->id,
            'capacity' => '15 Tons',
            'status' => 'Available',
            'rc_no' => 'RC123456',
            'rc_expiry_date' => '2028-12-31',
        ]);
    }

    public function test_truck_bulk_upload_fails_when_city_does_not_exist_and_does_not_create_city(): void
    {
        $context = $this->createAuthContext(['create trucks']);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);

        $csv = "Truck Number,State,City\n"
            ."RJ14XX9999,Rajasthan,NonExistentCityXYZ\n";

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file]);

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
        $this->assertStringContainsString('was not found in city records', $json['errors'][0]['message']);

        $this->assertDatabaseMissing('cities', ['name' => 'NonExistentCityXYZ']);
        $this->assertDatabaseMissing('trucks', ['truck_number' => 'RJ14XX9999']);
    }

    public function test_truck_bulk_upload_does_not_create_driver(): void
    {
        $context = $this->createAuthContext(['create trucks']);

        $csv = "Truck Number,Driver Name\n"
            ."GJ01AB1111,Mahesh Kumar Driver\n";

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'summary' => [
                'successful' => 1,
                'failed' => 0,
            ],
        ]);

        $this->assertDatabaseHas('trucks', [
            'truck_number' => 'GJ01AB1111',
            'company_id' => $context['company']->id,
        ]);

        if (Schema::hasTable('drivers')) {
            $this->assertDatabaseMissing('drivers', ['name' => 'Mahesh Kumar Driver']);
        }
    }

    public function test_truck_bulk_upload_detects_duplicate_truck_number(): void
    {
        $context = $this->createAuthContext(['create trucks']);

        Truck::create([
            'company_id' => $context['company']->id,
            'truck_number' => 'MH04AB9999',
        ]);

        $csv = "Truck Number\n"
            ."MH04AB9999\n"
            ."RJ14CD2222\n"
            ."rj14cd2222\n";

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'summary' => [
                'total' => 3,
                'successful' => 1,
                'failed' => 2,
            ],
        ]);

        $json = $response->json();
        $this->assertCount(2, $json['errors']);
        $this->assertStringContainsString('already exists', $json['errors'][0]['message']);
        $this->assertStringContainsString('Duplicate', $json['errors'][1]['message']);
    }

    public function test_truck_bulk_upload_requires_create_trucks_permission(): void
    {
        $context = $this->createAuthContext([]);

        $file = UploadedFile::fake()->createWithContent('trucks.csv', "Truck Number\nMH04AB1234");

        $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file])
            ->assertForbidden();
    }
}
