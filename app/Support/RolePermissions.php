<?php

namespace App\Support;

class RolePermissions
{
    public static function modules(): array
    {
        return [
            'companies', 'branches', 'trucks',
            'stations', 'parties', 'vendors', 'agents', 'groups',
            'goods', 'package_types', 'truck_types',
            'bilties', 'challans', 'delivery_receipts', 'invoices',
            'inward_registers', 'loading_reports', 'vouchers',
            'subscriptions', 'subscription_plans', 'roles', 'users',
            'reports',
        ];
    }

    public static function operationalModules(): array
    {
        return [
            'branches', 'trucks', 'stations', 'parties',
            'vendors', 'agents', 'groups', 'goods', 'package_types', 'truck_types',
            'bilties', 'challans', 'delivery_receipts', 'invoices',
            'inward_registers', 'loading_reports', 'vouchers', 'reports',
        ];
    }

    public static function setupModules(): array
    {
        return ['companies', 'branches', 'users', 'subscriptions', 'subscription_plans'];
    }

    public static function sharedMasterDataModules(): array
    {
        return ['stations', 'goods', 'package_types', 'truck_types'];
    }

    public static function permissionNames(array $modules, array $actions): array
    {
        $permissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = "{$action} {$module}";
            }
        }

        return $permissions;
    }

    public static function adminPermissions(): array
    {
        return self::permissionNames(self::operationalModules(), ['view', 'create', 'edit', 'delete']);
    }

    public static function moderatorPermissions(): array
    {
        return array_merge(
            self::permissionNames(self::operationalModules(), ['view', 'create']),
        );
    }

    public static function accountantPermissions(): array
    {
        return array_merge(
            self::permissionNames(
                ['branches', 'trucks', 'stations', 'parties', 'vendors', 'agents', 'bilties', 'challans', 'delivery_receipts', 'invoices', 'inward_registers', 'loading_reports', 'vouchers'],
                ['view']
            ),
            ['view reports'],
        );
    }

    public static function superAdminPermissions(): array
    {
        return array_merge(
            self::permissionNames(['companies', 'branches', 'users'], ['view', 'create', 'edit', 'delete']),
            self::permissionNames(['subscriptions', 'subscription_plans'], ['view', 'create', 'edit', 'delete']),
            self::permissionNames(self::sharedMasterDataModules(), ['view', 'create', 'edit', 'delete']),
        );
    }

    public static function masterAdminPermissions(): array
    {
        return self::permissionNames(
            ['users', 'roles', 'subscriptions', 'subscription_plans'],
            ['view', 'create', 'edit', 'delete'],
        );
    }
}
