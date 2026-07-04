<?php

namespace App\Support;

class Money
{
    public static function round(float|int|string $amount): float
    {
        return round((float) $amount, 2);
    }

    /** Format as rupees with paise decimals, e.g. 25164.24 */
    public static function format(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /** Display with rupee symbol, e.g. ₹25,164.24 */
    public static function display(float|int|string $amount): string
    {
        return '₹'.number_format((float) $amount, 2, '.', ',');
    }

    /** Convert stored rupees to paise for Razorpay API only. */
    public static function toPaise(float|int|string $rupees): int
    {
        return (int) round((float) $rupees * 100);
    }

    public static function fromPaise(int $paise): float
    {
        return self::round($paise / 100);
    }
}
