<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['email' => 'contact@truckbill.com'],
            [
                'name' => 'Truckbill Logistics Pvt Ltd',
                'phone' => '9876543210',
                'address' => '123, Logistics Hub, New Delhi',
                'gst_number' => '07AAAAA0000A1Z5',
                'status' => 1,
            ]
        );

        $this->call([
            SubscriptionPlanSeeder::class,
            RoleAndPermissionSeeder::class,
            TruckTypeSeeder::class,
            CountriesSeeder::class,
            StatesSeeder::class,
            CitiesSeeder::class,
            UserSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}
