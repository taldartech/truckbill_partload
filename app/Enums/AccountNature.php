<?php

namespace App\Enums;

enum AccountNature: string
{
    case Assets = 'assets';
    case Liabilities = 'liabilities';
    case Capital = 'capital';
    case Income = 'income';
    case Expenses = 'expenses';

    public function isDebitNormal(): bool
    {
        return match ($this) {
            self::Assets, self::Expenses => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Assets => 'Assets',
            self::Liabilities => 'Liabilities',
            self::Capital => 'Capital',
            self::Income => 'Income',
            self::Expenses => 'Expenses',
        };
    }
}
