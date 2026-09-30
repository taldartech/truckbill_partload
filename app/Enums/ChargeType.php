<?php

namespace App\Enums;

enum ChargeType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::Percentage => '% of Freight',
        };
    }
}
