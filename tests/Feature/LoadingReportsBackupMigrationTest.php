<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Good;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoadingReportsBackupMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const BACKUP_PATH = 'backups/loading_reports_backup.json';

    public function test_drop_backs_up_rows_and_rollback_restores_them(): void
    {
        Storage::fake('local');
        $migration = $this->dropMigration();

        $migration->down();
        $this->seedLoadingReportRows();

        $migration->up();

        $this->assertFalse(Schema::hasTable('loading_reports'));
        $this->assertFalse(Schema::hasTable('loading_report_details'));
        Storage::disk('local')->assertExists(self::BACKUP_PATH);

        $backup = json_decode(Storage::disk('local')->get(self::BACKUP_PATH), true);
        $this->assertCount(2, $backup['loading_reports']);
        $this->assertCount(1, $backup['loading_report_details']);

        $migration->down();

        $this->assertSame(2, DB::table('loading_reports')->count());
        $this->assertDatabaseHas('loading_reports', ['id' => 10, 'driver_name' => 'Ramesh', 'remark' => 'Kept']);
        $this->assertDatabaseHas('loading_reports', ['id' => 11, 'delete_remark' => 'Soft deleted']);
        $this->assertNotNull(DB::table('loading_reports')->where('id', 11)->value('deleted_at'));
        $this->assertDatabaseHas('loading_report_details', [
            'id' => 20,
            'loading_report_id' => 10,
            'no_of_packages' => 7,
        ]);
    }

    public function test_rollback_without_backup_recreates_empty_tables(): void
    {
        Storage::fake('local');

        $this->dropMigration()->down();

        $this->assertTrue(Schema::hasTable('loading_reports'));
        $this->assertTrue(Schema::hasTable('loading_report_details'));
        $this->assertSame(0, DB::table('loading_reports')->count());
    }

    private function dropMigration(): Migration
    {
        return require database_path('migrations/2026_09_30_200412_drop_loading_reports_tables.php');
    }

    /**
     * @return array{company: Company, fromStation: Station, toStation: Station, truck: Truck, good: Good, consignee: Party}
     */
    private function createParents(): array
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Backup Co', 'user_id' => $owner->id]);
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);

        return [
            'company' => $company,
            'fromStation' => Station::create(['name' => 'Backup From', 'owner_id' => $owner->id]),
            'toStation' => Station::create(['name' => 'Backup To', 'owner_id' => $owner->id]),
            'truck' => Truck::create(['company_id' => $company->id, 'truck_number' => 'MH-BK-1']),
            'good' => Good::create(['owner_id' => $owner->id, 'name' => 'Backup Goods']),
            'consignee' => Party::create([
                'company_id' => $company->id,
                'name' => 'Backup Consignee',
                'state_id' => $state->id,
                'city_id' => $city->id,
            ]),
        ];
    }

    private function seedLoadingReportRows(): void
    {
        $timestamps = ['created_at' => now(), 'updated_at' => now()];
        $parents = $this->createParents();
        $reportParents = [
            'company_id' => $parents['company']->id,
            'from_station_id' => $parents['fromStation']->id,
            'to_station_id' => $parents['toStation']->id,
            'truck_id' => $parents['truck']->id,
        ];

        DB::table('loading_reports')->insert([
            [
                'id' => 10,
                ...$reportParents,
                'date' => '2026-09-01',
                'driver_name' => 'Ramesh',
                'remark' => 'Kept',
                'no_of_packages' => 7,
                'gross_weight' => 70,
                'delete_remark' => null,
                'deleted_at' => null,
                ...$timestamps,
            ],
            [
                'id' => 11,
                ...$reportParents,
                'date' => '2026-09-02',
                'driver_name' => 'Suresh',
                'remark' => null,
                'no_of_packages' => 0,
                'gross_weight' => 0,
                'delete_remark' => 'Soft deleted',
                'deleted_at' => now(),
                ...$timestamps,
            ],
        ]);

        DB::table('loading_report_details')->insert([
            'id' => 20,
            'company_id' => $parents['company']->id,
            'loading_report_id' => 10,
            'good_id' => $parents['good']->id,
            'consignee_id' => $parents['consignee']->id,
            'no_of_packages' => 7,
            'gross_weight' => 70,
            ...$timestamps,
        ]);
    }
}
