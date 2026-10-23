<?php

namespace Tests\Feature\Ops;

use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\City;
use App\Models\Group;
use App\Models\Party;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class BulkUploadRowValidationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function errorMessages(array $json): array
    {
        return array_column($json['errors'], 'message');
    }

    private function upload(User $user, string $route, string $csv): array
    {
        return $this->actingAs($user)
            ->post(route($route), ['file' => UploadedFile::fake()->createWithContent('rows.csv', $csv)])
            ->assertOk()
            ->json();
    }

    public function test_party_bulk_upload_reports_state_city_and_group_problems(): void
    {
        $context = $this->createTenantContext(['create parties']);
        $state = $context['state'];
        $city = $context['city'];
        Group::create(['company_id' => $context['company']->id, 'name' => 'Known Group']);

        $csv = "Party Name,Address,State,City,Mobile,Email,GST Number,Group,Bilty Charge\n"
            ."No State,1 Road,,Udaipur,9829012345,,,,\n"
            ."Bad State,1 Road,Atlantis,Udaipur,9829012346,,,,\n"
            ."No City,1 Road,{$state->name},,9829012347,,,,\n"
            ."Missing City,1 Road,{$state->name},Nowhere,9829012348,,,,\n"
            ."Bad Group,1 Road,{$state->name},Udaipur,9829012349,,,Ghost Group,\n"
            ."By Id,1 Road,{$state->id},{$city->id},9829012350,,,Known Group,\n"
            ."By Id Again,1 Road,{$state->code},udaipur,9829012351,,,Known Group,\n"
            ."By Id Cached,1 Road,{$state->code},udaipur,9829012352,,,,12.5\n";

        $json = $this->upload($context['user'], 'parties.bulk-upload', $csv);

        $messages = implode('|', $this->errorMessages($json));
        $this->assertStringContainsString('The State field is required.', $messages);
        $this->assertStringContainsString('State "Atlantis" was not found', $messages);
        $this->assertStringContainsString('The City field is required.', $messages);
        $this->assertStringContainsString('City "Nowhere" was not found', $messages);
        $this->assertStringContainsString('Group "Ghost Group" was not found', $messages);
        $this->assertSame(3, $json['summary']['successful']);
        $this->assertSame(1, Party::query()->where('name', 'By Id Cached')->count());
    }

    public function test_station_bulk_upload_city_agent_and_company_context_rules(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $state = $context['state'];
        $city = $context['city'];
        Agent::create(['company_id' => $context['company']->id, 'name' => 'Known Agent']);

        $csv = "Station Name,State,City,Default Agent\n"
            ."Numeric City Station,{$state->name},{$city->id},Known Agent\n"
            ."Unknown Agent Station,{$state->name},Udaipur,Ghost Agent\n";

        $json = $this->upload($context['user'], 'stations.bulk-upload', $csv);

        $this->assertContains('Agent "Ghost Agent" not found for the active company.', $this->errorMessages($json));
        $this->assertSame(1, $json['summary']['successful']);

        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        Permission::findOrCreate('create stations');
        $super = User::factory()->create();
        $super->assignRole(UserRole::SuperAdmin->value);
        $super->givePermissionTo('create stations');

        $csv = "Station Name,State,City,Default Agent\nAgent Without Company,{$state->name},Udaipur,Known Agent\n";
        $json = $this->upload($super, 'stations.bulk-upload', $csv);

        $this->assertContains('Cannot assign default agent without an active company context.', $this->errorMessages($json));
        $this->assertSame(1, City::query()->count());
        $this->assertSame(0, Station::query()->where('name', 'Agent Without Company')->count());
    }

    public function test_truck_bulk_upload_requires_truck_number(): void
    {
        $context = $this->createTenantContext(['create trucks']);

        $csv = "Truck Number,Truck Type,Capacity,Status,State,City,Station,Truck Owner,Driver Name\n"
            .",,10,,,,,,Some Driver\n";

        $json = $this->upload($context['user'], 'trucks.bulk-upload', $csv);

        $this->assertSame(0, $json['summary']['successful']);
        $this->assertNotEmpty($json['errors']);
    }
}
