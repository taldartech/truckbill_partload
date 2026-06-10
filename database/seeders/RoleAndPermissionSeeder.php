<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (UserRole::all() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        foreach (RolePermissions::permissionNames(RolePermissions::modules(), ['view', 'create', 'edit', 'delete']) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::findByName(UserRole::MasterAdmin->value)->syncPermissions(RolePermissions::masterAdminPermissions());
        Role::findByName(UserRole::SuperAdmin->value)->syncPermissions(RolePermissions::superAdminPermissions());
        Role::findByName(UserRole::Admin->value)->syncPermissions(RolePermissions::adminPermissions());
        Role::findByName(UserRole::Moderator->value)->syncPermissions(RolePermissions::moderatorPermissions());
        Role::findByName(UserRole::Accountant->value)->syncPermissions(RolePermissions::accountantPermissions());

        Role::whereNotIn('name', UserRole::all())->delete();

        $masterAdmin = User::firstOrCreate(
            ['email' => 'admin@truckbill.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $masterAdmin->syncRoles([UserRole::MasterAdmin->value]);

        echo "Roles and Permissions seeded successfully!\n";
        echo "Master Admin: admin@truckbill.com / password\n";
    }
}
