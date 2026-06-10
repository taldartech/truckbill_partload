<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Station;
use App\Models\Party;
use App\Models\Branch;
use App\Models\Agent;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\City;
use App\Models\Bilty;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_IN'); // Indian context for realistic data
        $company = Company::first();

        if (! $company) {
            $this->command->error('No company found to seed data for.');
            return;
        }

        $ownerId = $company->user_id;

        if (! $ownerId) {
            $this->command->error('No super admin owner found for the company.');
            return;
        }

        // Get some valid cities for relations
        $cities = City::inRandomOrder()->limit(10)->get();
        if ($cities->isEmpty()) {
            $this->command->error('No cities found. Ensure CitiesSeeder has run.');
            return;
        }

        $truckTypes = TruckType::all();
        if ($truckTypes->isEmpty()) {
            $this->command->error('No truck types found.');
            return;
        }

        // 1. Seed Stations
        $stations = [];
        for ($i = 0; $i < 5; $i++) {
            $city = $cities->random();
            $stations[] = Station::create([
                'owner_id' => $ownerId,
                'name' => $city->name . ' Hub',
                'city_id' => $city->id,
                'state_id' => $city->state_id,
            ]);
        }
        $this->command->info('Seeded Stations');

        // 2. Seed Branches
        for ($i = 0; $i < 3; $i++) {
            $city = $cities->random();
            Branch::create([
                'company_id' => $company->id,
                'name' => $city->name . ' Branch',
                'address' => $faker->address,
                'city_id' => $city->id,
                'state_id' => $city->state_id,
                'is_head_office' => $i === 0,
            ]);
        }
        $this->command->info('Seeded Branches');

        // 3. Seed Parties (Consignors & Consignees)
        $parties = [];
        $types = ['Consignor', 'Consignee', 'Both'];
        for ($i = 0; $i < 10; $i++) {
            $city = $cities->random();
            $parties[] = Party::create([
                'company_id' => $company->id,
                'name' => $faker->company,
                'type' => $faker->randomElement($types),
                'address' => $faker->address,
                'city_id' => $city->id,
                'mobile' => $faker->numerify('##########'),
                'email' => $faker->companyEmail,
                'gst_number' => '07' . strtoupper($faker->bothify('?????####?')) . '1Z5',
                'bilty_charge' => $faker->randomElement([null, 50, 100, 150]),
            ]);
        }
        $this->command->info('Seeded Parties');

        // 4. Seed Drivers
        $drivers = [];
        for ($i = 0; $i < 5; $i++) {
            $city = $cities->random();
            $drivers[] = Driver::create([
                'company_id' => $company->id,
                'name' => $faker->name('male'),
                'father_name' => $faker->name('male'),
                'mobile' => $faker->numerify('##########'),
                'address' => $faker->address,
                'city_id' => $city->id,
                'state_id' => $city->state_id,
                'license_number' => strtoupper($faker->bothify('DL-##-####-#######')),
                'aadhar_no' => $faker->numerify('############'),
            ]);
        }
        $this->command->info('Seeded Drivers');

        // 5. Seed Transporters
        $transporters = [];
        for ($i = 0; $i < 3; $i++) {
            $transporters[] = Transporter::create([
                'company_id' => $company->id,
                'name' => $faker->company . ' Transport',
                'short_name' => $faker->lexify('???'),
                'gst_number' => '07' . strtoupper($faker->bothify('?????####?')) . '1Z5',
                'pan_number' => strtoupper($faker->bothify('?????####?')),
            ]);
        }
        $this->command->info('Seeded Transporters');

        // 6. Seed Goods
        $goods = [];
        $itemNames = ['Electronics', 'Textiles', 'Machinery', 'FMCG', 'Furniture', 'Auto Parts'];
        foreach ($itemNames as $name) {
            $goods[] = Good::create([
                'owner_id' => $ownerId,
                'name' => $name,
            ]);
        }
        $this->command->info('Seeded Goods');

        // 7. Seed Package Types
        $packageTypes = [];
        $packNames = ['Carton', 'Bag', 'Pallet', 'Box', 'Bundle', 'Drum'];
        foreach ($packNames as $name) {
            $packageTypes[] = PackageType::create([
                'owner_id' => $ownerId,
                'name' => $name,
            ]);
        }
        $this->command->info('Seeded Package Types');

        // 8. Seed Trucks
        $trucks = [];
        for ($i = 0; $i < 8; $i++) {
            $trucks[] = Truck::create([
                'company_id' => $company->id,
                'truck_number' => strtoupper($faker->bothify('HR##??####')),
                'truck_type_id' => $truckTypes->random()->id,
                'transporter_id' => $faker->boolean(70) ? $transporters[array_rand($transporters)]->id : null, // 70% chance of having a transporter
                'owner_name' => $faker->name,
                'owner_mobile' => $faker->numerify('##########'),
            ]);
        }
        $this->command->info('Seeded Trucks');

        // 9. Seed Agents
        for ($i = 0; $i < 3; $i++) {
            $city = $cities->random();
            Agent::create([
                'company_id' => $company->id,
                'name' => $faker->name . ' Agency',
                'mobile' => $faker->numerify('##########'),
                'city_id' => $city->id,
                'state_id' => $city->state_id,
            ]);
        }
        $this->command->info('Seeded Agents');

        // Note: Bilties & Challans require complex relational rules (Bilty details, weights, amounts).
        // Since the schema is complex and we are providing master data, we will stick to Master CRUDs
        // which are enough to test the application's forms.
        $this->command->info('Dummy Data Seeding Completed Successfully!');
    }
}
