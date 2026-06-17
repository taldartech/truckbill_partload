<?php

namespace App\Enums;

enum UserRole: string
{
    case MasterAdmin = 'master_admin';
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Accountant = 'accountant';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function systemRoles(): array
    {
        return self::all();
    }

    public static function assignableByMasterAdmin(): array
    {
        return [];
    }

    public static function assignableBySuperAdmin(): array
    {
        return [
            self::Admin->value,
            self::Moderator->value,
            self::Accountant->value,
        ];
    }

    public static function requiresBranchAssignment(): array
    {
        return [
            self::Admin->value,
            self::Moderator->value,
            self::Accountant->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::MasterAdmin => 'Master Admin',
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Moderator => 'Moderator',
            self::Accountant => 'Accountant',
        };
    }
}
