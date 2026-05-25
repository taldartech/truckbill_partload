<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least one company
        $company = Company::firstOrCreate(
            ['email' => 'testcompany@example.com'],
            [
                'name' => 'Test Company',
                'phone' => '1112223334',
                'address' => 'Test Address',
                'gst_number' => '22AAAAA0000A1Z5',
                'status' => 1
            ]
        );

        // Create test users
        $users = [
            [
                'name' => 'Test Admin',
                'email' => 'admin@testcompany.com',
                'role' => 'Admin'
            ],
            [
                'name' => 'Test Manager',
                'email' => 'manager@testcompany.com',
                'role' => 'Manager'
            ],
            [
                'name' => 'Test Staff',
                'email' => 'staff@testcompany.com',
                'role' => 'Staff'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'email_verified_at' => now(),
                ]
            );
            
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
