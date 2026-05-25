<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Station;
use App\Models\Party;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default company
        $company = Company::create([
            'name' => 'Truckbill Logistics Pvt Ltd',
            'email' => 'contact@truckbill.com',
            'phone' => '9876543210',
            'address' => '123, Logistics Hub, New Delhi',
            'gst_number' => '07AAAAA0000A1Z5',
            'status' => 1
        ]);

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

        // Create Manager and Staff Users for this company
        $manager = User::firstOrCreate(
            ['email' => 'manager@truckbill.com'],
            [
                'name' => 'Company Manager',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('Manager');

        $staff = User::firstOrCreate(
            ['email' => 'staff@truckbill.com'],
            [
                'name' => 'Company Staff',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]
        );
        $staff->assignRole('Staff');
    }
}
