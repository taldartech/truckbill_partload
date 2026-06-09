<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'super@truckbill.com')->first();

        $company = Company::firstOrCreate(
            ['email' => 'demo@truckbill.com'],
            [
                'name' => 'Demo Logistics Pvt Ltd',
                'phone' => '9876543210',
                'address' => 'Demo Address',
                'gst_number' => '22AAAAA0000A1Z5',
                'status' => 1,
                'user_id' => $superAdmin?->id,
            ]
        );

        if ($superAdmin && ! $company->user_id) {
            $company->update(['user_id' => $superAdmin->id]);
        }

        $branch = Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Head Office',
            ],
            [
                'is_head_office' => true,
            ]
        );

        $users = [
            [
                'name' => 'Branch Admin',
                'email' => 'admin@demo.com',
                'role' => UserRole::Admin->value,
            ],
            [
                'name' => 'Branch Moderator',
                'email' => 'moderator@demo.com',
                'role' => UserRole::Moderator->value,
            ],
            [
                'name' => 'Branch Accountant',
                'email' => 'accountant@demo.com',
                'role' => UserRole::Accountant->value,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'email_verified_at' => now(),
                ]
            );

            $user->update([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]);

            $user->syncRoles([$userData['role']]);
        }
    }
}
