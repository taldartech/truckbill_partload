<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create user roles as requested
        $roles = [
            'MasterAdmin',
            'Admin',
            'Moderator',
            'Accountant',
            'Manager',
            'Staff'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Define modules
        $modules = [
            'companies', 'branches', 'transporters', 'trucks', 
            'drivers', 'stations', 'parties', 'agents', 'groups',
            'goods', 'package_types', 'truck_types',
            // Transactions
            'bilties', 'challans', 'delivery_receipts', 'invoices', 
            'inward_registers', 'loading_reports', 'vouchers',
            // System & Settings
            'subscriptions', 'subscription_plans', 'roles', 'users', 
            'settings', 'reports'
        ];

        // Create Permissions
        $actions = ['view', 'create', 'edit', 'delete'];
        
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$module}"]);
            }
        }

        // Create Roles and Assign Permissions
        $masterAdminRole = Role::firstOrCreate(['name' => 'MasterAdmin']);
        $masterAdminRole->givePermissionTo(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            'view branches', 'view transporters', 'view trucks', 
            'view drivers', 'view stations', 'view parties', 'view agents', 'view groups',
            'view goods', 'view package_types', 'view truck_types',
            // Transactions
            'view bilties', 'view challans', 'view delivery_receipts', 'view invoices', 
            'view inward_registers', 'view loading_reports', 'view vouchers',
            // System & Settings
            'view subscriptions', 'view subscription_plans', 'view roles', 'view users', 
            'view settings', 'view reports',

            'create branches', 'create transporters', 'create trucks', 
            'create drivers', 'create stations', 'create parties', 'create agents', 'create groups',
            'create goods', 'create package_types', 'create truck_types',
            // Transactions
            'create bilties', 'create challans', 'create delivery_receipts', 'create invoices', 
            'create inward_registers', 'create loading_reports', 'create vouchers',
            // System & Settings
            'create subscriptions', 'create subscription_plans', 'create roles', 'create users', 
            'create settings', 'create reports',

            'edit branches', 'edit transporters', 'edit trucks', 
            'edit drivers', 'edit stations', 'edit parties', 'edit agents', 'edit groups',
            'edit goods', 'edit package_types', 'edit truck_types',
            // Transactions
            'edit bilties', 'edit challans', 'edit delivery_receipts', 'edit invoices', 
            'edit inward_registers', 'edit loading_reports', 'edit vouchers',
            // System & Settings
            'edit subscriptions', 'edit subscription_plans', 'edit roles', 'edit users', 
            'edit settings', 'edit reports',

            'delete branches', 'delete transporters', 'delete trucks', 
            'delete drivers', 'delete stations', 'delete parties', 'delete agents', 'delete groups',
            'delete goods', 'delete package_types', 'delete truck_types',
            // Transactions
            'delete bilties', 'delete challans', 'delete delivery_receipts', 'delete invoices', 
            'delete inward_registers', 'delete loading_reports', 'delete vouchers',
            // System & Settings
            'delete subscriptions', 'delete subscription_plans', 'delete roles', 'delete users', 
            'delete settings', 'delete reports'
        ]);

        $moderatorRole = Role::firstOrCreate(['name' => 'Moderator']);
        $moderatorRole->givePermissionTo([
            'view branches', 'view transporters', 'view trucks', 
            'view drivers', 'view stations', 'view parties', 'view agents', 'view groups',
            'view goods', 'view package_types', 'view truck_types',
            // Transactions
            'view bilties', 'view challans', 'view delivery_receipts', 'view invoices', 
            'view inward_registers', 'view loading_reports', 'view vouchers',
            // System & Settings
            'view subscriptions', 'view subscription_plans', 'view roles', 'view users', 
            'view settings', 'view reports',

            'create branches', 'create transporters', 'create trucks', 
            'create drivers', 'create stations', 'create parties', 'create agents', 'create groups',
            'create goods', 'create package_types', 'create truck_types',
            // Transactions
            'create bilties', 'create challans', 'create delivery_receipts', 'create invoices', 
            'create inward_registers', 'create loading_reports', 'create vouchers',
            // System & Settings
            'create subscriptions', 'create subscription_plans', 'create roles', 'create users', 
            'create settings', 'create reports',
        ]);

        $accountantRole = Role::firstOrCreate(['name' => 'Accountant']);
        $accountantRole->givePermissionTo([ 
            'view branches', 'view transporters', 'view trucks',
            'view drivers', 'view stations', 'view parties', 'view bilties', 'view challans',
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->givePermissionTo([ 
            'view branches', 'view transporters', 'view trucks',
            'view drivers', 'view stations', 'view parties', 'view bilties', 'view challans',
            'create bilties', 'create challans'
        ]);

        $staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $staffRole->givePermissionTo([
            'view bilties', 'view challans', 'create bilties', 'create challans'
        ]);

        // Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@truckbill.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole($masterAdminRole);

        echo "Roles and Permissions seeded successfully!\n";
        echo "Admin User: admin@truckbill.com / password\n";
    }
}
